<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Brand;
use App\Models\Contacts;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    // Halaman utama (home)
    public function home()
    {
        // Ambil brand beserta beberapa produknya
        $brands = Brand::with(['products' => function($query) {
            $query->with('variants')->withReviewSummary()->latest()->limit(4);
        }])->get();

        // Ambil satu produk terbaru dari setiap brand
        $featuredProducts = \App\Models\Product::with(['brand', 'variants'])
            ->withReviewSummary()
            ->whereIn('id', function($query) {
                $query->selectRaw('MAX(id)')
                    ->from('products')
                    ->groupBy('brand_id');
            })
            ->latest()
            ->get();

        // Ambil berita & kegiatan terbaru
        $latestNews = \App\Models\News::orderByDesc('published_at')->limit(3)->get();

        return view('pages.home', compact('brands', 'featuredProducts', 'latestNews'));
    }

    // Halaman daftar brand dari DB
    public function brands()
    {
        $brands = Brand::all();
        $products = \App\Models\Product::with(['brand', 'variants'])
            ->withReviewSummary()
            ->orderByRaw("CASE 
                WHEN brand_id = 1 THEN 1 
                WHEN brand_id = 2 THEN 2 
                ELSE 3 END")
            ->latest()
            ->get();
        return view('pages.brands', compact('brands', 'products'));
    }

    // Halaman detail brand (produk)
    public function brandShow(Brand $brand)
    {
        // Jika Brand mempunyai relasi products, tampilkan; jika tidak, kosong
        $products = method_exists($brand, 'products')
            ? $brand->products()->with('variants')->withReviewSummary()->get()
            : collect();
        return view('pages.brands.show', compact('brand', 'products'));
    }

    // Halaman detail produk
    public function productShow(Product $product)
    {
        $product->load(['brand', 'variants'])
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        $relatedProducts = Product::with(['brand', 'variants'])
            ->withReviewSummary()
            ->where('brand_id', $product->brand_id)
            ->whereKeyNot($product->id)
            ->latest()
            ->limit(3)
            ->get();

        $productReviews = $product->reviews()
            ->with(['user', 'productVariant'])
            ->latest()
            ->limit(8)
            ->get();

        return view('pages.products.show', compact('product', 'productReviews', 'relatedProducts'));
    }

    // Halaman berita dan kegiatan dari DB
    public function news()
    {
        $news = News::orderByDesc('published_at')->get();
        return view('pages.news', compact('news'));
    }

    public function newsShow(News $news)
    {
        $relatedNews = News::whereKeyNot($news->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('pages.news-show', compact('news', 'relatedNews'));
    }

    // Halaman tentang perusahaan
    public function about()
    {
        return view('pages.about');
    }

    // Halaman kontak
    public function contact()
    {
        return view('pages.contact');
    }

    // Simpan kontak ke DB
    public function sendContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // Simpan ke database
        Contacts::create($validated);

        return redirect()->route('contact')->with('success', 'Terima kasih, pesan Anda telah dikirim.');
    }

    // Hasil pencarian (opsional)
    public function search(Request $request)
    {
        $query = $request->input('q');
        $products = collect();
        $brands = collect();
        $news = collect();

        if (filled($query)) {
            $like = '%' . $query . '%';

            $products = Product::with(['brand', 'variants'])
                ->withReviewSummary()
                ->where('name', 'like', $like)
                ->orWhere('size', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->latest()
                ->limit(12)
                ->get();

            $brands = Brand::where('name', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->latest()
                ->limit(8)
                ->get();

            $news = News::where('title', 'like', $like)
                ->orWhere('content', 'like', $like)
                ->orderByDesc('published_at')
                ->limit(8)
                ->get();
        }

        return view('pages.search', compact('query', 'products', 'brands', 'news'));
    }
}

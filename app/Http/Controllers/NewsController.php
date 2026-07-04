<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Tampilkan daftar berita (admin index)
     */
    public function index()
    {
        $news = News::latest('published_at')->paginate(10);
        $brands = Brand::with('products')->get();
        return view('admin.news.index', compact('news', 'brands'));
    }

    /**
     * Tampilkan form create berita
     */
    public function create()
    {
        return view('admin.news.create');
    }

    /**
     * SIMPAN berita ke database (POST /admin/news)
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'published_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Handle upload thumbnail
        if ($request->hasFile('thumbnail')) {
            // Buat folder jika belum ada
            if (!is_dir(public_path('images/news'))) {
                mkdir(public_path('images/news'), 0755, true);
            }

            $file = $request->file('thumbnail');
            $filename = time() . '_' . Str::slug($validated['title']) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/news'), $filename);
            $validated['thumbnail'] = 'images/news/' . $filename;
        }

        // Generate unique slug dari title
        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug;
        $i = 1;
        while (News::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        $validated['slug'] = $slug;

        // Jika published_at kosong, gunakan waktu sekarang
        if (empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        // Simpan ke database dengan handling error
        try {
            News::create($validated);
        } catch (\Exception $e) {
            \Log::error('News store failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan berita. Silakan coba lagi.');
        }

        // Redirect dengan pesan success
        return redirect()->route('admin.news.index')
                        ->with('success', 'Berita "' . $validated['title'] . '" berhasil disimpan dan dipublikasikan.');
    }

    /**
     * Tampilkan form edit berita
     */
    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    /**
     * Update berita (PUT /admin/news/{id})
     */
    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'published_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Handle upload thumbnail baru
        if ($request->hasFile('thumbnail')) {
            // Hapus file lama
            if ($news->thumbnail && file_exists(public_path($news->thumbnail))) {
                @unlink(public_path($news->thumbnail));
            }

            if (!is_dir(public_path('images/news'))) {
                mkdir(public_path('images/news'), 0755, true);
            }

            $file = $request->file('thumbnail');
            $filename = time() . '_' . Str::slug($validated['title']) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/news'), $filename);
            $validated['thumbnail'] = 'images/news/' . $filename;
        }

        // Generate unique slug and ensure it doesn't clash with other records
        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug;
        $i = 1;
        while (News::where('slug', $slug)->where('id', '!=', $news->id)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        $validated['slug'] = $slug;

        if (empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        try {
            $news->update($validated);
        } catch (\Exception $e) {
            \Log::error('News update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui berita. Silakan coba lagi.');
        }

        return redirect()->route('admin.news.index')
                        ->with('success', 'Berita berhasil diperbarui.');
    }

    /**
     * Hapus berita (DELETE /admin/news/{id})
     */
    public function destroy(News $news)
    {
        // Hapus file thumbnail
        if ($news->thumbnail && file_exists(public_path($news->thumbnail))) {
            @unlink(public_path($news->thumbnail));
        }

        $news->delete();

        return redirect()->route('admin.news.index')
                        ->with('success', 'Berita berhasil dihapus.');
    }
}

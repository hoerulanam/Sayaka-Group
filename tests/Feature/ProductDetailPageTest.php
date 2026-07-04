<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_public_product_detail_and_see_full_description(): void
    {
        $brand = Brand::create(['name' => 'Detail Test Brand']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Detail Cleaner Kit',
            'code' => 'DTL-001',
            'size' => '100ml',
            'slug' => 'detail-cleaner-kit',
            'description' => 'Deskripsi lengkap produk ini harus tampil penuh di halaman detail, termasuk manfaat dan cara pakai.',
            'price' => 125000,
            'stock' => 7,
        ]);
        $this->createReviewForProduct(
            $product,
            rating: 4,
            userName: 'Pelanggan Detail',
            reviewText: 'Aromanya enak dan deskripsi produknya sesuai.'
        );

        $this->get(route('products.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('Detail Cleaner Kit')
            ->assertSee('Detail Test Brand')
            ->assertSee('DTL-001')
            ->assertSee('100ml')
            ->assertSee('Deskripsi lengkap produk ini harus tampil penuh di halaman detail, termasuk manfaat dan cara pakai.')
            ->assertSee('Rp 125.000')
            ->assertSee('Stok Tersedia')
            ->assertSee('7')
            ->assertSee('4,0/5')
            ->assertSee('1 review')
            ->assertSee('Review Produk')
            ->assertSee('Ulasan dari pengguna lain')
            ->assertSee('Pelanggan Detail')
            ->assertSee('Aromanya enak dan deskripsi produknya sesuai.')
            ->assertSee('4/5')
            ->assertSee('href="' . route('register') . '"', false);
    }

    public function test_variant_product_detail_shows_variant_rows_and_purchase_modal(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'SummerLabs']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Variant Detail Perfume',
            'code' => 'VDP-001',
            'slug' => 'variant-detail-perfume',
            'description' => 'Parfum dengan beberapa ukuran.',
            'price' => 99000,
            'stock' => 0,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'size' => '30ml',
            'code' => 'VDP-001-30ML',
            'price' => 99000,
            'stock' => 3,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'size' => '50ml',
            'code' => 'VDP-001-50ML',
            'price' => 139000,
            'stock' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('products.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('Varian Tersedia')
            ->assertSee('30ml')
            ->assertSee('VDP-001-30ML')
            ->assertSee('Rp 99.000')
            ->assertSee('Stok 3')
            ->assertSee('50ml')
            ->assertSee('VDP-001-50ML')
            ->assertSee('Rp 139.000')
            ->assertSee('Stok habis')
            ->assertSee('data-variant-modal-open', false)
            ->assertSee('Pilih ukuran botol')
            ->assertSee('catalog-variant-modal-' . $product->id, false)
            ->assertSee('name="purchase_mode" value="cart"', false);
    }

    public function test_catalog_cards_link_to_public_product_detail(): void
    {
        $brand = Brand::create(['name' => 'Linked Catalog Brand']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Linked Catalog Product',
            'code' => 'LINK-001',
            'slug' => 'linked-catalog-product',
            'description' => 'Produk untuk memastikan semua kartu katalog membuka detail produk.',
            'price' => 45000,
            'stock' => 5,
        ]);
        $detailUrl = route('products.show', ['product' => $product->slug]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="' . $detailUrl . '"', false);

        $this->get(route('brands.index'))
            ->assertOk()
            ->assertSee('href="' . $detailUrl . '"', false);

        $this->get(route('brands.show', $brand))
            ->assertOk()
            ->assertSee('href="' . $detailUrl . '"', false);

        $this->get(route('search', ['q' => 'Linked Catalog Product']))
            ->assertOk()
            ->assertSee('href="' . $detailUrl . '"', false)
            ->assertSee('Lihat Produk');
    }

    private function createReviewForProduct(
        Product $product,
        int $rating,
        string $userName = 'Pelanggan SAYAKA',
        string $reviewText = 'Produk sesuai deskripsi.'
    ): void
    {
        $user = User::factory()->create(['name' => $userName]);
        $order = Order::create([
            'user_id' => $user->id,
            'total' => $product->price,
            'status' => 'received',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Detail No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 0,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        ProductReview::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'rating' => $rating,
            'review' => $reviewText,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileCustomerUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_layout_renders_accessible_mobile_navigation(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('data-mobile-menu-toggle', false)
            ->assertSee('aria-controls="mobileMenuPanel"', false)
            ->assertSee('id="mobileMenuPanel"', false)
            ->assertSee('id="mobile-header-search-input"', false)
            ->assertSee('aria-label="Navigasi utama mobile"', false);
    }

    public function test_customer_mobile_navigation_includes_cart_and_orders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('href="' . route('cart.index') . '"', false)
            ->assertSee('href="' . route('orders.index') . '"', false)
            ->assertSee('aria-label="Keranjang"', false);
    }

    public function test_catalog_cart_button_marks_scroll_restore_behavior(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'Scroll Test Brand']);
        Product::create([
            'brand_id' => $brand->id,
            'name' => 'Scroll Restore Product',
            'code' => 'SCROLL-RESTORE-001',
            'slug' => 'scroll-restore-product',
            'description' => 'Produk untuk memastikan klik keranjang mempertahankan posisi scroll.',
            'price' => 25000,
            'stock' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('data-preserve-scroll="cart-add"', false)
            ->assertSee('window.rememberCartClickPosition', false)
            ->assertSee('sayaka:cart-scroll-restore', false);
    }
}

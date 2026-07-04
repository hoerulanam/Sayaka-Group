<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutStockWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_page_shows_warning_when_cart_quantity_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'SummerLabs']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Cart Warning Product',
            'slug' => 'cart-warning-product',
            'price' => 75000,
            'stock' => 2,
        ]);

        Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-item-stock-warning', false)
            ->assertSee('hanya tersedia 2 item');
    }

    public function test_direct_checkout_page_renders_stock_warning_element(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'FORTEGO']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Direct Warning Product',
            'slug' => 'direct-warning-product',
            'price' => 65000,
            'stock' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('checkout.product', $product))
            ->assertOk()
            ->assertSee('quantityStockWarning', false)
            ->assertSee('Stok tersedia: 2 item');
    }

    public function test_checkout_page_redirects_to_cart_when_selected_quantity_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'SummerLabs']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Stock Limited Serum',
            'slug' => 'stock-limited-serum',
            'price' => 75000,
            'stock' => 2,
        ]);
        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('checkout.index', ['cart_items' => [$cart->id]]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', function (string $message) {
                return str_contains($message, 'hanya tersedia 2 item')
                    && str_contains($message, 'jumlah yang dipilih 3 item');
            });
    }

    public function test_cart_checkout_rejects_quantity_that_exceeds_current_stock(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'NOID']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'NOID Stock Guard',
            'slug' => 'noid-stock-guard',
            'price' => 50000,
            'stock' => 1,
        ]);
        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.store'), $this->checkoutPayload([
                'cart_items' => [$cart->id],
            ]))
            ->assertSessionHasErrors('stock')
            ->assertSessionHas('error', function (string $message) {
                return str_contains($message, 'hanya tersedia 1 item')
                    && str_contains($message, 'jumlah yang dipilih 2 item');
            });

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 1,
        ]);
    }

    public function test_direct_checkout_rejects_quantity_that_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'FORTEGO']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'FORTEGO Limited',
            'slug' => 'fortego-limited',
            'price' => 65000,
            'stock' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), $this->checkoutPayload([
                'quantity' => 4,
            ]))
            ->assertSessionHasErrors('quantity')
            ->assertSessionHas('error', function (string $message) {
                return str_contains($message, 'hanya tersedia 2 item')
                    && str_contains($message, 'jumlah yang dipilih 4 item');
            });

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'cart_items' => [],
            'quantity' => 1,
            'payment_method' => 'midtrans_snap',
            'destination_city' => 'jakarta',
            'shipping_address' => 'Jl. Testing No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 12000,
        ], $overrides);
    }
}

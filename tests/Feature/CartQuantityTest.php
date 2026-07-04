<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'SummerShoesCare']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Cleaner Kit',
            'slug' => 'cleaner-kit',
            'price' => 50000,
            'stock' => 5,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('cart.update', $cart), [
                'quantity' => 3,
                'action' => 'set',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_cart_item_quantity_cannot_exceed_stock(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'NOID']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Noid Blast',
            'slug' => 'noid-blast-test',
            'price' => 35000,
            'stock' => 2,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->patch(route('cart.update', $cart), [
                'quantity' => 2,
                'action' => 'increase',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', "Stok {$product->name} hanya tersedia 2 item.");

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_ajax_cart_quantity_update_returns_updated_line_total(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'FORTEGO']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Metal Polish',
            'slug' => 'metal-polish-test',
            'price' => 30000,
            'stock' => 4,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->patchJson(route('cart.update', $cart), [
                'quantity' => 3,
                'action' => 'set',
            ])
            ->assertOk()
            ->assertJsonPath('quantity', 3)
            ->assertJsonPath('line_total', 90000)
            ->assertJsonPath('formatted_line_total', 'Rp 90.000');
    }

    public function test_ajax_cart_quantity_update_returns_stock_warning_when_quantity_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'SummerLabs']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Limited Toner',
            'slug' => 'limited-toner-test',
            'price' => 40000,
            'stock' => 2,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->patchJson(route('cart.update', $cart), [
                'quantity' => 5,
                'action' => 'set',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Stok Limited Toner hanya tersedia 2 item.')
            ->assertJsonPath('quantity', 1)
            ->assertJsonPath('stock', 2);
    }
}

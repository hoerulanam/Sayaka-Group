<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_index_renders_modern_order_cards(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Modern Cleaner');
        $order = $this->createOrder($user, [
            'status' => 'shipped',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50000,
        ]);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Riwayat Pesanan')
            ->assertSee('Kode Pesanan ' . $order->displayCode())
            ->assertDontSee('Pesanan #' . $order->id)
            ->assertSee('order-card-modern', false)
            ->assertSee('Dalam Pengiriman')
            ->assertSee('Modern Cleaner')
            ->assertSee('Lihat Detail');
    }

    public function test_order_detail_renders_modern_tracking_layout(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Premium Polish');
        $order = $this->createOrder($user, [
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 88000,
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Detail Pesanan')
            ->assertSee($order->displayCode())
            ->assertDontSee('Pesanan #' . $order->id)
            ->assertSee('order-detail-timeline', false)
            ->assertSee('Sedang Dikemas')
            ->assertSee('Premium Polish')
            ->assertSee('Bayar Sekarang')
            ->assertSee('Chat Admin');
    }

    private function createProduct(string $name): Product
    {
        $brand = Brand::create(['name' => 'SAYAKA Test Brand']);

        return Product::create([
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => str($name)->slug() . '-' . uniqid(),
            'price' => 50000,
            'stock' => 10,
        ]);
    }

    private function createOrder(User $user, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id,
            'total' => 112000,
            'status' => 'pending',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'pending',
            'shipping_address' => 'Jl. Pesanan Modern No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 12000,
        ], $overrides));
    }
}

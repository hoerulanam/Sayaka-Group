<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_unpaid_order(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Cancel Test Product', 50000, 10);
        $order = $this->createOrder($user, $product, 'pending', 'pending');

        $this->actingAs($user)
            ->post(route('payment.cancel', $order))
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('cancelled', $order->payment_status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_customer_cannot_cancel_paid_order(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Paid Cancel Product', 50000, 10);
        $order = $this->createOrder($user, $product, 'confirmed', 'paid');

        $this->actingAs($user)
            ->post(route('payment.cancel', $order))
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_admin_can_cancel_pending_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $product = $this->createProduct('Admin Pending Cancel Product', 50000, 10);
        $order = $this->createOrder($customer, $product, 'pending', 'pending');

        $this->actingAs($admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('cancelled', $order->payment_status);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseHas('messages', [
            'to_user_id' => $customer->id,
        ]);
    }

    public function test_admin_cancelling_paid_order_restores_product_stock(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $product = $this->createProduct('Admin Paid Restores Stock Product', 50000, 5);
        $order = $this->createOrder($customer, $product, 'confirmed', 'paid');

        $this->actingAs($admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('cancelled', $order->payment_status);
        $this->assertSame(6, $product->fresh()->stock);
    }

    public function test_admin_cannot_cancel_shipped_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $product = $this->createProduct('Shipped Cancel Test Product', 50000, 10);
        $order = $this->createOrder($customer, $product, 'shipped', 'paid');

        $this->actingAs($admin)
            ->patch(route('admin.orders.cancel', $order))
            ->assertSessionHas('error');

        $this->assertSame('shipped', $order->fresh()->status);
    }

    private function createProduct(string $name, int $price = 50000, int $stock = 10): Product
    {
        $brand = Brand::create(['name' => 'Cancel Brand']);

        return Product::create([
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => 'cancel-' . time() . '-' . rand(100, 999),
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    private function createOrder(User $user, Product $product, string $status = 'pending', string $paymentStatus = 'pending'): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'total' => 62000,
            'status' => $status,
            'payment_method' => 'midtrans_snap',
            'payment_status' => $paymentStatus,
            'shipping_address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'REG',
            'shipping_cost' => 12000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 50000,
        ]);

        return $order;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_return_request_for_received_order(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $order = Order::create([
            'user_id' => $user->id,
            'total' => 100000,
            'status' => 'received',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Merdeka No 123',
            'phone' => '08123456789',
        ]);

        $file = UploadedFile::fake()->image('bukti.jpg', 600, 600);

        $response = $this->actingAs($user)->post(route('orders.return.store', $order), [
            'reason' => 'Barang Rusak/Cacat',
            'description' => 'Produk terdapat goresan pada bagian botol.',
            'proof_image' => $file,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Budi Santoso',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('order_returns', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Barang Rusak/Cacat',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Budi Santoso',
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_submit_return_for_unreceived_order(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'total' => 100000,
            'status' => 'pending',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'paid',
        ]);

        $file = UploadedFile::fake()->image('bukti.jpg');

        $response = $this->actingAs($user)->post(route('orders.return.store', $order), [
            'reason' => 'Barang Rusak/Cacat',
            'description' => 'Barang cacat.',
            'proof_image' => $file,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Budi Santoso',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('order_returns', 0);
    }

    public function test_admin_can_approve_return_request(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $order = Order::create([
            'user_id' => $user->id,
            'total' => 50000,
            'status' => 'received',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'paid',
        ]);

        $orderReturn = OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Tidak Sesuai Pesanan',
            'description' => 'Salah produk',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.returns.approve', $orderReturn), [
            'admin_note' => 'Kirim kembali ke Alamat Gudang: Jalan Industri No 45, Jakarta.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_reject_return_request(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $order = Order::create([
            'user_id' => $user->id,
            'total' => 50000,
            'status' => 'received',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'paid',
        ]);

        $orderReturn = OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Lainnya',
            'description' => 'Tidak suka warnanya',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.returns.reject', $orderReturn), [
            'admin_note' => 'Retur tidak dapat diproses untuk alasan selera warna.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => 'rejected',
            'admin_note' => 'Retur tidak dapat diproses untuk alasan selera warna.',
        ]);
    }

    public function test_admin_completing_return_restores_product_stock_and_saves_refund_proof(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'Brand Test', 'description' => 'Desc']);

        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Produk Retur',
            'code' => 'PRD-001',
            'slug' => 'produk-retur',
            'price' => 50000,
            'stock' => 5,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'total' => 100000,
            'status' => 'received',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50000,
        ]);

        $orderReturn = OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Barang Rusak/Cacat',
            'description' => 'Cacat pabrik',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Budi Santoso',
            'status' => 'approved',
        ]);

        $refundProofFile = UploadedFile::fake()->image('struk_refund.jpg', 600, 600);

        $response = $this->actingAs($admin)->patch(route('admin.returns.complete', $orderReturn), [
            'refund_amount' => 100000,
            'refund_proof_image' => $refundProofFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => 'completed',
            'refund_amount' => 100000,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'returned',
        ]);

        $this->assertEquals(7, $product->fresh()->stock);
    }
}

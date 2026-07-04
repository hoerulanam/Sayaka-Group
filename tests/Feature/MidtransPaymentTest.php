<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.client_key' => 'SB-Mid-client-test',
            'services.midtrans.is_production' => false,
        ]);
    }

    public function test_checkout_creates_midtrans_snap_order_and_keeps_current_stock_timing(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Midtrans Cleaner', 100000, 5);

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), $this->checkoutPayload())
            ->assertRedirect();

        $order = Order::where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->assertSame('midtrans_snap', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertEquals(112000, (int) $order->total);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_payment_page_creates_snap_token_and_renders_snap_popup_button(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithItem($user);

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-test',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-test',
            ], 201),
        ]);

        $this->actingAs($user)
            ->get(route('payment.show', $order))
            ->assertOk()
            ->assertSee('https://app.sandbox.midtrans.com/snap/snap.js', false)
            ->assertSee('SB-Mid-client-test')
            ->assertSee('snap-token-test')
            ->assertSee('Bayar Sekarang');

        Http::assertSent(function (HttpRequest $request) use ($order) {
            return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                && $request['transaction_details']['order_id'] === $order->fresh()->midtrans_order_id
                && $request['transaction_details']['gross_amount'] === 112000;
        });

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'midtrans_snap_token' => 'snap-token-test',
            'midtrans_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-test',
        ]);
    }

    public function test_midtrans_notification_marks_order_as_paid_with_valid_signature(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithItem($user, [
            'midtrans_order_id' => 'SYK-TEST-000001',
        ]);

        $payload = $this->signedNotificationPayload([
            'order_id' => 'SYK-TEST-000001',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ]);

        $this->postJson(route('midtrans.notification'), $payload)
            ->assertOk()
            ->assertJson(['message' => 'Notification processed.']);

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('settlement', $order->midtrans_transaction_status);
        $this->assertSame('qris', $order->midtrans_payment_type);
        $this->assertNotNull($order->paid_at);
    }

    public function test_midtrans_notification_rejects_invalid_signature(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithItem($user, [
            'midtrans_order_id' => 'SYK-TEST-000002',
        ]);

        $payload = $this->signedNotificationPayload([
            'order_id' => 'SYK-TEST-000002',
            'signature_key' => 'invalid-signature',
        ]);

        $this->postJson(route('midtrans.notification'), $payload)
            ->assertForbidden();

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_midtrans_notification_does_not_downgrade_paid_order(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithItem($user, [
            'payment_status' => 'paid',
            'paid_at' => now(),
            'midtrans_order_id' => 'SYK-TEST-000003',
        ]);

        $payload = $this->signedNotificationPayload([
            'order_id' => 'SYK-TEST-000003',
            'transaction_status' => 'pending',
        ]);

        $this->postJson(route('midtrans.notification'), $payload)->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_user_can_sync_payment_status_from_midtrans_status_api(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithItem($user, [
            'midtrans_order_id' => 'SYK-TEST-000004',
            'midtrans_snap_token' => 'snap-token-test',
            'midtrans_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-test',
        ]);

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/SYK-TEST-000004/status' => Http::response($this->signedNotificationPayload([
                'order_id' => 'SYK-TEST-000004',
                'transaction_status' => 'settlement',
                'payment_type' => 'bank_transfer',
            ]), 200),
        ]);

        $this->actingAs($user)
            ->post(route('payment.sync', $order))
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('bank_transfer', $order->midtrans_payment_type);
        $this->assertNotNull($order->paid_at);
    }

    private function createProduct(string $name, int $price = 100000, int $stock = 10): Product
    {
        $brand = Brand::create(['name' => 'Midtrans Test Brand']);

        return Product::create([
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    private function createOrderWithItem(User $user, array $overrides = []): array
    {
        $product = $this->createProduct('Midtrans Order Product');
        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'total' => 112000,
            'status' => 'pending',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'pending',
            'shipping_address' => 'Jl. Midtrans Sandbox No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 12000,
        ], $overrides));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100000,
        ]);

        return [$order, $product];
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'quantity' => 1,
            'payment_method' => 'midtrans_snap',
            'destination_city' => 'jakarta',
            'shipping_address' => 'Jl. Midtrans Sandbox No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 12000,
        ], $overrides);
    }

    private function signedNotificationPayload(array $overrides = []): array
    {
        $payload = array_merge([
            'order_id' => 'SYK-TEST-000001',
            'status_code' => '200',
            'gross_amount' => '112000.00',
            'transaction_id' => 'midtrans-transaction-test',
            'transaction_status' => 'pending',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
            'status_message' => 'Success, transaction is found',
        ], $overrides);

        $payload['signature_key'] = $payload['signature_key'] ?? hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('services.midtrans.server_key')
        );

        return $payload;
    }
}

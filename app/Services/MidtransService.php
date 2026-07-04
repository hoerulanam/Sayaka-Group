<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransService
{
    public function ensureSnapTransaction(Order $order): Order
    {
        if ($order->midtrans_snap_token && $order->midtrans_redirect_url) {
            return $order;
        }

        $order->loadMissing(['items.product', 'user']);

        if (! $order->midtrans_order_id) {
            $order->forceFill([
                'midtrans_order_id' => $order->displayCode(),
            ])->save();
        }

        $response = $this->request()
            ->post($this->snapTransactionUrl(), $this->snapPayload($order))
            ->throw()
            ->json();

        $order->forceFill([
            'midtrans_snap_token' => $response['token'] ?? null,
            'midtrans_redirect_url' => $response['redirect_url'] ?? null,
        ])->save();

        return $order;
    }

    /**
     * @throws RequestException
     */
    public function fetchStatus(Order $order): array
    {
        if (! $order->midtrans_order_id) {
            throw new RuntimeException('Order belum memiliki ID transaksi Midtrans.');
        }

        return $this->request()
            ->get($this->statusUrl($order->midtrans_order_id))
            ->throw()
            ->json();
    }

    public function applyTransactionStatus(Order $order, array $payload): Order
    {
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $paymentStatus = $this->paymentStatusFromMidtrans($transactionStatus, $fraudStatus);

        $updates = [
            'midtrans_transaction_id' => $payload['transaction_id'] ?? $order->midtrans_transaction_id,
            'midtrans_payment_type' => $payload['payment_type'] ?? $order->midtrans_payment_type,
            'midtrans_transaction_status' => $transactionStatus ?? $order->midtrans_transaction_status,
            'midtrans_fraud_status' => $fraudStatus ?? $order->midtrans_fraud_status,
            'midtrans_status_code' => $payload['status_code'] ?? $order->midtrans_status_code,
            'midtrans_status_message' => $payload['status_message'] ?? $order->midtrans_status_message,
            'midtrans_payload' => $payload,
        ];

        if ($order->payment_status !== 'paid' && $paymentStatus) {
            $updates['payment_status'] = $paymentStatus;

            if ($paymentStatus === 'paid') {
                $updates['paid_at'] = now();
            }
        }

        $order->forceFill($updates)->save();

        return $order->refresh();
    }

    public function isValidSignature(array $payload): bool
    {
        $serverKey = $this->serverKey();
        $signature = $payload['signature_key'] ?? null;

        if (! $serverKey || ! $signature) {
            return false;
        }

        $signatureFields = ($payload['order_id'] ?? '')
            . ($payload['status_code'] ?? '')
            . ($payload['gross_amount'] ?? '')
            . $serverKey;

        return hash_equals(hash('sha512', $signatureFields), $signature);
    }

    public function clientKey(): ?string
    {
        return config('services.midtrans.client_key');
    }

    public function snapScriptUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    private function snapPayload(Order $order): array
    {
        $itemDetails = $this->itemDetails($order);
        $grossAmount = collect($itemDetails)->sum(fn (array $item) => $item['price'] * $item['quantity']);

        return [
            'transaction_details' => [
                'order_id' => $order->midtrans_order_id,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $order->user?->name ?? 'Customer',
                'email' => $order->user?->email,
                'phone' => $order->phone ?? $order->user?->phone,
                'shipping_address' => [
                    'first_name' => $order->user?->name ?? 'Customer',
                    'phone' => $order->phone ?? $order->user?->phone,
                    'address' => $order->shipping_address,
                ],
            ],
            'item_details' => $itemDetails,
            'credit_card' => [
                'secure' => true,
            ],
            'callbacks' => [
                'finish' => route('orders.show', $order),
            ],
        ];
    }

    private function itemDetails(Order $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                'id' => (string) ($item->product?->code ?: 'ITEM-' . $item->id),
                'price' => (int) round((float) $item->price),
                'quantity' => (int) $item->quantity,
                'name' => Str::limit($item->product?->name ?? 'Produk Sayaka', 50, ''),
            ];
        }

        $targetTotal = (int) round((float) $order->total);
        $productTotal = collect($items)->sum(fn (array $item) => $item['price'] * $item['quantity']);
        $shippingCost = max(0, $targetTotal - $productTotal);

        if ($shippingCost > 0) {
            $items[] = [
                'id' => 'SHIPPING',
                'price' => $shippingCost,
                'quantity' => 1,
                'name' => Str::limit('Ongkos kirim ' . ($order->shipping_carrier ?: 'Sayaka Ship'), 50, ''),
            ];
        }

        return $items;
    }

    private function paymentStatusFromMidtrans(?string $transactionStatus, ?string $fraudStatus): ?string
    {
        return match ($transactionStatus) {
            'settlement' => 'paid',
            'capture' => $fraudStatus === 'accept' ? 'paid' : null,
            'pending' => 'pending',
            'deny' => 'denied',
            'cancel' => 'cancelled',
            'expire' => 'expired',
            'failure' => 'failed',
            default => null,
        };
    }

    private function request()
    {
        $serverKey = $this->serverKey();

        if (! $serverKey) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withBasicAuth($serverKey, '');
    }

    private function snapTransactionUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private function statusUrl(string $midtransOrderId): string
    {
        $baseUrl = $this->isProduction()
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';

        return $baseUrl . '/' . rawurlencode($midtransOrderId) . '/status';
    }

    private function serverKey(): ?string
    {
        return config('services.midtrans.server_key');
    }

    private function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production', false);
    }
}

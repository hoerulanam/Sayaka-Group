<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        private MidtransService $midtrans,
        private ProductVariantService $variantService
    ) {
    }

    public function show(Order $order)
    {
        $this->authorizeOrderOwner($order);

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order)->with('success', 'Pesanan ini sudah dibayar.');
        }

        $midtransError = null;

        if ($this->canStartPayment($order)) {
            try {
                $order = $this->midtrans->ensureSnapTransaction($order);
            } catch (Throwable $exception) {
                report($exception);
                $midtransError = 'Transaksi Midtrans belum bisa dibuat. Periksa konfigurasi sandbox Midtrans lalu coba lagi.';
            }
        }

        return view('payment.show', [
            'order' => $order->fresh(['items.product', 'user']),
            'midtransClientKey' => $this->midtrans->clientKey(),
            'snapScriptUrl' => $this->midtrans->snapScriptUrl(),
            'midtransError' => $midtransError,
        ]);
    }

    public function sync(Order $order)
    {
        $this->authorizeOrderOwner($order);

        try {
            $order = $this->midtrans->ensureSnapTransaction($order);
            $status = $this->midtrans->fetchStatus($order);
            $order = $this->midtrans->applyTransactionStatus($order, $status);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Status pembayaran belum bisa disinkronkan dari Midtrans.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order)->with('success', 'Pembayaran berhasil dikonfirmasi dari Midtrans.');
        }

        return back()->with('success', 'Status pembayaran berhasil diperbarui dari Midtrans.');
    }

    public function notification(Request $request)
    {
        $payload = $request->all();

        if (! $this->midtrans->isValidSignature($payload)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $order = Order::where('midtrans_order_id', $payload['order_id'] ?? null)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $this->midtrans->applyTransactionStatus($order, $payload);

        return response()->json(['message' => 'Notification processed.']);
    }

    public function repay(Order $order)
    {
        $this->authorizeOrderOwner($order);

        if ($order->payment_status !== 'expired') {
            return back()->with('error', 'Hanya pesanan kedaluwarsa yang bisa dibayar ulang.');
        }

        $newMidtransOrderId = $order->displayCode() . '-' . uniqid();

        $order->update([
            'payment_status' => 'pending',
            'midtrans_order_id' => $newMidtransOrderId,
            'midtrans_snap_token' => null,
            'midtrans_redirect_url' => null,
            'midtrans_transaction_id' => null,
            'midtrans_payment_type' => null,
            'midtrans_transaction_status' => null,
            'midtrans_fraud_status' => null,
            'midtrans_status_code' => null,
            'midtrans_status_message' => null,
            'midtrans_payload' => null,
        ]);

        return back()->with('success', 'Pesanan siap dibayar ulang. Silakan lanjutkan pembayaran.');
    }

    public function cancel(Order $order)
    {
        $this->authorizeOrderOwner($order);

        if ($order->payment_status !== 'expired') {
            return back()->with('error', 'Hanya pesanan kedaluwarsa yang bisa dibatalkan.');
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

            foreach ($order->items as $item) {
                if ($item->productVariant) {
                    $item->productVariant->increment('stock', $item->quantity);
                    $this->variantService->syncProductStock($item->product);
                } else {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        });

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan berhasil dibatalkan dan stok telah dikembalikan.');
    }

    private function authorizeOrderOwner(Order $order): void
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function canStartPayment(Order $order): bool
    {
        return in_array($order->payment_status, [null, 'pending'], true);
    }
}

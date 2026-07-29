<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Message;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user', 'items.product', 'items.productVariant'])->latest()->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'items.productVariant', 'items.review', 'orderReturn', 'returns']);
        return view('admin.orders.show', compact('order'));
    }

    public function confirm(Order $order)
    {
        $order->update(['status' => 'confirmed']);

        // Kirim chat otomatis ke user
        Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $order->user_id,
            'message' => 'Pesanan Anda sudah dikonfirmasi. Dalam proses pengemasan. Setelah pengemasan, kami akan konfirmasi untuk pengiriman.',
        ]);

        return back()->with('success', 'Pesanan dikonfirmasi dan notifikasi dikirim ke user.');
    }

    public function ship(Order $order)
    {
        $order->update(['status' => 'shipped']);
        return back()->with('success', 'Pesanan dikirim.');
    }

    public function receive(Order $order)
    {
        $order->update(['status' => 'received']);
        return back()->with('success', 'Pesanan diterima.');
    }

    public function cancel(Order $order)
    {
        if (in_array($order->status, ['shipped', 'received', 'cancelled'], true)) {
            return back()->with('error', 'Pesanan yang sudah dikirim, diterima, atau dibatalkan tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($order) {
            if ($order->payment_status === 'paid') {
                $order->loadMissing(['items.product', 'items.productVariant']);
                $variantService = app(ProductVariantService::class);
                foreach ($order->items as $item) {
                    if ($item->productVariant) {
                        $item->productVariant->increment('stock', $item->quantity);
                        $variantService->syncProductStock($item->product);
                    } elseif ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
            }

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

            Message::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $order->user_id,
                'message' => 'Pesanan Anda #' . $order->id . ' (' . $order->displayCode() . ') telah dibatalkan oleh Admin.',
            ]);
        });

        return back()->with('success', 'Pesanan berhasil dibatalkan dan notifikasi dikirim ke pelanggan.');
    }
}

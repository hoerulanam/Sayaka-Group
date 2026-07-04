<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user', 'items.product', 'items.productVariant'])->latest()->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'items.productVariant', 'items.review']);
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
}

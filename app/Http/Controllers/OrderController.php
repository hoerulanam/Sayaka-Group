<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['items.product', 'items.productVariant'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        // Pastikan user hanya bisa melihat pesanan mereka sendiri
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $order->load(['items.product', 'items.productVariant', 'items.review', 'orderReturn', 'returns']);

        return view('orders.show', compact('order'));
    }
}

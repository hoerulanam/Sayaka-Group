<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(Request $request, Order $order, OrderItem $orderItem)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if ((int) $orderItem->order_id !== (int) $order->id) {
            abort(404);
        }

        if ($order->status !== 'received') {
            return back()->with('error', 'Review hanya dapat diberikan setelah pesanan selesai.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $review = ProductReview::updateOrCreate(
            ['order_item_id' => $orderItem->id],
            [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'product_id' => $orderItem->product_id,
                'product_variant_id' => $orderItem->product_variant_id,
                'rating' => $validated['rating'],
                'review' => $validated['review'] ?? null,
            ]
        );

        return back()->with(
            'success',
            $review->wasRecentlyCreated
                ? 'Review produk berhasil dikirim.'
                : 'Review produk berhasil diperbarui.'
        );
    }
}

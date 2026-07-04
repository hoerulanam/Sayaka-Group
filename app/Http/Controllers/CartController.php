<?php

namespace App\Http\Controllers;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index()
    {
        $carts = Cart::where('user_id', Auth::id())
            ->with(['product.brand', 'product.variants', 'productVariant'])
            ->get();

        return view('cart.index', compact('carts'));
    }

    public function add(Request $request, Product $product)
    {
        $purchaseMode = $request->validate([
            'purchase_mode' => ['nullable', 'in:cart,checkout'],
        ])['purchase_mode'] ?? 'cart';

        $product->load('variants');
        $variant = $this->requestedVariant($request, $product);

        $cartQuery = Cart::where('user_id', Auth::id())
            ->where('product_id', $product->id);

        $variant
            ? $cartQuery->where('product_variant_id', $variant->id)
            : $cartQuery->whereNull('product_variant_id');

        $cart = $cartQuery->first();
        $currentQuantity = $cart?->quantity ?? 0;
        $stock = max(0, (int) ($variant?->stock ?? $product->stock ?? 0));
        $displayName = $this->displayName($product, $variant);

        if ($purchaseMode === 'checkout') {
            if ($cart) {
                if ($stock < $cart->quantity) {
                    return back()->with('error', "Stok {$displayName} hanya tersedia {$stock} item.");
                }

                return redirect()->route('checkout.index', ['cart_items' => [$cart->id]]);
            }

            if ($stock < 1) {
                return back()->with('error', "Stok {$displayName} sedang kosong.");
            }

            $cart = Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => 1,
            ]);

            return redirect()->route('checkout.index', ['cart_items' => [$cart->id]]);
        }

        if ($stock <= $currentQuantity) {
            return back()->with('error', "Stok {$displayName} hanya tersedia {$stock} item. 
            Jumlah di keranjang sudah mencapai batas stok.");
        }

        if ($cart) {
            $cart->increment('quantity');
        } else {
            Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => 1,
            ]);
        }

        return back()->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === Auth::id(), 403);

        $cart->load(['product', 'productVariant']);
        $stock = $cart->availableStock();
        $displayName = $cart->displayName();

        if ($stock < 1) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Stok {$displayName} sedang kosong.",
                    'quantity' => $cart->quantity,
                    'stock' => $stock,
                ], 422);
            }

            return back()->with('error', "Stok {$displayName} sedang kosong.");
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'action' => ['nullable', 'in:set,increase,decrease'],
        ]);

        $quantity = (int) $validated['quantity'];
        $action = $validated['action'] ?? 'set';

        if ($action === 'increase') {
            $quantity = $cart->quantity + 1;
        }

        if ($action === 'decrease') {
            $quantity = max(1, $cart->quantity - 1);
        }

        if ($quantity > $stock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Stok {$displayName} hanya tersedia {$stock} item.",
                    'quantity' => $cart->quantity,
                    'stock' => $stock,
                ], 422);
            }

            return back()->with('error', "Stok {$displayName} hanya tersedia {$stock} item.");
        }

        $cart->update(['quantity' => $quantity]);

        if ($request->expectsJson()) {
            $lineTotal = $cart->quantity * $cart->unitPrice();

            return response()->json([
                'message' => 'Jumlah produk di keranjang diperbarui.',
                'quantity' => $cart->quantity,
                'stock' => $stock,
                'line_total' => $lineTotal,
                'formatted_line_total' => 'Rp ' . number_format($lineTotal, 0, ',', '.'),
            ]);
        }

        return back()->with('success', 'Jumlah produk di keranjang diperbarui.');
    }

    public function remove(Cart $cart)
    {
        abort_unless($cart->user_id === Auth::id(), 403);

        $cart->delete();

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }

    private function requestedVariant(Request $request, Product $product): ?ProductVariant
    {
        if (! $product->hasVariants()) {
            return null;
        }

        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
        ]);

        $variant = $product->variants->firstWhere('id', (int) $validated['product_variant_id']);

        if (! $variant) {
            throw ValidationException::withMessages([
                'product_variant_id' => $product->requiresColorSelection()
                    ? 'Warna Cat Sepatu yang dipilih tidak valid.'
                    : 'Varian produk yang dipilih tidak valid.',
            ]);
        }

        return $variant;
    }

    private function displayName(Product $product, ?ProductVariant $variant): string
    {
        $variantLabel = $variant?->label();
        $labels = $variant?->color
            ? array_filter([$product->size, $variantLabel])
            : array_filter([$variantLabel ?? $product->size]);

        return $labels ? "{$product->name} " . implode(' - ', $labels) : $product->name;
    }
}

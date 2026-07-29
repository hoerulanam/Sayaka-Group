<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(private ProductVariantService $variantService)
    {
    }

    public function index(Request $request)
    {
        $selectedCartIds = $this->selectedCartIds($request);

        if (empty($selectedCartIds)) {
            return redirect()->route('cart.index')->with('error', 'Pilih minimal satu produk untuk checkout.');
        }

        $carts = Cart::where('user_id', Auth::id())
            ->whereIn('id', $selectedCartIds)
            ->with(['product.variants', 'productVariant'])
            ->get();

        if ($carts->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Produk yang dipilih tidak ditemukan di keranjang.');
        }

        if ($stockWarning = $this->firstCartStockWarning($carts)) {
            return redirect()->route('cart.index')->with('error', $stockWarning);
        }

        $total = $carts->sum(function ($cart) {
            return $cart->quantity * $cart->unitPrice();
        });

        return view('checkout.index', compact('carts', 'total', 'selectedCartIds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cart_items' => 'required|array|min:1',
            'cart_items.*' => 'integer',
            'payment_method' => 'required|in:midtrans_snap',
            'destination_city' => 'required|in:jakarta,surabaya,bandung,medan,makassar,denpasar,yogyakarta,semarang,palembang,balikpapan',
            'shipping_address' => 'required|string|max:1000',
            'phone' => 'required|string|max:20',
            'shipping_carrier' => 'required|string|max:50',
            'shipping_service' => 'required|string|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
        ]);

        $selectedCartIds = collect($validated['cart_items'])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $carts = Cart::where('user_id', Auth::id())
            ->whereIn('id', $selectedCartIds)
            ->with(['product.variants', 'productVariant'])
            ->get();

        if ($carts->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Pilih minimal satu produk untuk checkout.');
        }

        if ($stockWarning = $this->firstCartStockWarning($carts)) {
            return back()
                ->withInput()
                ->with('error', $stockWarning)
                ->withErrors(['stock' => $stockWarning]);
        }

        $validated['shipping_cost'] = $this->resolveShippingCost(
            $validated['destination_city'],
            $validated['shipping_carrier'],
            $validated['shipping_service']
        );

        if ($validated['shipping_cost'] === null) {
            throw ValidationException::withMessages([
                'shipping_service' => 'Pilihan pengiriman tidak valid.',
            ]);
        }

        // Sync phone to user profile if currently empty
        $user = Auth::user();
        if (empty($user->phone)) {
            $user->update(['phone' => $validated['phone']]);
        }

        $order = DB::transaction(function () use ($carts, $validated) {
            $items = [];
            $productTotal = 0;

            foreach ($carts as $cart) {
                $product = Product::whereKey($cart->product_id)->lockForUpdate()->firstOrFail();
                $variant = $this->lockedCartVariant($cart, $product);
                $stock = max(0, (int) ($variant?->stock ?? $product->stock ?? 0));
                $displayName = $this->displayName($product, $variant);

                if ($stock < $cart->quantity) {
                    throw ValidationException::withMessages([
                        'stock' => $this->stockWarningMessage($displayName, $stock, (int) $cart->quantity),
                    ]);
                }

                $price = $variant?->price ?? $product->price ?? 0;
                $productTotal += $cart->quantity * $price;
                $items[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $cart->quantity,
                    'price' => $price,
                ];
            }

            $order = Order::create([
                'user_id' => Auth::id(),
                'total' => $productTotal + $validated['shipping_cost'],
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'shipping_address' => $validated['shipping_address'],
                'phone' => $validated['phone'],
                'shipping_carrier' => $validated['shipping_carrier'],
                'shipping_service' => $validated['shipping_service'],
                'shipping_cost' => $validated['shipping_cost'],
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_variant_id' => $item['variant']?->id,
                    'variant_label' => $this->orderVariantLabel($item['product'], $item['variant']),
                    'selected_color' => $item['variant']?->color,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            Cart::where('user_id', Auth::id())
                ->whereIn('id', $carts->pluck('id'))
                ->delete();

            return $order;
        });

        return redirect()->route('payment.show', $order);
    }

    public function showProduct(Request $request, Product $product)
    {
        $product->load('variants');

        if ($product->availableStock() < 1) {
            return back()->with('error', 'Stok produk habis.');
        }

        $selectedVariant = null;

        if ($product->hasVariants()) {
            $selectedVariant = $product->variants
                ->firstWhere('id', (int) $request->input('product_variant_id'))
                ?? $product->variants->first(fn (ProductVariant $variant) => $variant->stock > 0)
                ?? $product->variants->first();
        }

        return view('checkout.product', compact('product', 'selectedVariant'));
    }

    public function direct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'payment_method' => 'required|in:midtrans_snap',
            'destination_city' => 'required|in:jakarta,surabaya,bandung,medan,makassar,denpasar,yogyakarta,semarang,palembang,balikpapan',
            'shipping_address' => 'required|string|max:1000',
            'phone' => 'required|string|max:20',
            'shipping_carrier' => 'required|string|max:50',
            'shipping_service' => 'required|string|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
        ]);

        $product->load('variants');
        $variant = $this->requestedVariant($product, $validated['product_variant_id'] ?? null);
        $stock = max(0, (int) ($variant?->stock ?? $product->stock ?? 0));
        $displayName = $this->displayName($product, $variant);

        if ((int) $validated['quantity'] > $stock) {
            $message = $this->stockWarningMessage($displayName, $stock, (int) $validated['quantity']);

            return back()
                ->withInput()
                ->with('error', $message)
                ->withErrors(['quantity' => $message]);
        }

        $validated['shipping_cost'] = $this->resolveShippingCost(
            $validated['destination_city'],
            $validated['shipping_carrier'],
            $validated['shipping_service']
        );

        if ($validated['shipping_cost'] === null) {
            throw ValidationException::withMessages([
                'shipping_service' => 'Pilihan pengiriman tidak valid.',
            ]);
        }

        // Sync phone to user profile if currently empty
        $user = Auth::user();
        if (empty($user->phone)) {
            $user->update(['phone' => $validated['phone']]);
        }

        $order = DB::transaction(function () use ($product, $variant, $validated) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $variant = $variant
                ? ProductVariant::whereKey($variant->id)->where('product_id', $product->id)->lockForUpdate()->firstOrFail()
                : null;
            $stock = max(0, (int) ($variant?->stock ?? $product->stock ?? 0));
            $displayName = $this->displayName($product, $variant);

            if ($stock < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => $this->stockWarningMessage($displayName, $stock, (int) $validated['quantity']),
                ]);
            }

            $price = $variant?->price ?? $product->price ?? 0;
            $grandTotal = ($price * $validated['quantity']) + $validated['shipping_cost'];

            $order = Order::create([
                'user_id' => Auth::id(),
                'total' => $grandTotal,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'shipping_address' => $validated['shipping_address'],
                'phone' => $validated['phone'],
                'shipping_carrier' => $validated['shipping_carrier'],
                'shipping_service' => $validated['shipping_service'],
                'shipping_cost' => $validated['shipping_cost'],
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'variant_label' => $this->orderVariantLabel($product, $variant),
                'selected_color' => $variant?->color,
                'quantity' => $validated['quantity'],
                'price' => $price,
            ]);

            return $order;
        });

        return redirect()->route('payment.show', $order);
    }

    private function resolveShippingCost(string $city, string $carrier, string $service): ?int
    {
        $rates = $this->shippingRates()[$city] ?? $this->shippingRates()['default'];

        foreach ($rates[$carrier] ?? [] as $option) {
            if ($option['service'] === $service) {
                return $option['cost'];
            }
        }

        return null;
    }

    private function selectedCartIds(Request $request): array
    {
        return collect($request->input('cart_items', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function firstCartStockWarning($carts): ?string
    {
        foreach ($carts as $cart) {
            if (!$cart->product) {
                return 'Produk di keranjang tidak ditemukan. Hapus produk tersebut sebelum checkout.';
            }

            if ($cart->product->hasVariants() && ! $cart->productVariant) {
                return "Pilih varian untuk {$cart->product->name} sebelum checkout.";
            }

            $stock = $cart->availableStock();
            $quantity = (int) $cart->quantity;

            if ($quantity > $stock) {
                return $this->stockWarningMessage($cart->displayName(), $stock, $quantity);
            }
        }

        return null;
    }

    private function stockWarningMessage(string $productName, int $stock, int $quantity): string
    {
        if ($stock < 1) {
            return "Stok {$productName} sedang kosong. Hapus produk atau pilih produk lain sebelum checkout.";
        }

        return "Stok {$productName} hanya tersedia {$stock} item, sedangkan jumlah yang dipilih {$quantity} item. Kurangi jumlah sebelum checkout.";
    }

    private function requestedVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if (! $product->hasVariants()) {
            return null;
        }

        $variant = $product->variants->firstWhere('id', $variantId);

        if (! $variant) {
            throw ValidationException::withMessages([
                'product_variant_id' => $product->requiresColorSelection()
                    ? 'Pilih warna Cat Sepatu sebelum checkout.'
                    : 'Pilih varian produk sebelum checkout.',
            ]);
        }

        return $variant;
    }

    private function lockedCartVariant(Cart $cart, Product $product): ?ProductVariant
    {
        if (! $cart->product_variant_id) {
            if ($product->variants()->exists()) {
                throw ValidationException::withMessages([
                    'stock' => "Pilih varian untuk {$product->name} sebelum checkout.",
                ]);
            }

            return null;
        }

        return ProductVariant::whereKey($cart->product_variant_id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function displayName(Product $product, ?ProductVariant $variant): string
    {
        $variantLabel = $variant?->label();
        $labels = $variant?->color
            ? array_filter([$product->size, $variantLabel])
            : array_filter([$variantLabel ?? $product->size]);

        return $labels ? "{$product->name} " . implode(' - ', $labels) : $product->name;
    }

    private function orderVariantLabel(Product $product, ?ProductVariant $variant): ?string
    {
        if ($variant?->color) {
            return $product->size;
        }

        return $variant?->label() ?? $product->size;
    }

    private function shippingRates(): array
    {
        return [
            'jakarta' => [
                'JNE' => [
                    ['service' => 'Reguler (REG)', 'cost' => 12000],
                    ['service' => 'Express (YES)', 'cost' => 22000],
                    ['service' => 'Oke (Hemat)', 'cost' => 9000],
                ],
                'J&T' => [
                    ['service' => 'J&T EZ (Reguler)', 'cost' => 11000],
                    ['service' => 'J&T Super (Express)', 'cost' => 24000],
                ],
                'SiCepat' => [
                    ['service' => 'REG (Reguler)', 'cost' => 11500],
                    ['service' => 'GOKIL (Cargo)', 'cost' => 8000],
                ],
                'POS' => [
                    ['service' => 'Kilat Khusus', 'cost' => 10000],
                    ['service' => 'Express Sameday', 'cost' => 25000],
                ],
            ],
            'surabaya' => [
                'JNE' => [
                    ['service' => 'Reguler (REG)', 'cost' => 22000],
                    ['service' => 'Express (YES)', 'cost' => 35000],
                    ['service' => 'Oke (Hemat)', 'cost' => 18000],
                ],
                'J&T' => [
                    ['service' => 'J&T EZ (Reguler)', 'cost' => 21000],
                    ['service' => 'J&T Super (Express)', 'cost' => 38000],
                ],
                'SiCepat' => [
                    ['service' => 'REG (Reguler)', 'cost' => 20000],
                    ['service' => 'GOKIL (Cargo)', 'cost' => 15000],
                ],
                'POS' => [
                    ['service' => 'Kilat Khusus', 'cost' => 19000],
                ],
            ],
            'bandung' => [
                'JNE' => [
                    ['service' => 'Reguler (REG)', 'cost' => 15000],
                    ['service' => 'Express (YES)', 'cost' => 26000],
                ],
                'J&T' => [
                    ['service' => 'J&T EZ (Reguler)', 'cost' => 14000],
                ],
                'SiCepat' => [
                    ['service' => 'REG (Reguler)', 'cost' => 14500],
                ],
                'POS' => [
                    ['service' => 'Kilat Khusus', 'cost' => 13000],
                ],
            ],
            'default' => [
                'JNE' => [
                    ['service' => 'Reguler (REG)', 'cost' => 32000],
                    ['service' => 'Express (YES)', 'cost' => 48000],
                    ['service' => 'Oke (Hemat)', 'cost' => 25000],
                ],
                'J&T' => [
                    ['service' => 'J&T EZ (Reguler)', 'cost' => 30000],
                ],
                'SiCepat' => [
                    ['service' => 'REG (Reguler)', 'cost' => 29000],
                    ['service' => 'GOKIL (Cargo)', 'cost' => 20000],
                ],
                'POS' => [
                    ['service' => 'Kilat Khusus', 'cost' => 26000],
                ],
            ],
        ];
    }
}

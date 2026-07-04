<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\InventoryItem;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(private ProductVariantService $variantService)
    {
    }

    public function index()
    {
        $brands = Brand::with('products.variants')->get();
        return view('admin.products.index', compact('brands'));
    }

    public function brandIndex($brandId)
    {
        $brand = Brand::findOrFail($brandId);
        $products = $brand->products()->with('variants')->get();
        return view('admin.products.brand', compact('brand', 'products'));
    }

    public function create(Request $request)
    {
        $brands = Brand::all();
        $brandId = $request->query('brand');
        return view('admin.products.create', compact('brands', 'brandId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:200',
            'code' => 'nullable|string|max:100',
            'thumbnail' => 'nullable|image|max:2048',
            'price' => 'nullable|numeric',
            'size' => 'nullable|string|max:20',
            'has_variants' => 'nullable|boolean',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.type' => 'nullable|in:size,color',
            'variants.*.value' => 'nullable|string|max:20',
            'variants.*.code' => 'nullable|string|max:100',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variant_prices' => 'nullable|array',
            'variant_prices.30ml' => 'nullable|numeric|min:0',
            'variant_prices.50ml' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = filled($validated['code'] ?? null) ? strtoupper(trim($validated['code'])) : null;
        $brand = Brand::findOrFail($validated['brand_id']);
        $variantPrices = $validated['variant_prices'] ?? [];
        $variantRows = $this->genericVariantsFromRequest($request);
        $usesSizeVariants = $this->variantService->brandUsesSizeVariants($brand);
        $usesColorVariants = strtoupper((string) ($validated['code'] ?? null)) === Product::SHOE_PAINT_CODE;
        $usesGenericVariants = $this->usesGenericVariants($request, $brand, $validated['code'] ?? null);
        unset($validated['variant_prices']);
        unset($validated['variants'], $validated['has_variants']);

        $this->validateSpecialVariants($usesSizeVariants, $usesColorVariants, $variantRows, $variantPrices);
        $this->validateGenericVariants($usesGenericVariants, $variantRows);

        if ($usesSizeVariants) {
            $validated['price'] = $this->variantPriceForLabel($variantRows, $variantPrices, ProductVariantService::SIZES[0]);
            $validated['size'] = null;
        } elseif ($usesGenericVariants) {
            $validated['price'] = $variantRows[0]['price'];
            $validated['size'] = null;
        }

        // Handle thumbnail upload (store in public/images/products)
        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            if (!is_dir(public_path('images/products'))) {
                mkdir(public_path('images/products'), 0755, true);
            }
            $file->move(public_path('images/products'), $filename);
            $validated['thumbnail'] = 'images/products/' . $filename;
        }

        // Generate unique slug
        $base = Str::slug($validated['name']);
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        $validated['slug'] = $slug;

        $product = Product::create($validated);

        if ($usesSizeVariants) {
            $this->variantService->ensureForProduct($product, $variantPrices, $variantRows, true);
        } elseif ($usesColorVariants) {
            $this->variantService->ensureForProduct($product, [], $variantRows, true);
        } elseif ($usesGenericVariants) {
            $this->variantService->syncGenericVariants($product, $variantRows);
        } else {
            $this->variantService->ensureInventoryItems($product);
        }

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $product->load(['brand', 'variants']);
        $brands = Brand::all();
        return view('admin.products.edit', compact('product', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:200',
            'code' => 'nullable|string|max:100',
            'thumbnail' => 'nullable|image|max:2048',
            'price' => 'nullable|numeric',
            'size' => 'nullable|string|max:20',
            'has_variants' => 'nullable|boolean',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.type' => 'nullable|in:size,color',
            'variants.*.value' => 'nullable|string|max:20',
            'variants.*.code' => 'nullable|string|max:100',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variant_prices' => 'nullable|array',
            'variant_prices.30ml' => 'nullable|numeric|min:0',
            'variant_prices.50ml' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = filled($validated['code'] ?? null) ? strtoupper(trim($validated['code'])) : null;
        $brand = Brand::findOrFail($validated['brand_id']);
        $variantPrices = $validated['variant_prices'] ?? [];
        $variantRows = $this->genericVariantsFromRequest($request);
        $usesSizeVariants = $this->variantService->brandUsesSizeVariants($brand);
        $usesColorVariants = strtoupper((string) ($validated['code'] ?? $product->code)) === Product::SHOE_PAINT_CODE;
        $usesGenericVariants = $this->usesGenericVariants($request, $brand, $validated['code'] ?? $product->code);
        unset($validated['variant_prices']);
        unset($validated['variants'], $validated['has_variants']);

        $this->validateSpecialVariants($usesSizeVariants, $usesColorVariants, $variantRows, $variantPrices, $product);
        $this->validateGenericVariants($usesGenericVariants, $variantRows, $product);

        if ($usesSizeVariants) {
            $validated['price'] = $this->variantPriceForLabel($variantRows, $variantPrices, ProductVariantService::SIZES[0], $product);
            $validated['size'] = null;
        } elseif ($usesGenericVariants) {
            $validated['price'] = $variantRows[0]['price'];
            $validated['size'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            // Hapus thumbnail lama jika ada
            if ($product->thumbnail && file_exists(public_path($product->thumbnail))) {
                @unlink(public_path($product->thumbnail));
            }
            $file = $request->file('thumbnail');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            if (!is_dir(public_path('images/products'))) {
                mkdir(public_path('images/products'), 0755, true);
            }
            $file->move(public_path('images/products'), $filename);
            $validated['thumbnail'] = 'images/products/' . $filename;
        }

        // Update slug if name changed
        if ($validated['name'] !== $product->name) {
            $base = Str::slug($validated['name']);
            $slug = $base;
            $i = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $validated['slug'] = $slug;
        }

        $oldBrandId = $product->brand_id;
        $oldName = $product->name;

        $product->update($validated);

        $inventoryItems = InventoryItem::where('brand_id', $oldBrandId)
            ->where('name', $oldName)
            ->get();

        foreach ($inventoryItems as $inventoryItem) {
            $inventoryItem->update([
                'brand_id' => $product->brand_id,
                'name' => $product->name,
                'size' => $inventoryItem->color && $product->requiresColorSelection() ? $product->size : $inventoryItem->size,
            ]);
        }

        if ($usesSizeVariants) {
            $this->variantService->ensureForProduct($product, $variantPrices, $variantRows, true);
        } elseif ($usesColorVariants) {
            $this->variantService->ensureForProduct($product, [], $variantRows, true);
        } elseif ($usesGenericVariants) {
            $this->variantService->syncGenericVariants($product, $variantRows);
        } else {
            $product->variants()
                ->get()
                ->each(function ($variant) {
                    $inventoryStock = InventoryItem::where('product_variant_id', $variant->id)->sum('stock');

                    if ((int) $variant->stock < 1 && (int) $inventoryStock < 1) {
                        $variant->delete();
                    }
                });
            $this->variantService->ensureInventoryItems($product);
        }

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function validateVariantPrices(Brand $brand, array $variantPrices): void
    {
        if (! $this->variantService->brandUsesSizeVariants($brand)) {
            return;
        }

        foreach (ProductVariantService::SIZES as $size) {
            if (! isset($variantPrices[$size]) || $variantPrices[$size] === '') {
                throw ValidationException::withMessages([
                    "variant_prices.{$size}" => "Harga parfum ukuran {$size} wajib diisi.",
                ]);
            }
        }
    }

    private function validateSpecialVariants(
        bool $usesSizeVariants,
        bool $usesColorVariants,
        array $variants,
        array $variantPrices,
        ?Product $product = null
    ): void {
        if (! $usesSizeVariants && ! $usesColorVariants) {
            return;
        }

        if ($usesSizeVariants && empty($variants) && ! $product) {
            $brand = $product?->brand ?? new Brand(['name' => 'SummerLabs']);
            $this->validateVariantPrices($brand, $variantPrices);
        }

        $expectedType = $usesColorVariants ? 'color' : 'size';
        $expectedLabel = $usesColorVariants ? 'warna' : 'size';
        $labels = [];

        foreach ($variants as $index => $variant) {
            if (($variant['type'] ?? '') !== $expectedType) {
                throw ValidationException::withMessages([
                    "variants.{$index}.type" => "Tipe varian {$expectedLabel} wajib dipilih.",
                ]);
            }

            if ($variant['value'] === '') {
                throw ValidationException::withMessages([
                    "variants.{$index}.value" => 'Nilai varian wajib diisi.',
                ]);
            }

            if ($usesSizeVariants && ($variant['price'] === null || $variant['price'] === '')) {
                throw ValidationException::withMessages([
                    "variants.{$index}.price" => 'Harga varian wajib diisi.',
                ]);
            }

            $key = strtolower($variant['value']);

            if (isset($labels[$key])) {
                throw ValidationException::withMessages([
                    "variants.{$index}.value" => 'Varian dengan nilai yang sama tidak boleh duplikat.',
                ]);
            }

            if ($product) {
                $variantId = $variant['id'] ? (int) $variant['id'] : null;

                if ($variantId && ! $product->variants()->whereKey($variantId)->exists()) {
                    throw ValidationException::withMessages([
                        "variants.{$index}.value" => 'Varian yang dipilih tidak valid untuk produk ini.',
                    ]);
                }

                $column = $usesColorVariants ? 'color' : 'size';
                $conflictQuery = $product->variants()->where($column, $variant['value']);

                if ($variantId) {
                    $conflictQuery->where('id', '!=', $variantId);
                }

                if ($variantId && $conflictQuery->exists()) {
                    throw ValidationException::withMessages([
                        "variants.{$index}.value" => 'Varian dengan nilai yang sama sudah ada.',
                    ]);
                }
            }

            $labels[$key] = true;
        }

        if ($usesSizeVariants) {
            foreach (ProductVariantService::SIZES as $size) {
                $price = $this->variantPriceForLabel($variants, $variantPrices, $size, $product);

                if ($price === null || $price === '') {
                    throw ValidationException::withMessages([
                        "variant_prices.{$size}" => "Harga parfum ukuran {$size} wajib diisi.",
                    ]);
                }
            }
        }
    }

    private function usesGenericVariants(Request $request, Brand $brand, ?string $productCode): bool
    {
        return $request->boolean('has_variants')
            && ! $this->variantService->brandUsesSizeVariants($brand)
            && strtoupper((string) $productCode) !== Product::SHOE_PAINT_CODE;
    }

    private function genericVariantsFromRequest(Request $request): array
    {
        return collect($request->input('variants', []))
            ->map(function (array $variant) {
                return [
                    'id' => $variant['id'] ?? null,
                    'type' => $variant['type'] ?? '',
                    'value' => trim((string) ($variant['value'] ?? '')),
                    'code' => filled($variant['code'] ?? null) ? strtoupper(trim((string) $variant['code'])) : '',
                    'price' => $variant['price'] ?? null,
                ];
            })
            ->filter(fn (array $variant) => $variant['value'] !== '' || $variant['code'] !== '' || $variant['price'] !== null)
            ->values()
            ->all();
    }

    private function variantPriceForLabel(array $variants, array $variantPrices, string $label, ?Product $product = null)
    {
        $key = strtolower($label);

        foreach ($variants as $variant) {
            if (strtolower((string) ($variant['value'] ?? '')) !== $key) {
                continue;
            }

            if (($variant['price'] ?? null) !== null && $variant['price'] !== '') {
                return $variant['price'];
            }
        }

        if (array_key_exists($label, $variantPrices) && $variantPrices[$label] !== '') {
            return $variantPrices[$label];
        }

        if ($product) {
            return $product->variants()->where('size', $label)->value('price');
        }

        return null;
    }

    private function validateGenericVariants(bool $usesGenericVariants, array $variants, ?Product $product = null): void
    {
        if (! $usesGenericVariants) {
            return;
        }

        if (empty($variants)) {
            throw ValidationException::withMessages([
                'variants' => 'Tambahkan minimal satu varian produk.',
            ]);
        }

        $labels = [];

        foreach ($variants as $index => $variant) {
            if (! in_array($variant['type'], ['size', 'color'], true)) {
                throw ValidationException::withMessages([
                    "variants.{$index}.type" => 'Tipe varian wajib dipilih.',
                ]);
            }

            if ($variant['value'] === '') {
                throw ValidationException::withMessages([
                    "variants.{$index}.value" => 'Nilai varian wajib diisi.',
                ]);
            }

            if ($variant['price'] === null || $variant['price'] === '') {
                throw ValidationException::withMessages([
                    "variants.{$index}.price" => 'Harga varian wajib diisi.',
                ]);
            }

            $key = $variant['type'] . '|' . strtolower($variant['value']);

            if (isset($labels[$key])) {
                throw ValidationException::withMessages([
                    "variants.{$index}.value" => 'Varian dengan tipe dan nilai yang sama tidak boleh duplikat.',
                ]);
            }

            if ($product) {
                $variantId = $variant['id'] ? (int) $variant['id'] : null;

                if ($variantId && ! $product->variants()->whereKey($variantId)->exists()) {
                    throw ValidationException::withMessages([
                        "variants.{$index}.value" => 'Varian yang dipilih tidak valid untuk produk ini.',
                    ]);
                }

                $column = $variant['type'] === 'color' ? 'color' : 'size';
                $conflictQuery = $product->variants()->where($column, $variant['value']);

                if ($variantId) {
                    $conflictQuery->where('id', '!=', $variantId);
                }

                if ($conflictQuery->exists()) {
                    throw ValidationException::withMessages([
                        "variants.{$index}.value" => 'Varian dengan tipe dan nilai yang sama sudah ada.',
                    ]);
                }
            }

            $labels[$key] = true;
        }
    }
}

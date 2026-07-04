<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductVariantService
{
    public const SIZES = ['30ml', '50ml'];

    public function brandUsesSizeVariants(?Brand $brand): bool
    {
        return $brand && strcasecmp($brand->name, 'SummerLabs') === 0;
    }

    public function productUsesSizeVariants(Product $product): bool
    {
        return $product->relationLoaded('variants')
            ? $product->variants->isNotEmpty()
            : $product->variants()->exists();
    }

    public function ensureForProduct(Product $product, array $prices = [], array $variants = [], bool $pruneMissing = false): Collection
    {
        if ($product->requiresColorSelection()) {
            return $this->syncSpecialVariants(
                $product,
                'color',
                Product::SHOE_PAINT_COLORS,
                $variants,
                $pruneMissing
            );
        }

        return $this->syncSpecialVariants(
            $product,
            'size',
            self::SIZES,
            $variants,
            $pruneMissing,
            $prices
        );
    }

    public function syncGenericVariants(Product $product, array $variants): Collection
    {
        $keptVariantIds = collect();

        foreach ($variants as $variantData) {
            $type = $variantData['type'] ?? 'size';
            $label = trim((string) ($variantData['value'] ?? ''));

            if ($label === '') {
                continue;
            }

            $variant = null;
            $variantId = $variantData['id'] ?? null;

            if ($variantId) {
                $variant = $product->variants()
                    ->whereKey($variantId)
                    ->first();
            }

            if (! $variant) {
                $variant = ProductVariant::firstOrNew($type === 'color'
                    ? ['product_id' => $product->id, 'color' => $label]
                    : ['product_id' => $product->id, 'size' => $label]);
            }

            $variant->name = $label;
            $variant->size = $type === 'size' ? $label : null;
            $variant->color = $type === 'color' ? $label : null;
            $variant->code = filled($variantData['code'] ?? null)
                ? trim((string) $variantData['code'])
                : $this->variantCode($product, $label);
            $variant->price = $variantData['price'] ?? $product->price;

            if (! $variant->exists) {
                $variant->stock = 0;
            }

            $variant->save();
            $keptVariantIds->push($variant->id);
        }

        $staleVariants = $product->variants()
            ->when($keptVariantIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $keptVariantIds))
            ->get();

        foreach ($staleVariants as $staleVariant) {
            $inventoryStock = InventoryItem::where('product_variant_id', $staleVariant->id)->sum('stock');

            if ((int) $staleVariant->stock < 1 && (int) $inventoryStock < 1) {
                $staleVariant->delete();
            }
        }

        return $this->finishVariants($product);
    }

    public function ensureInventoryItems(Product $product): void
    {
        $product->loadMissing('variants');

        if ($product->variants->isEmpty()) {
            $item = InventoryItem::firstOrNew([
                'product_id' => $product->id,
                'product_variant_id' => null,
            ]);

            if (! $item->exists) {
                $legacyItem = InventoryItem::where('brand_id', $product->brand_id)
                    ->where('name', $product->name)
                    ->where('code', $product->code)
                    ->whereNull('product_variant_id')
                    ->first();

                if ($legacyItem) {
                    $item = $legacyItem;
                }
            }

            $item->fill([
                'product_id' => $product->id,
                'product_variant_id' => null,
                'brand_id' => $product->brand_id,
                'name' => $product->name,
                'code' => $product->code,
            ]);

            $item->size = $product->size;
            $item->color = null;

            if (! $item->exists) {
                $item->stock = 0;
            }

            $item->save();

            return;
        }

        foreach ($product->variants as $variant) {
            $item = InventoryItem::firstOrNew([
                'product_variant_id' => $variant->id,
            ]);

            if (! $item->exists) {
                $legacyQuery = InventoryItem::where('brand_id', $product->brand_id)
                    ->where('name', $product->name)
                    ->where('code', $variant->code)
                    ->whereNull('product_variant_id');

                if ($variant->size) {
                    $legacyQuery->where('size', $variant->size);
                }

                if ($variant->color) {
                    $legacyQuery->where('color', $variant->color);
                }

                $legacyItem = $legacyQuery->first();

                if ($legacyItem) {
                    $item = $legacyItem;
                }
            }

            $item->fill([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'brand_id' => $product->brand_id,
                'name' => $product->name,
                'code' => $variant->code,
                'size' => $variant->size ?? ($variant->color && $product->requiresColorSelection() ? $product->size : null),
                'color' => $variant->color,
            ]);

            if (! $item->exists) {
                $item->stock = 0;
            }

            $item->save();
        }
    }

    public function syncProductStock(Product $product): void
    {
        if (! $product->variants()->exists()) {
            return;
        }

        $product->forceFill([
            'price' => $product->variants()->whereNotNull('price')->min('price') ?? $product->price,
            'stock' => (int) $product->variants()->sum('stock'),
        ])->save();
    }

    public function variantCode(Product $product, string $label): string
    {
        $baseCode = $product->code ?: strtoupper(Str::slug($product->name, '-'));
        $variantCode = strtoupper(Str::slug($label, '-'));

        return $variantCode ? $baseCode . '-' . $variantCode : $baseCode;
    }

    private function syncSpecialVariants(
        Product $product,
        string $type,
        array $defaultLabels,
        array $variants = [],
        bool $pruneMissing = false,
        array $defaultPrices = []
    ): Collection {
        $column = $type === 'color' ? 'color' : 'size';
        $keptVariantIds = collect();
        $rowsByLabel = collect($variants)
            ->filter(fn (array $variant) => ($variant['type'] ?? $type) === $type)
            ->keyBy(fn (array $variant) => $this->variantKey((string) ($variant['value'] ?? '')));

        $rows = collect($defaultLabels)
            ->map(function (string $label) use ($rowsByLabel, $defaultPrices, $type) {
                $row = $rowsByLabel->get($this->variantKey($label), [
                    'type' => $type,
                    'value' => $label,
                    'code' => '',
                    'price' => null,
                ]);

                $row['type'] = $type;
                $row['value'] = $label;

                if (($row['price'] ?? null) === null || $row['price'] === '') {
                    $row['price'] = $defaultPrices[$label] ?? null;
                }

                return $row;
            });

        $defaultKeys = collect($defaultLabels)
            ->map(fn (string $label) => $this->variantKey($label))
            ->all();

        $extraRows = collect($variants)
            ->filter(fn (array $variant) => ($variant['type'] ?? $type) === $type)
            ->reject(fn (array $variant) => in_array($this->variantKey((string) ($variant['value'] ?? '')), $defaultKeys, true))
            ->values();

        $rows = $rows->concat($extraRows);

        foreach ($rows as $variantData) {
            $label = trim((string) ($variantData['value'] ?? ''));

            if ($label === '') {
                continue;
            }

            $variant = null;
            $variantId = $variantData['id'] ?? null;

            if ($variantId) {
                $variant = $product->variants()
                    ->whereKey($variantId)
                    ->first();
            }

            if (! $variant) {
                $variant = ProductVariant::firstOrNew([
                    'product_id' => $product->id,
                    $column => $label,
                ]);
            }

            $variant->name = $label;
            $variant->size = $type === 'size' ? $label : null;
            $variant->color = $type === 'color' ? $label : null;
            $variant->code = filled($variantData['code'] ?? null)
                ? trim((string) $variantData['code'])
                : ($type === 'color' && $product->requiresColorSelection()
                    ? Product::shoePaintVariantCode($label)
                    : $this->variantCode($product, $label));

            if (($variantData['price'] ?? null) !== null && $variantData['price'] !== '') {
                $variant->price = $variantData['price'];
            } elseif (! $variant->exists) {
                $variant->price = $product->price;
            }

            if (! $variant->exists) {
                $variant->stock = 0;
            }

            $variant->save();
            $keptVariantIds->push($variant->id);
        }

        if ($pruneMissing) {
            $this->deletePrunableVariants($product, $keptVariantIds, $defaultLabels);
        }

        return $this->finishVariants($product);
    }

    private function deletePrunableVariants(Product $product, Collection $keptVariantIds, array $protectedLabels = []): void
    {
        $protectedKeys = collect($protectedLabels)
            ->map(fn (string $label) => $this->variantKey($label))
            ->all();

        $staleVariants = $product->variants()
            ->when($keptVariantIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $keptVariantIds))
            ->get();

        foreach ($staleVariants as $staleVariant) {
            if (in_array($this->variantKey((string) $staleVariant->label()), $protectedKeys, true)) {
                continue;
            }

            $inventoryStock = InventoryItem::where('product_variant_id', $staleVariant->id)->sum('stock');

            if ((int) $staleVariant->stock < 1 && (int) $inventoryStock < 1) {
                $staleVariant->delete();
            }
        }
    }

    private function variantKey(string $label): string
    {
        return strtolower(trim($label));
    }

    private function finishVariants(Product $product): Collection
    {
        $product->load('variants');
        $this->ensureInventoryItems($product);
        $this->syncProductStock($product);

        return $product->variants;
    }
}

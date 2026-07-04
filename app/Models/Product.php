<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    public const SHOE_PAINT_CODE = 'SSC-CAT-002';

    public const SHOE_PAINT_COLORS = ['Hitam', 'Putih', 'Cokelat', 'Merah', 'Biru'];

    protected $fillable = ['brand_id', 'name', 'code', 'size', 'slug', 'description', 'thumbnail', 'price', 'stock'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('name')
            ->orderBy('size')
            ->orderBy('color');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function scopeWithReviewSummary($query)
    {
        return $query->withAvg('reviews', 'rating')->withCount('reviews');
    }

    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->isNotEmpty()
            : $this->variants()->exists();
    }

    public function availableStock(): int
    {
        if (! $this->hasVariants()) {
            return max(0, (int) ($this->stock ?? 0));
        }

        return (int) ($this->relationLoaded('variants')
            ? $this->variants->sum('stock')
            : $this->variants()->sum('stock'));
    }

    public function startingPrice(): float
    {
        if (! $this->hasVariants()) {
            return (float) ($this->price ?? 0);
        }

        return (float) ($this->relationLoaded('variants')
            ? $this->variants->whereNotNull('price')->min('price')
            : $this->variants()->whereNotNull('price')->min('price'));
    }

    public function requiresColorSelection(): bool
    {
        return $this->code === self::SHOE_PAINT_CODE;
    }

    public function selectableColors(): array
    {
        if (! $this->requiresColorSelection()) {
            return [];
        }

        $variantColors = $this->relationLoaded('variants')
            ? $this->variants->pluck('color')->filter()->all()
            : $this->variants()->whereNotNull('color')->pluck('color')->filter()->all();

        return array_values(array_unique(array_merge(self::SHOE_PAINT_COLORS, $variantColors)));
    }

    public static function isShoePaintInventoryCode(?string $code): bool
    {
        return $code === self::SHOE_PAINT_CODE
            || str_starts_with((string) $code, self::SHOE_PAINT_CODE . '-');
    }

    public static function shoePaintVariantCode(string $color): string
    {
        return self::SHOE_PAINT_CODE . '-' . strtoupper(Str::slug($color, '-'));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id', 'product_id', 'product_variant_id', 'quantity'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function unitPrice(): float
    {
        return (float) ($this->productVariant?->price ?? $this->product?->price ?? 0);
    }

    public function availableStock(): int
    {
        return max(0, (int) ($this->productVariant?->stock ?? $this->product?->stock ?? 0));
    }

    public function displayName(): string
    {
        $name = $this->product?->name ?? 'Produk';
        $variantLabel = $this->productVariant?->label();
        $labels = $this->productVariant?->color
            ? array_filter([$this->product?->size, $variantLabel])
            : array_filter([$variantLabel ?? $this->product?->size]);

        return $labels ? "{$name} " . implode(' - ', $labels) : $name;
    }
}

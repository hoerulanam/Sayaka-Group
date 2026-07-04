<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['product_id', 'product_variant_id', 'name', 'code', 'size', 'color', 'brand_id', 'stock'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function incomingInventories()
    {
        return $this->hasMany(IncomingInventory::class, 'inventory_item_id');
    }

    public function variantLabel(): ?string
    {
        return $this->productVariant?->label() ?? $this->color ?? $this->size;
    }

    public function variantAttributeName(): string
    {
        if ($this->productVariant) {
            return $this->productVariant->attributeName();
        }

        if ($this->color) {
            return 'Warna';
        }

        if ($this->size) {
            return 'Ukuran';
        }

        return 'Varian';
    }
}

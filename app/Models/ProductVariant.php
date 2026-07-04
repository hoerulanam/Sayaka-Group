<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'size', 'color', 'code', 'price', 'stock'];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function label(): ?string
    {
        return $this->color ?? $this->size ?? $this->name;
    }

    public function attributeName(): string
    {
        if ($this->color) {
            return 'Warna';
        }

        if ($this->size) {
            return 'Ukuran';
        }

        return 'Ukuran';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brands';
    protected $fillable = ['name', 'logo', 'description'];

    /**
     * Relasi: Brand memiliki banyak Produk
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function usesSizeVariants(): bool
    {
        return strcasecmp($this->name, 'SummerLabs') === 0;
    }
}

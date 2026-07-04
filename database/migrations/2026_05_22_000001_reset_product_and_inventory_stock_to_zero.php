<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'stock')) {
            DB::table('products')->update(['stock' => 0]);
        }

        if (! Schema::hasTable('inventory_items')) {
            return;
        }

        if (Schema::hasTable('products')) {
            $now = now();
            $products = DB::table('products')
                ->select('brand_id', 'name', 'code')
                ->get();

            foreach ($products as $product) {
                $query = DB::table('inventory_items')
                    ->where('brand_id', $product->brand_id)
                    ->where('name', $product->name);

                if ($product->code) {
                    $query->where('code', $product->code);
                } else {
                    $query->whereNull('code');
                }

                if (! $query->exists()) {
                    DB::table('inventory_items')->insert([
                        'brand_id' => $product->brand_id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'stock' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (Schema::hasColumn('inventory_items', 'stock')) {
            DB::table('inventory_items')->update(['stock' => 0]);
        }
    }

    public function down(): void
    {
        // Nilai stok lama tidak bisa dipulihkan dengan aman tanpa riwayat sebelumnya.
    }
};

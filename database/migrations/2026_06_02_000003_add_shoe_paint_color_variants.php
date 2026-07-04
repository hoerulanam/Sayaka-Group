<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('size', 20)->nullable()->change();
            $table->string('color', 20)->nullable()->after('size');
            $table->unique(['product_id', 'color']);
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('color', 20)->nullable()->after('size');
        });

        $product = DB::table('products')
            ->where('code', Product::SHOE_PAINT_CODE)
            ->first();

        if (! $product) {
            return;
        }

        $now = now();

        DB::table('products')
            ->where('id', $product->id)
            ->update(['stock' => 0, 'updated_at' => $now]);

        DB::table('carts')
            ->where('product_id', $product->id)
            ->delete();

        DB::table('inventory_items')
            ->where('brand_id', $product->brand_id)
            ->where('code', Product::SHOE_PAINT_CODE)
            ->whereNull('color')
            ->update(['stock' => 0, 'updated_at' => $now]);

        foreach (Product::SHOE_PAINT_COLORS as $color) {
            $code = Product::shoePaintVariantCode($color);

            DB::table('product_variants')->updateOrInsert(
                ['product_id' => $product->id, 'color' => $color],
                [
                    'size' => null,
                    'code' => $code,
                    'price' => $product->price,
                    'stock' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('inventory_items')->updateOrInsert(
                [
                    'brand_id' => $product->brand_id,
                    'code' => $code,
                    'color' => $color,
                ],
                [
                    'name' => $product->name,
                    'size' => $product->size,
                    'stock' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('inventory_items')
            ->whereNotNull('color')
            ->delete();

        DB::table('product_variants')
            ->whereNotNull('color')
            ->delete();

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('color');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'color']);
            $table->dropColumn('color');
            $table->string('size', 20)->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'name')) {
                $table->string('name', 100)->nullable()->after('product_id');
            }
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_items', 'product_id')) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('products')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('inventory_items', 'product_variant_id')) {
                $table->foreignId('product_variant_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->nullOnDelete();
            }
        });

        DB::table('product_variants')
            ->whereNull('name')
            ->chunkById(100, function ($variants) {
                foreach ($variants as $variant) {
                    DB::table('product_variants')
                        ->where('id', $variant->id)
                        ->update([
                            'name' => $variant->color ?: $variant->size,
                            'updated_at' => now(),
                        ]);
                }
            });

        DB::table('inventory_items')
            ->chunkById(100, function ($items) {
                foreach ($items as $item) {
                    $product = DB::table('products')
                        ->where('brand_id', $item->brand_id)
                        ->where('name', $item->name)
                        ->first();

                    if (! $product && $item->code) {
                        $product = DB::table('products')
                            ->where('brand_id', $item->brand_id)
                            ->where('code', $item->code)
                            ->first();
                    }

                    if (! $product) {
                        continue;
                    }

                    $variantQuery = DB::table('product_variants')
                        ->where('product_id', $product->id);

                    $variant = null;

                    if ($item->color) {
                        $variant = (clone $variantQuery)->where('color', $item->color)->first();
                    }

                    if (! $variant && $item->size) {
                        $variant = (clone $variantQuery)->where('size', $item->size)->first();
                    }

                    if (! $variant && $item->code) {
                        $variant = (clone $variantQuery)->where('code', $item->code)->first();
                    }

                    DB::table('inventory_items')
                        ->where('id', $item->id)
                        ->update([
                            'product_id' => $product->id,
                            'product_variant_id' => $variant?->id,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'product_variant_id')) {
                $table->dropConstrainedForeignId('product_variant_id');
            }

            if (Schema::hasColumn('inventory_items', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};

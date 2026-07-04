<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 20);
            $table->string('code')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'size']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->string('variant_label', 50)->nullable()->after('product_variant_id');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('size', 20)->nullable()->after('code');
        });

        $summerLabs = DB::table('brands')
            ->whereRaw('LOWER(name) = ?', ['summerlabs'])
            ->first();

        if (! $summerLabs) {
            return;
        }

        $now = now();
        $products = DB::table('products')
            ->where('brand_id', $summerLabs->id)
            ->get();

        foreach ($products as $product) {
            $baseCode = $product->code ?: strtoupper(Str::slug($product->name, '-'));
            $thirtyCode = $baseCode . '-30ML';
            $fiftyCode = $baseCode . '-50ML';

            $thirtyId = DB::table('product_variants')->insertGetId([
                'product_id' => $product->id,
                'size' => '30ml',
                'code' => $thirtyCode,
                'price' => $product->price,
                'stock' => (int) ($product->stock ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('product_variants')->insert([
                'product_id' => $product->id,
                'size' => '50ml',
                'code' => $fiftyCode,
                'price' => $product->price,
                'stock' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('carts')
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->update(['product_variant_id' => $thirtyId]);

            $inventoryItem = DB::table('inventory_items')
                ->where('brand_id', $summerLabs->id)
                ->where('name', $product->name)
                ->whereNull('size')
                ->first();

            if ($inventoryItem) {
                DB::table('inventory_items')
                    ->where('id', $inventoryItem->id)
                    ->update([
                        'code' => $thirtyCode,
                        'size' => '30ml',
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('inventory_items')->insert([
                    'brand_id' => $summerLabs->id,
                    'name' => $product->name,
                    'code' => $thirtyCode,
                    'size' => '30ml',
                    'stock' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $hasFiftyInventory = DB::table('inventory_items')
                ->where('brand_id', $summerLabs->id)
                ->where('name', $product->name)
                ->where('size', '50ml')
                ->exists();

            if (! $hasFiftyInventory) {
                DB::table('inventory_items')->insert([
                    'brand_id' => $summerLabs->id,
                    'name' => $product->name,
                    'code' => $fiftyCode,
                    'size' => '50ml',
                    'stock' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn('variant_label');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });

        Schema::dropIfExists('product_variants');
    }
};

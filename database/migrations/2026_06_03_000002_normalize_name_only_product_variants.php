<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variants', 'name')) {
            return;
        }

        DB::table('product_variants')
            ->whereNull('size')
            ->whereNull('color')
            ->whereNotNull('name')
            ->chunkById(100, function ($variants) {
                foreach ($variants as $variant) {
                    $value = substr((string) $variant->name, 0, 20);

                    if ($value === '') {
                        continue;
                    }

                    DB::table('product_variants')
                        ->where('id', $variant->id)
                        ->update([
                            'name' => $value,
                            'size' => $value,
                            'updated_at' => now(),
                        ]);

                    DB::table('inventory_items')
                        ->where('product_variant_id', $variant->id)
                        ->update([
                            'size' => $value,
                            'color' => null,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Data normalization is intentionally not reversed.
    }
};

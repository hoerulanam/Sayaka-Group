<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('size', 20)->nullable()->after('code');
        });

        $summerShoesCare = DB::table('brands')
            ->whereRaw('LOWER(name) = ?', ['summershoescare'])
            ->first();

        if (! $summerShoesCare) {
            return;
        }

        $now = now();

        DB::table('products')
            ->where('brand_id', $summerShoesCare->id)
            ->where('code', 'SSC-LEM-001')
            ->update([
                'name' => 'Lem Super Kuat',
                'slug' => 'lem-super-kuat',
                'size' => '100ml',
                'updated_at' => $now,
            ]);

        DB::table('inventory_items')
            ->where('brand_id', $summerShoesCare->id)
            ->where('code', 'SSC-LEM-001')
            ->update([
                'name' => 'Lem Super Kuat',
                'size' => '100ml',
                'updated_at' => $now,
            ]);

        DB::table('products')
            ->where('brand_id', $summerShoesCare->id)
            ->where('code', 'SSC-CAT-002')
            ->update([
                'size' => '60ml',
                'updated_at' => $now,
            ]);

        DB::table('inventory_items')
            ->where('brand_id', $summerShoesCare->id)
            ->where('code', 'SSC-CAT-002')
            ->update([
                'size' => '60ml',
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        $summerShoesCare = DB::table('brands')
            ->whereRaw('LOWER(name) = ?', ['summershoescare'])
            ->first();

        if ($summerShoesCare) {
            $now = now();

            DB::table('products')
                ->where('brand_id', $summerShoesCare->id)
                ->where('code', 'SSC-LEM-001')
                ->update([
                    'name' => 'Lem Sepatu',
                    'slug' => 'lem-sepatu',
                    'updated_at' => $now,
                ]);

            DB::table('inventory_items')
                ->where('brand_id', $summerShoesCare->id)
                ->whereIn('code', ['SSC-LEM-001', 'SSC-CAT-002'])
                ->update([
                    'size' => null,
                    'updated_at' => $now,
                ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};

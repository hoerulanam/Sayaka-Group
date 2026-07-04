<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incoming_inventories', function (Blueprint $table) {
            if (Schema::hasColumn('incoming_inventories', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
            if (!Schema::hasColumn('incoming_inventories', 'inventory_item_id')) {
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_inventories', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
            $table->dropColumn('inventory_item_id');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
        });
    }
};

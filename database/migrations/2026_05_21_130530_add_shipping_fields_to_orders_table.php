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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('shipping_address')->nullable()->after('payment_status');
            $table->string('phone')->nullable()->after('shipping_address');
            $table->string('shipping_carrier')->nullable()->after('phone');
            $table->string('shipping_service')->nullable()->after('shipping_carrier');
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('shipping_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_address', 'phone', 'shipping_carrier', 'shipping_service', 'shipping_cost']);
        });
    }
};

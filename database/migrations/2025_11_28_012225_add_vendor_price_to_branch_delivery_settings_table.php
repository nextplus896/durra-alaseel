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
        Schema::table('branch_delivery_settings', function (Blueprint $table) {
            $table->decimal('vendor_price', 28, 8)->default(0)->after('delivery_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_delivery_settings', function (Blueprint $table) {
            $table->dropColumn('vendor_price');
        });
    }
};

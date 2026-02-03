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
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->integer('rental_days')->default(1)->after('slug');
            $table->integer('allowance_km')->default(0)->after('rental_days');
            $table->decimal('balance_deducted', 28, 8)->nullable()->after('paid_from_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->dropColumn(['rental_days', 'allowance_km', 'balance_deducted']);
        });
    }
};

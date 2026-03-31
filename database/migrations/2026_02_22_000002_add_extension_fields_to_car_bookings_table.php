<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->date('return_date')->nullable()->after('rental_days');
            $table->integer('original_rental_days')->nullable()->after('return_date');
            $table->integer('extension_count')->default(0)->after('original_rental_days');
            $table->integer('total_extension_days')->default(0)->after('extension_count');

            $table->index(['car_id', 'return_date', 'status'], 'car_bookings_car_return_status_index');
        });

        // Backfill return_date for existing bookings that have pickup_date and rental_days
        DB::statement("
            UPDATE car_bookings
            SET return_date = DATE_ADD(pickup_date, INTERVAL rental_days DAY),
                original_rental_days = rental_days
            WHERE pickup_date IS NOT NULL
              AND rental_days IS NOT NULL
              AND rental_days > 0
              AND return_date IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->dropIndex('car_bookings_car_return_status_index');
            $table->dropColumn(['return_date', 'original_rental_days', 'extension_count', 'total_extension_days']);
        });
    }
};

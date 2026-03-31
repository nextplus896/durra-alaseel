<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds daily_rate (decimal 12,2) to car_booking_transactions.
     * Only rental and extension rows have a daily_rate; delivery rows remain NULL.
     *
     * Effective daily rate formula: rental_fees ÷ rental_days
     * - rental  rows: amount / b.rental_days  (from parent car_bookings row)
     * - extension rows: copy daily_rate from the sibling rental transaction for
     *                   the same booking, guaranteeing the original rate is preserved
     *                   even if the car price later changes.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('car_booking_transactions', 'daily_rate')) {
            Schema::table('car_booking_transactions', function (Blueprint $table) {
                $table->decimal('daily_rate', 12, 2)->nullable()->after('total');
            });
        }

        // ------------------------------------------------------------------
        // Backfill rental rows
        // daily_rate = booking.amount / booking.rental_days
        // ------------------------------------------------------------------
        DB::statement("
            UPDATE car_booking_transactions t
            INNER JOIN car_bookings b ON b.id = t.car_booking_id
            SET t.daily_rate = CASE
                WHEN b.rental_days > 0
                THEN ROUND(b.amount / b.rental_days, 2)
                ELSE NULL
            END
            WHERE t.type = 'rental'
        ");

        // ------------------------------------------------------------------
        // Backfill extension rows
        // Copy daily_rate from the rental transaction of the same booking.
        // This preserves the original rate regardless of later car-price changes.
        // ------------------------------------------------------------------
        DB::statement("
            UPDATE car_booking_transactions ext_t
            INNER JOIN car_booking_transactions rent_t
                ON  rent_t.car_booking_id = ext_t.car_booking_id
                AND rent_t.type = 'rental'
            SET ext_t.daily_rate = rent_t.daily_rate
            WHERE ext_t.type = 'extension'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};

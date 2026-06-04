<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('car_booking_transactions', 'daily_rate')) {
            Schema::table('car_booking_transactions', function (Blueprint $table) {
                $table->decimal('daily_rate', 12, 2)->nullable()->after('total');
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support UPDATE ... INNER JOIN — use correlated subqueries.
            DB::statement("
                UPDATE car_booking_transactions
                SET daily_rate = (
                    SELECT CASE WHEN b.rental_days > 0 THEN ROUND(CAST(b.amount AS REAL) / b.rental_days, 2) ELSE NULL END
                    FROM car_bookings b
                    WHERE b.id = car_booking_transactions.car_booking_id
                )
                WHERE type = 'rental'
            ");

            DB::statement("
                UPDATE car_booking_transactions
                SET daily_rate = (
                    SELECT rent_t.daily_rate
                    FROM car_booking_transactions rent_t
                    WHERE rent_t.car_booking_id = car_booking_transactions.car_booking_id
                      AND rent_t.type = 'rental'
                    LIMIT 1
                )
                WHERE type = 'extension'
            ");
        } else {
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

            DB::statement("
                UPDATE car_booking_transactions ext_t
                INNER JOIN car_booking_transactions rent_t
                    ON  rent_t.car_booking_id = ext_t.car_booking_id
                    AND rent_t.type = 'rental'
                SET ext_t.daily_rate = rent_t.daily_rate
                WHERE ext_t.type = 'extension'
            ");
        }
    }

    public function down(): void
    {
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};

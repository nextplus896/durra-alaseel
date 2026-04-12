<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copy all extension rows from car_booking_transactions into booking_extensions
        DB::statement("
            INSERT INTO booking_extensions
                (car_booking_id, user_id, extension_days, previous_return_date, new_return_date,
                 daily_rate, rental_fees, tax_percentage, tax_amount, total_cost,
                 status, created_at, updated_at)
            SELECT
                cbt.car_booking_id,
                cb.user_id,
                COALESCE(cbt.extension_days, 0),
                cbt.previous_return_date,
                cbt.new_return_date,
                COALESCE(cbt.daily_rate, 0),
                COALESCE(cbt.amount, 0),
                COALESCE(cbt.tax_percentage, 0),
                COALESCE(cbt.tax_amount, 0),
                COALESCE(cbt.total, 0),
                1,
                cbt.created_at,
                cbt.updated_at
            FROM car_booking_transactions cbt
            INNER JOIN car_bookings cb ON cb.id = cbt.car_booking_id
            WHERE cbt.type = 'extension'
        ");

        // Remove migrated extension rows from car_booking_transactions
        DB::statement("DELETE FROM car_booking_transactions WHERE type = 'extension'");

        // Drop extension-specific columns from car_booking_transactions
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->dropColumn(['extension_days', 'previous_return_date', 'new_return_date']);
        });
    }

    public function down(): void
    {
        // Restore extension-specific columns (data loss on rollback is accepted)
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->unsignedInteger('extension_days')->nullable()->after('type');
            $table->date('previous_return_date')->nullable()->after('extension_days');
            $table->date('new_return_date')->nullable()->after('previous_return_date');
        });
    }
};

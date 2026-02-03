<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('car_bookings') || !Schema::hasColumn('car_bookings', 'distance')) {
            return;
        }

        // Avoid doctrine/dbal dependency by using raw SQL.
        // Keep a sane default for existing logic that assumes a numeric value.
        DB::statement("ALTER TABLE `car_bookings` MODIFY `distance` DECIMAL(28,8) NULL DEFAULT 0");
    }

    public function down(): void
    {
        if (!Schema::hasTable('car_bookings') || !Schema::hasColumn('car_bookings', 'distance')) {
            return;
        }

        DB::statement("UPDATE `car_bookings` SET `distance` = 0 WHERE `distance` IS NULL");
        DB::statement("ALTER TABLE `car_bookings` MODIFY `distance` DECIMAL(28,8) NOT NULL DEFAULT 0");
    }
};

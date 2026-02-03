<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('car_bookings') || !Schema::hasColumn('car_bookings', 'destination')) {
            return;
        }

        // Avoid doctrine/dbal dependency by using raw SQL.
        DB::statement("ALTER TABLE `car_bookings` MODIFY `destination` VARCHAR(255) NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('car_bookings') || !Schema::hasColumn('car_bookings', 'destination')) {
            return;
        }

        // Ensure rollback won't fail due to NULL values.
        DB::statement("UPDATE `car_bookings` SET `destination` = '' WHERE `destination` IS NULL");
        DB::statement("ALTER TABLE `car_bookings` MODIFY `destination` VARCHAR(255) NOT NULL");
    }
};

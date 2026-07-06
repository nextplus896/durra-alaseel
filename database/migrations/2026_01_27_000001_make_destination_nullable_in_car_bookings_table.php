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

        // SQLite does not support MODIFY — use Doctrine DBAL-backed change() instead.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('car_bookings', function ($table) {
                $table->string('destination')->nullable()->change();
            });
            return;
        }

        DB::statement("ALTER TABLE `car_bookings` MODIFY `destination` VARCHAR(255) NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('car_bookings') || !Schema::hasColumn('car_bookings', 'destination')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('car_bookings', function ($table) {
                $table->string('destination')->nullable(false)->change();
            });
            return;
        }

        DB::statement("UPDATE `car_bookings` SET `destination` = '' WHERE `destination` IS NULL");
        DB::statement("ALTER TABLE `car_bookings` MODIFY `destination` VARCHAR(255) NOT NULL");
    }
};

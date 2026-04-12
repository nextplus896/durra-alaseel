<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->dropColumn('original_rental_days');
        });

        // Add DB comment documenting the return_date formula
        DB::statement(
            "ALTER TABLE car_bookings
             MODIFY COLUMN return_date DATE NULL
             COMMENT 'Computed as: pickup_date + pickup_time + rental_days + total_extension_days'"
        );
    }

    public function down(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->integer('original_rental_days')->nullable()->after('rental_days');
        });

        DB::statement(
            "ALTER TABLE car_bookings
             MODIFY COLUMN return_date DATE NULL
             COMMENT ''"
        );
    }
};

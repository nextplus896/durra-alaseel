<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->string('insurance_type')->nullable()->after('total_amount');  // 'daily' or 'deductible'
            $table->decimal('daily_insurance', 28, 8)->default(0)->after('insurance_type');       // per-day rate snapshot
            $table->decimal('insurance_total', 28, 8)->default(0)->after('daily_insurance');      // daily_insurance × rental_days
            $table->decimal('deductible_insurance', 28, 8)->default(0)->after('insurance_total'); // excess liability snapshot
        });
    }

    public function down(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->dropColumn(['insurance_type', 'daily_insurance', 'insurance_total', 'deductible_insurance']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->decimal('daily_insurance', 28, 8)->default(0)->after('allowance_price_per_km');
            $table->decimal('deductible_insurance', 28, 8)->default(0)->after('daily_insurance');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['daily_insurance', 'deductible_insurance']);
        });
    }
};

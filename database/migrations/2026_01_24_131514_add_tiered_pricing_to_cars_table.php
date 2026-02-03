<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->decimal('price_per_day', 28, 8)->default(0)->after('fees');
            $table->decimal('price_per_week', 28, 8)->default(0)->after('price_per_day');
            $table->decimal('price_per_month', 28, 8)->default(0)->after('price_per_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['price_per_day', 'price_per_week', 'price_per_month']);
        });
    }
};

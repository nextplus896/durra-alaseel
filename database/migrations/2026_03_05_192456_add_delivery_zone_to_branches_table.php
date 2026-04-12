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
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('delivery_enabled')->default(false)->after('service_radius_km');
            $table->decimal('delivery_radius_km', 8, 2)->nullable()->default(null)->after('delivery_enabled');
            $table->index('delivery_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['delivery_enabled']);
            $table->dropColumn(['delivery_enabled', 'delivery_radius_km']);
        });
    }
};

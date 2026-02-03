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
        Schema::table('car_bookings', function (Blueprint $table) {
            // Add pickup_location fields for map-based location selection
            $table->decimal('pickup_latitude', 10, 8)->nullable()->after('location');
            $table->decimal('pickup_longitude', 11, 8)->nullable()->after('pickup_latitude');
            $table->string('pickup_address', 500)->nullable()->after('pickup_longitude');

            // Make allowance_km nullable since it's vendor-only now
            $table->integer('allowance_km')->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_latitude', 'pickup_longitude', 'pickup_address']);
        });
    }
};

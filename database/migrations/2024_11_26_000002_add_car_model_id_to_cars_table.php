<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cars', function (Blueprint $table) {
            // Add car_model_id column
            $table->unsignedBigInteger('car_model_id')->nullable()->after('car_type_id');

            // Add foreign key
            $table->foreign('car_model_id')->references('id')->on('car_models')->onDelete('cascade')->onUpdate('cascade');

            // Drop old columns (keeping area for Option A - backward compatibility)
            // Uncomment below if you want to remove these columns completely
            // $table->dropForeign(['car_area_id']);
            // $table->dropColumn(['car_area_id', 'experience', 'car_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropForeign(['car_model_id']);
            $table->dropColumn('car_model_id');
        });
    }
};

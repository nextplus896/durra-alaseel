<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('VAT');
            $table->decimal('percentage', 5, 2)->default(15.00);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('last_edit_by')->nullable();
            $table->timestamps();

            $table->foreign('last_edit_by')->references('id')->on('admins')->onDelete('set null');
        });

        // Insert default tax setting
        DB::table('tax_settings')->insert([
            'name' => 'VAT',
            'percentage' => 15.00,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_settings');
    }
};

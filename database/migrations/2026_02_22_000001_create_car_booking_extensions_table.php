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
        Schema::create('car_booking_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_booking_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('extension_days');
            $table->date('previous_return_date');
            $table->date('new_return_date');
            $table->decimal('rental_fees', 28, 8)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 28, 8)->default(0);
            $table->decimal('total_cost', 28, 8)->default(0);
            $table->unsignedBigInteger('balance_transaction_id')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=approved, 0=pending, 2=cancelled');
            $table->timestamps();

            $table->foreign('car_booking_id')->references('id')->on('car_bookings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('balance_transaction_id')->references('id')->on('balance_transactions')->nullOnDelete();

            $table->index(['car_booking_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_booking_extensions');
    }
};

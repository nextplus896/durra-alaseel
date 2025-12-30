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
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('trx_id')->unique();
            $table->enum('type', ['recharge', 'booking_deduction', 'refund', 'adjustment'])->default('recharge');
            $table->decimal('amount', 28, 8);
            $table->decimal('balance_before', 28, 8);
            $table->decimal('balance_after', 28, 8);
            $table->string('payment_method')->nullable()->comment('e.g., paytabs, wallet, cash');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0: pending, 1: success, 2: failed');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('car_bookings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('balance_transactions');
    }
};

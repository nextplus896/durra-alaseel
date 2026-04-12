<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('invoice_id', 100)->unique()->comment('Moyasar invoice ID');
            $table->string('payment_id', 100)->nullable()->comment('Moyasar payment ID (set after payment)');
            $table->decimal('amount', 28, 8);
            $table->tinyInteger('status')->default(0)->comment('0=pending, 1=paid, 2=failed, 3=refunded');
            $table->string('provider', 50)->default('moyasar');
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};

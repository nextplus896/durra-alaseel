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
        Schema::create('twilio_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_sid')->unique();
            $table->string('account_sid')->nullable();
            $table->string('to', 20);
            $table->string('from', 50)->nullable();
            $table->string('channel', 20)->default('sms'); // sms or whatsapp
            $table->string('status', 50)->nullable(); // queued, sent, delivered, failed, undelivered
            $table->string('direction', 20)->default('outbound');
            $table->text('body')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('price', 10, 6)->nullable();
            $table->string('price_unit', 5)->nullable();
            $table->unsignedBigInteger('verification_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index('created_at');
            $table->foreign('verification_id')->references('id')->on('phone_verifications')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('twilio_messages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add extension-specific columns to transactions
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->unsignedInteger('extension_days')->nullable()->after('car_booking_extension_id');
            $table->date('previous_return_date')->nullable()->after('extension_days');
            $table->date('new_return_date')->nullable()->after('previous_return_date');
        });

        // 2. Drop FK + column — SQLite does not support dropForeign; skip on SQLite
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['car_booking_extension_id']);
            }
            if (Schema::hasColumn('car_booking_transactions', 'car_booking_extension_id')) {
                $table->dropColumn('car_booking_extension_id');
            }
        });

        // 3. Drop the extensions table
        Schema::dropIfExists('car_booking_extensions');
    }

    public function down(): void
    {
        Schema::create('car_booking_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_booking_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('extension_days');
            $table->date('previous_return_date');
            $table->date('new_return_date');
            $table->decimal('rental_fees', 28, 8)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 28, 8)->default(0);
            $table->decimal('total_cost', 28, 8)->default(0);
            $table->unsignedBigInteger('balance_transaction_id')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            $table->foreign('car_booking_id')->references('id')->on('car_bookings')->onDelete('cascade');
        });

        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('car_booking_extension_id')->nullable()->after('car_booking_id');
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('car_booking_extension_id')
                    ->references('id')->on('car_booking_extensions')
                    ->nullOnDelete();
            }
            $table->dropColumn(['extension_days', 'previous_return_date', 'new_return_date']);
        });
    }
};

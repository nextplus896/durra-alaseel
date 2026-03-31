<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Merge car_booking_extensions into car_booking_transactions.
     *
     * - Adds extension_days, previous_return_date, new_return_date to transactions
     *   (nullable — only populated on type=extension rows).
     * - Drops the car_booking_extension_id FK + column from transactions.
     * - Drops the car_booking_extensions table.
     */
    public function up(): void
    {
        // 1. Add the extension-specific columns to transactions
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->unsignedInteger('extension_days')->nullable()->after('car_booking_extension_id');
            $table->date('previous_return_date')->nullable()->after('extension_days');
            $table->date('new_return_date')->nullable()->after('previous_return_date');
        });

        // 2. Drop FK + column for car_booking_extension_id
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->dropForeign(['car_booking_extension_id']);
            $table->dropColumn('car_booking_extension_id');
        });

        // 3. Drop the extensions table (data is empty — tables were truncated)
        Schema::dropIfExists('car_booking_extensions');
    }

    /**
     * Reverse: recreate car_booking_extensions, restore FK column on transactions.
     * Extension-specific column DATA will be lost on rollback.
     */
    public function down(): void
    {
        // Recreate car_booking_extensions
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

        // Restore car_booking_extension_id column on transactions
        Schema::table('car_booking_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('car_booking_extension_id')->nullable()->after('car_booking_id');
            $table->foreign('car_booking_extension_id')
                ->references('id')->on('car_booking_extensions')
                ->nullOnDelete();

            $table->dropColumn(['extension_days', 'previous_return_date', 'new_return_date']);
        });
    }
};

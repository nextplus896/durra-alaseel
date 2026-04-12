<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the car_booking_transactions table and seeds it with data from
     * existing car_bookings and approved car_booking_extensions.
     */
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. Create table
        // ------------------------------------------------------------------
        Schema::create('car_booking_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('car_booking_id');
            $table->unsignedBigInteger('car_booking_extension_id')->nullable();

            // rental | extension | delivery
            $table->string('type')->default('rental');

            $table->string('description')->nullable();

            $table->decimal('amount', 28, 8)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 28, 8)->default(0);
            $table->decimal('total', 28, 8)->default(0);

            $table->timestamp('transacted_at')->nullable();

            $table->timestamps();

            $table->foreign('car_booking_id')
                ->references('id')->on('car_bookings')
                ->onDelete('cascade');

            $table->foreign('car_booking_extension_id')
                ->references('id')->on('car_booking_extensions')
                ->nullOnDelete();

            $table->index(['car_booking_id', 'type']);
            $table->index(['car_booking_id', 'transacted_at']);
        });

        // ------------------------------------------------------------------
        // 2. Seed rental (and delivery) transactions from existing bookings
        // ------------------------------------------------------------------
        $bookings = DB::table('car_bookings')->get();

        foreach ($bookings as $booking) {
            $deliveryFee    = (float) ($booking->delivery_fee ?? 0);
            $totalAmount    = (float) ($booking->total_amount ?? 0);
            $taxAmount      = (float) ($booking->tax_amount ?? 0);
            $taxPercentage  = (float) ($booking->tax_percentage ?? 0);
            $amount         = (float) ($booking->amount ?? 0);
            $rentalDays     = (int)   ($booking->rental_days ?? 0);
            $now            = now();
            $transactedAt   = $booking->created_at ?? $now;

            $rentalDescription = $rentalDays > 0
                ? "{$rentalDays} days rental"
                : 'Rental';

            // Each rental row total = entire booking total minus standalone delivery fee
            // (delivery_fee is tracked separately below so totals don't double-count)
            $rentalTotal = $deliveryFee > 0
                ? $totalAmount - $deliveryFee
                : $totalAmount;

            DB::table('car_booking_transactions')->insert([
                'car_booking_id'           => $booking->id,
                'car_booking_extension_id' => null,
                'type'                     => 'rental',
                'description'              => $rentalDescription,
                'amount'                   => $amount,
                'tax_percentage'           => $taxPercentage,
                'tax_amount'               => $taxAmount,
                'total'                    => $rentalTotal,
                'transacted_at'            => $transactedAt,
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);

            // Delivery row (when delivery was charged)
            if ($deliveryFee > 0) {
                DB::table('car_booking_transactions')->insert([
                    'car_booking_id'           => $booking->id,
                    'car_booking_extension_id' => null,
                    'type'                     => 'delivery',
                    'description'              => 'Delivery service',
                    'amount'                   => $deliveryFee,
                    'tax_percentage'           => 0,
                    'tax_amount'               => 0,
                    'total'                    => $deliveryFee,
                    'transacted_at'            => $transactedAt,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ]);
            }
        }

        // ------------------------------------------------------------------
        // 3. Seed extension transactions from approved extensions
        // ------------------------------------------------------------------
        $extensions = DB::table('car_booking_extensions')
            ->where('status', 1) // 1 = approved
            ->get();

        foreach ($extensions as $extension) {
            $extensionDays = (int) ($extension->extension_days ?? 0);
            $description   = $extensionDays > 0
                ? "+{$extensionDays} days extension"
                : 'Extension';

            DB::table('car_booking_transactions')->insert([
                'car_booking_id'           => $extension->car_booking_id,
                'car_booking_extension_id' => $extension->id,
                'type'                     => 'extension',
                'description'              => $description,
                'amount'                   => (float) ($extension->rental_fees ?? 0),
                'tax_percentage'           => (float) ($extension->tax_percentage ?? 0),
                'tax_amount'               => (float) ($extension->tax_amount ?? 0),
                'total'                    => (float) ($extension->total_cost ?? 0),
                'transacted_at'            => $extension->created_at ?? now(),
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_booking_transactions');
    }
};

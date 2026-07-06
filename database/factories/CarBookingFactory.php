<?php

namespace Database\Factories;

use App\Models\Admin\Branch;
use App\Models\CarBooking;
use App\Models\User;
use App\Models\Vendor\Cars\Car;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * CarBooking factory — creates a booking record with realistic defaults.
 *
 * Status constants (CarBookingConst):
 *   0 = PENDING  — awaiting vendor confirmation
 *   1 = BOOKED   — confirmed by vendor
 *   2 = ONGOING  — car picked up, rental in progress
 *   3 = COMPLETED
 *   4 = REJECTED
 *
 * Default: status=0 (pending), payment_type=cash, 3 rental days starting tomorrow.
 *
 * Usage:
 *   CarBooking::factory()->create(['user_id' => $user->id, 'car_id' => $car->id]);
 *   CarBooking::factory()->ongoing()->create([...]);   // status=2
 *   CarBooking::factory()->booked()->create([...]);    // status=1
 */
class CarBookingFactory extends Factory
{
    protected $model = CarBooking::class;

    public function definition(): array
    {
        $rentalDays = 3;
        $pickupDate = now()->addDay()->toDateString();

        return [
            'trip_id'              => substr(date('y'), -2) . fake()->unique()->numerify('#####'),
            'car_id'               => Car::factory(),
            'user_id'              => User::factory(),
            'branch_id'            => Branch::factory(),
            'slug'                 => (string) Str::uuid(),
            'rental_days'          => $rentalDays,
            'pickup_date'          => $pickupDate,
            'pickup_time'          => '10:00:00',
            'return_date'          => now()->addDays($rentalDays + 1)->toDateString(),
            'location'             => 'Riyadh',
            'destination'          => 'Jeddah',
            'email'                => fake()->safeEmail(),
            'phone'                => '+966501234567',
            'status'               => 0,
            'payment_type'         => 'cash',
            'trx_id'               => 'TRX-' . strtoupper(Str::random(8)),
            'amount'               => $rentalDays * 100.00,
            'charges'              => 0.00,
            'tax_percentage'       => 15.00,
            'tax_amount'           => $rentalDays * 15.00,
            'subtotal'             => $rentalDays * 100.00,
            'total_amount'         => $rentalDays * 115.00,
            'paid_from_balance'    => false,
            'balance_deducted'     => 0.00,
            'extension_count'      => 0,
            'total_extension_days' => 0,
        ];
    }

    /**
     * State: booking is ONGOING (status=2, car in use).
     *
     * Use this for extension tests — only ONGOING bookings can be extended.
     * The pickup_date is set to today so return_date is always in the future.
     */
    public function ongoing(): static
    {
        return $this->state([
            'status'      => 2,
            'pickup_date' => now()->toDateString(),
            'pickup_time' => '00:00:00',
            'rental_days' => 10, // 10 days ensures return is well in the future
            'return_date' => now()->addDays(10)->toDateString(),
        ]);
    }

    /**
     * State: booking is BOOKED (status=1, confirmed but not started).
     * Cannot be extended — only ONGOING bookings can be extended.
     */
    public function booked(): static
    {
        return $this->state(['status' => 1]);
    }

    /**
     * State: booking is COMPLETED (status=3).
     * Used to test that completed bookings cannot be extended or cancelled.
     */
    public function completed(): static
    {
        return $this->state(['status' => 3]);
    }
}

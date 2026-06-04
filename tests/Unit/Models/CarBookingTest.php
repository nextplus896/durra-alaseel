<?php

namespace Tests\Unit\Models;

use App\Models\Admin\Branch;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use App\Models\CarBooking;
use App\Models\Vendor\Cars\Car;
use App\Models\Vendor\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for CarBooking computed attributes and static helpers.
 *
 * What we cover:
 *   1. calculateReturnDate() — pickup_date + rental_days + extension_days
 *   2. isExtendable         — status=ONGOING and return date still in future
 *   3. daysRemaining        — positive count or floor-to-zero if expired
 *   4. hasConflictForExtension() — double-booking detection static helper
 *
 * All tests use real database rows (RefreshDatabase + factory) because:
 * - calculateReturnDate() reads from the model's attributes (needs a real instance)
 * - isExtendable reads `status` and calls calculateReturnDate()
 * - hasConflictForExtension() runs a real query against car_bookings
 *
 * No mocks needed — these are pure model-level queries with no HTTP.
 */
class CarBookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a minimal Car + User + Branch, then return a CarBooking with the
     * given attribute overrides.
     *
     * By centralising the setup here, individual tests stay readable and only
     * specify the attributes that are relevant to what they're testing.
     */
    private function makeBooking(array $overrides = []): CarBooking
    {
        $vendor   = Vendor::factory()->create();
        $carType  = CarType::factory()->create();
        $carModel = CarModel::factory()->create(['car_type_id' => $carType->id]);
        $branch   = Branch::factory()->create();

        $car = Car::factory()->create([
            'vendor_id'    => $vendor->id,
            'car_type_id'  => $carType->id,
            'car_model_id' => $carModel->id,
            'branch_id'    => $branch->id,
        ]);

        $user = $this->createUserWithWallet();

        return CarBooking::factory()->create(array_merge([
            'car_id'    => $car->id,
            'user_id'   => $user->id,
            'branch_id' => $branch->id,
        ], $overrides));
    }

    // =========================================================================
    // Section 1: calculateReturnDate()
    // =========================================================================

    /**
     * @test
     * Return date = pickup_date + rental_days (no extensions).
     *
     * Example: Car picked up on 2026-07-01 for 5 days → returned on 2026-07-06.
     * This is the standard calculation. The pickup_time is preserved in the
     * returned Carbon instance for exact hour-level comparisons elsewhere.
     */
    public function return_date_equals_pickup_date_plus_rental_days(): void
    {
        $booking = $this->makeBooking([
            'pickup_date'          => '2026-07-01',
            'pickup_time'          => '10:00:00',
            'rental_days'          => 5,
            'total_extension_days' => 0,
        ]);

        $returnDate = $booking->calculateReturnDate();

        $this->assertEquals('2026-07-06', $returnDate->toDateString(),
            'Return date must be pickup_date + rental_days for unextended bookings');
    }

    /**
     * @test
     * When extensions have been approved, total_extension_days is added.
     *
     * Example: 5-day booking + 3 extension days = return on day 9 from pickup.
     * The original rental_days is kept immutable; only total_extension_days grows.
     */
    public function return_date_includes_total_extension_days(): void
    {
        $booking = $this->makeBooking([
            'pickup_date'          => '2026-07-01',
            'pickup_time'          => '10:00:00',
            'rental_days'          => 5,
            'total_extension_days' => 3, // approved extensions
        ]);

        $returnDate = $booking->calculateReturnDate();

        $this->assertEquals('2026-07-09', $returnDate->toDateString(),
            'Return date must account for all approved extension days');
    }

    // =========================================================================
    // Section 2: isExtendable (getIsExtendableAttribute)
    // =========================================================================

    /**
     * @test
     * A booking that is ONGOING (status=2) and still has >2 hours until return
     * is eligible for extension.
     *
     * Why: Status=2 means the car has been picked up. Only at this point can
     * the customer request more time. The 2-hour buffer prevents extending a
     * booking that is effectively already over.
     */
    public function ongoing_booking_with_future_return_is_extendable(): void
    {
        $booking = $this->makeBooking([
            'status'               => 2,  // ONGOING
            'pickup_date'          => now()->toDateString(),
            'pickup_time'          => '00:00:00',
            'rental_days'          => 10, // return is 10 days from now — well in future
            'total_extension_days' => 0,
        ]);

        $this->assertTrue($booking->isExtendable,
            'An ONGOING booking with a future return date must be extendable');
    }

    /**
     * @test
     * A PENDING booking (status=0) cannot be extended — the car hasn't even
     * been picked up yet.
     *
     * Why: Extending a pending booking makes no sense. The vendor could still
     * reject it. Extensions are only allowed after the booking is active.
     */
    public function pending_booking_is_not_extendable(): void
    {
        $booking = $this->makeBooking([
            'status'      => 0,   // PENDING
            'pickup_date' => now()->toDateString(),
            'rental_days' => 10,
        ]);

        $this->assertFalse($booking->isExtendable,
            'PENDING bookings (status=0) are not yet active and cannot be extended');
    }

    /**
     * @test
     * A booking whose return date has already passed cannot be extended —
     * even if the status is still ONGOING (e.g., return was never recorded).
     *
     * Example: Car pickup 5 days ago, 3-day rental → return date was 2 days ago.
     * The UI should not show an "Extend" button in this state.
     */
    public function booking_with_past_return_date_is_not_extendable(): void
    {
        $booking = $this->makeBooking([
            'status'               => 2,   // ONGOING
            'pickup_date'          => now()->subDays(5)->toDateString(),
            'rental_days'          => 3,   // return was 2 days ago
            'total_extension_days' => 0,
        ]);

        $this->assertFalse($booking->isExtendable,
            'Booking with a past return date must not be extendable');
    }

    // =========================================================================
    // Section 3: daysRemaining (getDaysRemainingAttribute)
    // =========================================================================

    /**
     * @test
     * For an active booking, daysRemaining > 0.
     *
     * Example: A 10-day rental that started today has ~10 days remaining.
     * The exact value depends on `now()` so we only assert > 0.
     */
    public function days_remaining_is_positive_for_active_booking(): void
    {
        $booking = $this->makeBooking([
            'pickup_date'          => now()->toDateString(),
            'pickup_time'          => '00:00:00',
            'rental_days'          => 10,
            'total_extension_days' => 0,
        ]);

        $this->assertGreaterThan(0, $booking->daysRemaining,
            'An active booking with a future return must have positive daysRemaining');
    }

    /**
     * @test
     * For a booking whose return date has passed, daysRemaining returns 0
     * (not a negative number).
     *
     * Why: Callers use this to display "X days remaining" in the UI.
     * A negative value would display "-2 days remaining" which is confusing.
     * max(0, diff) ensures the floor is always 0.
     */
    public function days_remaining_floors_at_zero_for_expired_booking(): void
    {
        $booking = $this->makeBooking([
            'pickup_date'          => now()->subDays(20)->toDateString(),
            'rental_days'          => 5,   // return was 15 days ago
            'total_extension_days' => 0,
        ]);

        $this->assertEquals(0, $booking->daysRemaining,
            'Expired bookings must show 0 days remaining, not a negative number');
    }

    // =========================================================================
    // Section 4: hasConflictForExtension() — double-booking prevention
    // =========================================================================

    /**
     * @test
     * No conflict when there are no other bookings for the same car in the
     * extension window.
     *
     * Example: Car A is booked 2026-08-01 to 2026-08-06. The customer wants
     * to extend to 2026-08-09. No other booking exists for car A in that window,
     * so hasConflictForExtension() must return false.
     */
    public function no_conflict_when_no_other_bookings_exist(): void
    {
        $booking = $this->makeBooking([
            'pickup_date'          => '2026-08-01',
            'rental_days'          => 5,
            'total_extension_days' => 0,
        ]);

        $hasConflict = CarBooking::hasConflictForExtension(
            carId: $booking->car_id,
            currentReturnDate: '2026-08-06',
            newReturnDate: '2026-08-09',
            excludeBookingId: $booking->id,
        );

        $this->assertFalse($hasConflict,
            'No conflict should exist when no other bookings overlap the extension window');
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\Admin\CancellationPolicy;
use App\Models\CarBooking;
use App\Services\CancellationRefundService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Unit tests for CancellationRefundService.
 *
 * What we cover:
 *   1. Outside-window cancellations → only service fee
 *   2. Inside-window cancellations  → deduction + service fee
 *   3. All four deduction types (none, fixed, percentage, day)
 *   4. All three service fee types  (none, fixed, percentage)
 *   5. Refund is never negative (financial safety guard)
 *   6. Multi-day rental with day-type deduction
 *   7. No active policy → throws RuntimeException
 *
 * Why "unit"? No HTTP, no auth, no routing. Pure service logic.
 * Bookings are built via factory + state overrides.
 */
class CancellationRefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private CancellationRefundService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CancellationRefundService::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a minimal CarBooking stub without hitting the full factory chain.
     *
     * We override only the fields the service reads:
     *   amount, rental_days, pickup_date, pickup_time
     */
    private function makeBooking(
        float $amount,
        int   $rentalDays,
        int   $hoursFromNow
    ): CarBooking {
        $booking = new CarBooking();
        $booking->amount      = $amount;
        $booking->rental_days = $rentalDays;
        $booking->pickup_date = now()->addHours($hoursFromNow)->toDateString();
        $booking->pickup_time = now()->addHours($hoursFromNow)->format('H:i:s');
        return $booking;
    }

    /**
     * Create the global CancellationPolicy record in the DB (required by service).
     *
     * Deletes all existing records first because:
     *   1. The migration inserts a default seed record.
     *   2. CancellationPolicy is single-record — getActive() returns first(),
     *      so multiple rows would cause getActive() to return the wrong one.
     */
    private function makePolicy(array $overrides = []): CancellationPolicy
    {
        CancellationPolicy::query()->delete();

        return CancellationPolicy::create(array_merge([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1.00,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10.00,
            'is_active'                 => true,
        ], $overrides));
    }

    // =========================================================================
    // 1. Window boundary
    // =========================================================================

    /**
     * @test
     * Cancelling CLEARLY OUTSIDE the window (5h > 4h window) is treated as
     * "outside" — only the service fee is charged, no rental deduction.
     *
     * Note: The exact boundary (hours == window) is intentionally avoided here
     * because PHP datetime arithmetic introduces sub-millisecond drift that
     * makes == comparisons unreliable in tests. The service uses strict < so
     * any value >= window is safely outside.
     *
     * Example: window=4h, cancel 5h before pickup → only fee applies.
     */
    public function cancel_outside_window_applies_only_service_fee(): void
    {
        $this->makePolicy([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10,
        ]);

        // Booking pickup is 5 hours from now — clearly outside the 4h window
        $booking = $this->makeBooking(amount: 200, rentalDays: 1, hoursFromNow: 5);

        $dto = $this->service->calculate($booking);

        $this->assertFalse($dto->is_within_window, 'Outside window');
        $this->assertEquals(0.00,   $dto->deduction_amount,   'No deduction outside window');
        $this->assertEquals(20.00,  $dto->service_fee_amount, '10% of 200 = 20');
        $this->assertEquals(180.00, $dto->refund_amount,      '200 - 0 - 20 = 180');
    }

    /**
     * @test
     * Cancelling BEFORE the window (hours > window): only service fee charged.
     *
     * Example: window=4h, cancel 10h before pickup
     *   Rental = 200, Service fee = 10% = 20, Refund = 180
     */
    public function cancel_before_window_applies_only_service_fee(): void
    {
        $this->makePolicy([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10,
        ]);

        $booking = $this->makeBooking(amount: 200, rentalDays: 1, hoursFromNow: 10);

        $dto = $this->service->calculate($booking);

        $this->assertFalse($dto->is_within_window);
        $this->assertEquals(0.00,   $dto->deduction_amount);
        $this->assertEquals(20.00,  $dto->service_fee_amount);
        $this->assertEquals(180.00, $dto->refund_amount);
    }

    /**
     * @test
     * Cancelling INSIDE the window (hours < window): both deduction AND fee applied.
     *
     * Example: window=4h, cancel 2h before pickup
     *   Rental = 200 (1 day), Deduction = 1 day = 200, Fee = 10% = 20, Refund = max(0, -20) = 0
     */
    public function cancel_inside_window_applies_deduction_and_service_fee(): void
    {
        $this->makePolicy([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10,
        ]);

        $booking = $this->makeBooking(amount: 200, rentalDays: 1, hoursFromNow: 2);

        $dto = $this->service->calculate($booking);

        $this->assertTrue($dto->is_within_window);
        $this->assertEquals(200.00, $dto->deduction_amount);
        $this->assertEquals(20.00,  $dto->service_fee_amount);
        $this->assertEquals(0.00,   $dto->refund_amount, 'Refund clamped to 0 — never negative');
    }

    // =========================================================================
    // 2. Deduction types
    // =========================================================================

    /**
     * @test
     * DEDUCTION_NONE: inside window but deduction_type = none → zero deduction.
     */
    public function deduction_type_none_skips_deduction(): void
    {
        $this->makePolicy([
            'deduction_type'  => 'none',
            'deduction_value' => 0,
            'service_fee_type'  => 'none',
            'service_fee_value' => 0,
        ]);

        $booking = $this->makeBooking(amount: 300, rentalDays: 3, hoursFromNow: 1);

        $dto = $this->service->calculate($booking);

        $this->assertEquals(0.00,   $dto->deduction_amount);
        $this->assertEquals(0.00,   $dto->service_fee_amount);
        $this->assertEquals(300.00, $dto->refund_amount);
    }

    /**
     * @test
     * DEDUCTION_FIXED: inside window, fixed deduction of 150 SAR.
     *
     * Rental = 500, Deduction = 150, Fee = none, Refund = 350
     */
    public function deduction_type_fixed_applies_fixed_amount(): void
    {
        $this->makePolicy([
            'deduction_type'    => 'fixed',
            'deduction_value'   => 150,
            'service_fee_type'  => 'none',
            'service_fee_value' => 0,
        ]);

        $booking = $this->makeBooking(amount: 500, rentalDays: 5, hoursFromNow: 1);

        $dto = $this->service->calculate($booking);

        $this->assertEquals(150.00, $dto->deduction_amount);
        $this->assertEquals(0.00,   $dto->service_fee_amount);
        $this->assertEquals(350.00, $dto->refund_amount);
    }

    /**
     * @test
     * DEDUCTION_PERCENTAGE: inside window, 50% deduction.
     *
     * Rental = 400, Deduction = 200, Fee = none, Refund = 200
     */
    public function deduction_type_percentage_calculates_correctly(): void
    {
        $this->makePolicy([
            'deduction_type'    => 'percentage',
            'deduction_value'   => 50,
            'service_fee_type'  => 'none',
            'service_fee_value' => 0,
        ]);

        $booking = $this->makeBooking(amount: 400, rentalDays: 2, hoursFromNow: 1);

        $dto = $this->service->calculate($booking);

        $this->assertEquals(200.00, $dto->deduction_amount);
        $this->assertEquals(200.00, $dto->refund_amount);
    }

    /**
     * @test
     * DEDUCTION_DAY (multi-day): inside window, 1 day deducted from 3-day booking.
     *
     * Example from spec:
     *   Duration = 3 days, Daily = 200 SAR, Rental = 600
     *   Deduction = 1 day = 200, Fee = 10% = 60, Refund = 340
     */
    public function deduction_type_day_multi_day_rental(): void
    {
        $this->makePolicy([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10,
        ]);

        $booking = $this->makeBooking(amount: 600, rentalDays: 3, hoursFromNow: 1);

        $dto = $this->service->calculate($booking);

        $this->assertTrue($dto->is_within_window);
        $this->assertEquals(200.00, $dto->deduction_amount,   '600 / 3 days × 1 = 200');
        $this->assertEquals(60.00,  $dto->service_fee_amount, '10% of 600 = 60');
        $this->assertEquals(340.00, $dto->refund_amount,      '600 - 200 - 60 = 340');
    }

    // =========================================================================
    // 3. Service fee types
    // =========================================================================

    /**
     * @test
     * FEE_NONE: outside window with no fee → full refund.
     */
    public function service_fee_type_none_applies_zero_fee(): void
    {
        $this->makePolicy([
            'service_fee_type'  => 'none',
            'service_fee_value' => 0,
            'deduction_type'    => 'none',
            'deduction_value'   => 0,
        ]);

        $booking = $this->makeBooking(amount: 300, rentalDays: 3, hoursFromNow: 10);

        $dto = $this->service->calculate($booking);

        $this->assertEquals(0.00,   $dto->service_fee_amount);
        $this->assertEquals(300.00, $dto->refund_amount, 'No deduction or fee → full refund');
    }

    /**
     * @test
     * FEE_FIXED: 50 SAR flat service fee outside window.
     *
     * Rental = 200, Fee = 50 fixed, Refund = 150
     */
    public function service_fee_type_fixed_applies_flat_amount(): void
    {
        $this->makePolicy([
            'service_fee_type'  => 'fixed',
            'service_fee_value' => 50,
            'deduction_type'    => 'none',
            'deduction_value'   => 0,
        ]);

        $booking = $this->makeBooking(amount: 200, rentalDays: 2, hoursFromNow: 10);

        $dto = $this->service->calculate($booking);

        $this->assertEquals(50.00,  $dto->service_fee_amount);
        $this->assertEquals(150.00, $dto->refund_amount);
    }

    // =========================================================================
    // 4. Financial safety guards
    // =========================================================================

    /**
     * @test
     * Refund must never go negative.
     *
     * Example: 1-day booking, fixed deduction = 999 SAR, rental = 200 SAR.
     * 200 - 999 - fee would be negative → clamp to 0.
     */
    public function refund_is_clamped_to_zero_never_negative(): void
    {
        $this->makePolicy([
            'deduction_type'    => 'fixed',
            'deduction_value'   => 999,
            'service_fee_type'  => 'fixed',
            'service_fee_value' => 50,
        ]);

        $booking = $this->makeBooking(amount: 200, rentalDays: 1, hoursFromNow: 1);

        $dto = $this->service->calculate($booking);

        $this->assertEquals(0.00, $dto->refund_amount, 'Refund must never be negative');
    }

    /**
     * @test
     * When deductions are exactly equal to the rental, refund is 0 (not negative).
     */
    public function refund_is_zero_when_deductions_equal_rental(): void
    {
        $this->makePolicy([
            'deduction_type'    => 'percentage',
            'deduction_value'   => 90,          // 90% of 200 = 180
            'service_fee_type'  => 'percentage',
            'service_fee_value' => 10,          // 10% of 200 = 20  → 200 total
        ]);

        $booking = $this->makeBooking(amount: 200, rentalDays: 1, hoursFromNow: 1);

        $dto = $this->service->calculate($booking);

        $this->assertEquals(0.00, $dto->refund_amount);
    }

    // =========================================================================
    // 5. Error handling
    // =========================================================================

    /**
     * @test
     * Throws RuntimeException when no active policy is configured.
     *
     * Why: The service contract guarantees it always returns a DTO or throws.
     * Callers must handle the RuntimeException (e.g. return 503 to the client).
     */
    public function throws_when_no_active_policy_configured(): void
    {
        // Remove the migration seed record so no active policy exists
        CancellationPolicy::query()->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active cancellation policy found');

        $booking = $this->makeBooking(amount: 200, rentalDays: 1, hoursFromNow: 10);
        $this->service->calculate($booking);
    }

    /**
     * @test
     * Returns RuntimeException when policy exists but is_active = false.
     */
    public function throws_when_policy_is_inactive(): void
    {
        // The migration seed is active — override it to inactive
        $this->makePolicy(['is_active' => false]);

        $this->expectException(RuntimeException::class);

        $booking = $this->makeBooking(amount: 200, rentalDays: 1, hoursFromNow: 10);
        $this->service->calculate($booking);
    }
}

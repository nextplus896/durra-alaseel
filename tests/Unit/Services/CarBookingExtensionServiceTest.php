<?php

namespace Tests\Unit\Services;

use App\Models\Admin\BasicSettings;
use App\Models\Admin\Branch;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use App\Models\CarBooking;
use App\Models\Vendor\Cars\Car;
use App\Models\Vendor\Vendor;
use App\Services\CarBookingExtensionService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Unit tests for CarBookingExtensionService.
 *
 * What we cover:
 *   1. validateExtension()     — ownership, status, day-range, 365-day cap
 *   2. calculateExtensionCost() — delegates to tiered pricing + tax
 *   3. processExtension()      — DB writes, balance deduction, record creation
 *
 * Setup: Every test in this class starts with a single ONGOING booking
 * for a car priced at 100 SAR/day, belonging to a user with 5000 SAR balance.
 * Tests that need different conditions override specific fields via update().
 *
 * Why real DB? processExtension() uses DB::transaction() + lockForUpdate().
 * Mocking those would make the test useless for catching concurrency bugs.
 * SQLite ignores lockForUpdate gracefully (no error, no actual lock).
 */
class CarBookingExtensionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CarBookingExtensionService $service;
    private Car $car;
    private CarBooking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        // Seed 15% VAT so cost calculations are deterministic.
        BasicSettings::factory()->create(['tax_status' => true, 'tax_percentage' => 15.00]);

        $this->service = app(CarBookingExtensionService::class);

        // Build a single car with known prices (100 SAR/day daily tier).
        $vendor   = Vendor::factory()->create();
        $carType  = CarType::factory()->create();
        $carModel = CarModel::factory()->create(['car_type_id' => $carType->id]);
        $branch   = Branch::factory()->create();

        $this->car = Car::factory()->create([
            'vendor_id'      => $vendor->id,
            'car_type_id'    => $carType->id,
            'car_model_id'   => $carModel->id,
            'branch_id'      => $branch->id,
            'price_per_day'  => 100.00,
            'price_per_week' => 80.00,
        ]);

        // User with enough balance to pay for any reasonable extension.
        $user = $this->createUserWithWallet(5000.00);

        // ONGOING booking that started today — return is 10 days from now.
        $this->booking = CarBooking::factory()->ongoing()->create([
            'car_id'               => $this->car->id,
            'user_id'              => $user->id,
            'branch_id'            => $branch->id,
            'rental_days'          => 10,
            'total_extension_days' => 0,
        ]);
    }

    // =========================================================================
    // Section 1: validateExtension()
    // =========================================================================

    /**
     * @test
     * A user who did not create the booking cannot extend it.
     *
     * Example: User B tries to extend User A's booking via a spoofed API call.
     * The service must reject this based on the booking's user_id, not just
     * the route auth guard.
     */
    public function validation_fails_when_caller_is_not_the_booking_owner(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/not authorized/i');

        // 9999 is a different user ID — not the booking owner.
        $this->service->validateExtension($this->booking, 5, authUserId: 9999);
    }

    /**
     * @test
     * Only ONGOING (status=2) bookings can be extended.
     *
     * Example: User has a BOOKED (status=1) booking — car not yet picked up.
     * Allowing extension here makes no business sense.
     *
     * Status codes: 0=PENDING, 1=BOOKED, 2=ONGOING, 3=COMPLETED, 4=REJECTED
     */
    public function validation_fails_when_booking_is_not_ongoing(): void
    {
        $this->booking->update(['status' => 1]); // BOOKED, not ONGOING

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/ongoing bookings/i');

        $this->service->validateExtension($this->booking->fresh(), 5, $this->booking->user_id);
    }

    /**
     * @test
     * Extension days must be at least 1 — zero-day extensions are rejected.
     *
     * Example: A bug in the mobile app sends extension_days=0. The service
     * must reject this before touching the DB.
     */
    public function validation_fails_when_extension_days_is_zero(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/between 1 and 90/i');

        $this->service->validateExtension($this->booking, extensionDays: 0, authUserId: $this->booking->user_id);
    }

    /**
     * @test
     * Extension days cannot exceed 90 in a single request.
     *
     * Why: Extensions over 90 days suggest a data entry error. The customer
     * should create a new booking instead of stacking months of extensions.
     */
    public function validation_fails_when_extension_days_exceed_90(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/between 1 and 90/i');

        $this->service->validateExtension($this->booking, extensionDays: 91, authUserId: $this->booking->user_id);
    }

    /**
     * @test
     * The total rental duration (original + all extensions + this request)
     * cannot exceed 365 days.
     *
     * Example: Booking has 360 rental days + 0 previous extensions.
     * Requesting 10 more days would make it 370 days total → rejected.
     *
     * Why: 365-day cap prevents a single booking from occupying a car
     * indefinitely and blocking future customers.
     */
    public function validation_fails_when_total_rental_exceeds_365_days(): void
    {
        $this->booking->update(['rental_days' => 360, 'total_extension_days' => 0]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/365 days/i');

        $this->service->validateExtension($this->booking->fresh(), 10, $this->booking->user_id);
    }

    // =========================================================================
    // Section 2: calculateExtensionCost()
    // =========================================================================

    /**
     * @test
     * 5-day extension at 100 SAR/day (daily tier) + 15% VAT = 575 SAR total.
     *
     * Calculation breakdown:
     *   rental_fees = 5 × 100 = 500 SAR
     *   tax_amount  = 500 × 15% = 75 SAR
     *   total_cost  = 575 SAR
     *
     * The daily tier applies because 5 ≤ 7.
     */
    public function extension_cost_uses_daily_tier_for_5_days(): void
    {
        $cost = $this->service->calculateExtensionCost($this->car, 5);

        $this->assertEquals('daily', $cost['price_rule_applied'],
            '5 days is within the daily tier (≤ 7 days)');
        $this->assertEquals(100.00, $cost['base_price'],
            'Base price is price_per_day = 100 SAR');
        $this->assertEquals(500.00, $cost['rental_fees'],
            '5 days × 100 SAR = 500 SAR rental fees');
        $this->assertEquals(75.00, $cost['tax_amount'],
            '500 SAR × 15% = 75 SAR VAT');
        $this->assertEquals(575.00, $cost['total_cost'],
            '500 + 75 = 575 SAR total');
    }

    /**
     * @test
     * 10-day extension uses the weekly tier (80 SAR/day) because 10 > 7.
     *
     * Important: The extension pricing uses the CURRENT car's price_per_week,
     * NOT the original booking's daily rate. This is by design — extension
     * pricing is independent of the original booking's tier.
     */
    public function extension_cost_uses_weekly_tier_for_10_days(): void
    {
        $cost = $this->service->calculateExtensionCost($this->car, 10);

        $this->assertEquals('weekly', $cost['price_rule_applied'],
            '10 days falls in the weekly tier (8–30 days)');
        $this->assertEquals(80.00, $cost['base_price'],
            'Base price is price_per_week = 80 SAR');
    }

    // =========================================================================
    // Section 3: processExtension()
    // =========================================================================

    /**
     * @test
     * A successful cash extension creates a booking_extensions record.
     *
     * Example: Customer extends by 5 days and will pay at the counter (cash).
     * The extension record is created immediately; payment happens on return.
     */
    public function process_extension_creates_extension_record_for_cash_payment(): void
    {
        $user = $this->booking->user;

        $this->service->processExtension(
            booking: $this->booking,
            extensionDays: 5,
            authUserId: $user->id,
            paymentType: 'cash',
        );

        $this->assertDatabaseHas('booking_extensions', [
            'car_booking_id' => $this->booking->id,
            'extension_days' => 5,
            'payment_type'   => 'cash',
        ]);
    }

    /**
     * @test
     * After a successful extension, the parent booking's total_extension_days
     * is incremented by the requested number of days.
     *
     * Why: total_extension_days on the booking is what calculateReturnDate()
     * uses. If it's not updated, the return date will be wrong.
     */
    public function process_extension_updates_booking_total_extension_days(): void
    {
        $user = $this->booking->user;

        $this->service->processExtension($this->booking, 3, $user->id, 'cash');

        $this->assertEquals(3, $this->booking->fresh()->total_extension_days,
            'total_extension_days must be incremented by the extension length');
    }

    /**
     * @test
     * A balance payment extension deducts the cost from the user's wallet.
     *
     * Calculation: 5 days × 100 SAR + 15% VAT = 575 SAR deducted.
     * Starting balance: 5000 SAR → expected remaining: 4425 SAR.
     */
    public function process_extension_deducts_balance_for_wallet_payment(): void
    {
        $user = $this->booking->user;

        $this->service->processExtension($this->booking, 5, $user->id, 'balance', $user);

        // 5 × 100 × 1.15 = 575 SAR deducted from 5000 SAR
        $expectedBalance = 5000.00 - 575.00;
        $this->assertEquals($expectedBalance, $user->fresh()->balance,
            'Wallet balance must decrease by the extension total cost (rental + VAT)');
    }

    /**
     * @test
     * A balance payment extension creates a balance_transactions ledger row.
     *
     * Why: Financial integrity — every money movement needs an audit trail.
     * The row is also linked to the booking via booking_id so support staff
     * can trace which booking triggered the deduction.
     */
    public function process_extension_creates_balance_transaction_for_wallet_payment(): void
    {
        $user = $this->booking->user;

        $this->service->processExtension($this->booking, 5, $user->id, 'balance', $user);

        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $user->id,
            'type'    => 'booking_deduction',
        ]);
    }

    /**
     * @test
     * If the user's wallet has insufficient balance, processExtension() throws
     * BEFORE entering the DB transaction — no partial writes occur.
     *
     * Example: Extension costs 575 SAR, user only has 10 SAR.
     * The exception is raised during the pre-transaction balance check.
     */
    public function process_extension_throws_when_wallet_has_insufficient_balance(): void
    {
        // Replace the booking owner with a user who only has 10 SAR.
        $poorUser = $this->createUserWithWallet(10.00);
        $this->booking->update(['user_id' => $poorUser->id]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient balance/i');

        $this->service->processExtension(
            $this->booking->fresh(), 5, $poorUser->id, 'balance', $poorUser
        );
    }
}

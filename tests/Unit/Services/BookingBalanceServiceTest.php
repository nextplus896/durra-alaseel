<?php

namespace Tests\Unit\Services;

use App\Models\Admin\BasicSettings;
use App\Models\Vendor\Cars\Car;
use App\Services\BookingBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for BookingBalanceService.
 *
 * What we cover:
 *   1. Tiered pricing — daily / weekly / monthly rate selection
 *   2. Boundary conditions — the exact day cutoffs (7→8, 30→31)
 *   3. Tax calculation — percentage read from BasicSettings, rounded correctly
 *   4. Booking total assembly — rental + delivery + charges + tax
 *   5. Balance sufficiency check — simple comparison helper
 *
 * Why unit tests (not feature tests)?
 *   BookingBalanceService contains pure arithmetic. There is no HTTP layer
 *   to test, no authentication, no routing. Keeping them as unit tests
 *   makes them fast, focused, and easy to run in isolation.
 */
class BookingBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingBalanceService $service;

    /**
     * $car is a plain PHP object (not persisted) because calculateRentalFees()
     * only reads Car attributes — it never queries the database.
     * We build it manually to avoid a DB round-trip and keep the test fast.
     */
    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BookingBalanceService();

        // Build an in-memory Car with known prices for deterministic assertions.
        // price_per_day   → applies when rental_days ≤ 7
        // price_per_week  → applies when rental_days 8–30
        // price_per_month → applies when rental_days ≥ 31
        $this->car = new Car();
        $this->car->price_per_day   = 100.00;
        $this->car->price_per_week  = 80.00;
        $this->car->price_per_month = 70.00;
    }

    // =========================================================================
    // Section 1: Tiered Pricing — calculateRentalFees()
    // =========================================================================

    /**
     * @test
     * Daily pricing (≤ 7 days): 5 days × 100 SAR/day = 500 SAR.
     *
     * Example: A customer rents for a weekend (Fri–Tue = 5 days).
     * They pay the daily rate because their trip is within the 7-day window.
     */
    public function daily_pricing_applies_for_1_to_7_days(): void
    {
        $result = $this->service->calculateRentalFees($this->car, 5);

        $this->assertEquals('daily', $result['price_rule_applied'],
            'Trips ≤ 7 days should use the daily tier');
        $this->assertEquals(500.00, $result['rental_fees'],
            '5 days × 100 SAR = 500 SAR');
        $this->assertEquals(100.00, $result['base_price']);
        $this->assertEquals(5, $result['rental_days']);
    }

    /**
     * @test
     * Weekly pricing (8–30 days): 14 days × 80 SAR/day = 1120 SAR.
     *
     * Example: A business trip for 2 weeks qualifies for the cheaper weekly rate.
     * Each day is still billed individually — the "weekly" label means the
     * per-day rate is taken from price_per_week.
     */
    public function weekly_pricing_applies_for_8_to_30_days(): void
    {
        $result = $this->service->calculateRentalFees($this->car, 14);

        $this->assertEquals('weekly', $result['price_rule_applied'],
            'Trips of 8–30 days should use the weekly tier');
        $this->assertEquals(14 * 80.00, $result['rental_fees'],
            '14 days × 80 SAR = 1120 SAR');
        $this->assertEquals(80.00, $result['base_price']);
    }

    /**
     * @test
     * Monthly pricing (≥ 31 days): 31 days × 70 SAR/day = 2170 SAR.
     *
     * Example: An expat renting for an entire month gets the best per-day rate.
     */
    public function monthly_pricing_applies_for_31_plus_days(): void
    {
        $result = $this->service->calculateRentalFees($this->car, 31);

        $this->assertEquals('monthly', $result['price_rule_applied'],
            'Trips ≥ 31 days should use the monthly tier');
        $this->assertEquals(31 * 70.00, $result['rental_fees'],
            '31 days × 70 SAR = 2170 SAR');
        $this->assertEquals(70.00, $result['base_price']);
    }

    /**
     * @test
     * Boundary check: exactly 7 days → still uses the daily tier.
     *
     * This is the last day before the weekly rate kicks in.
     * If the boundary is off-by-one, a 7-day rental would wrongly use
     * the cheaper weekly rate, costing the platform money.
     */
    public function boundary_7_days_uses_daily_rate(): void
    {
        $result = $this->service->calculateRentalFees($this->car, 7);

        $this->assertEquals('daily', $result['price_rule_applied'],
            '7 days is the maximum for daily tier (≤ 7)');
    }

    /**
     * @test
     * Boundary check: exactly 8 days → switches to weekly tier.
     *
     * The customer saves (100 - 80) × 8 = 160 SAR by crossing the threshold.
     */
    public function boundary_8_days_uses_weekly_rate(): void
    {
        $result = $this->service->calculateRentalFees($this->car, 8);

        $this->assertEquals('weekly', $result['price_rule_applied'],
            '8 days is the first day of the weekly tier');
    }

    /**
     * @test
     * Boundary check: exactly 30 days → still in weekly tier.
     * Day 31 is the first day of monthly pricing.
     */
    public function boundary_30_days_uses_weekly_rate(): void
    {
        $result = $this->service->calculateRentalFees($this->car, 30);

        $this->assertEquals('weekly', $result['price_rule_applied'],
            '30 days is the last day of the weekly tier (≤ 30)');
    }

    // =========================================================================
    // Section 2: Tax — getTaxPercentage() and calculateTax()
    // =========================================================================

    /**
     * @test
     * When no BasicSettings row exists, the service returns a hard-coded 15% default.
     *
     * Why: A misconfigured database should not silently charge 0% tax.
     * The 15% fallback matches Saudi Arabia's VAT rate.
     */
    public function returns_default_15_percent_when_no_basic_settings_row(): void
    {
        // No BasicSettings seeded — table is empty.
        $percentage = $this->service->getTaxPercentage();

        $this->assertEquals(15.00, $percentage,
            'Missing settings should fall back to the hardcoded 15% default');
    }

    /**
     * @test
     * When BasicSettings has tax_status=true and tax_percentage=10, return 10.
     *
     * Example: Admin lowers the tax rate to 10% during a promotional period.
     * All new bookings should immediately reflect the new rate.
     */
    public function returns_configured_tax_when_tax_is_enabled(): void
    {
        BasicSettings::factory()->create(['tax_status' => true, 'tax_percentage' => 10.00]);

        $this->assertEquals(10.00, $this->service->getTaxPercentage());
    }

    /**
     * @test
     * When tax_status=false, getTaxPercentage() returns the 15% hardcoded fallback.
     *
     * Why: The service treats "tax disabled" as "use the safe default" rather
     * than returning 0%, which would let a misconfigured toggle silently
     * waive all VAT. The condition is: if (settings exist AND tax_status=true)
     * → use configured %; otherwise → use 15% fallback.
     *
     * To get 0% tax in practice, the admin must set tax_percentage=0 AND
     * tax_status=true, not just toggle tax_status off.
     */
    public function returns_15_fallback_when_tax_status_is_disabled(): void
    {
        BasicSettings::factory()->noTax()->create();

        // Disabled tax_status falls through to the hardcoded 15% fallback.
        $this->assertEquals(15.00, $this->service->getTaxPercentage());
    }

    /**
     * @test
     * calculateTax(1000, 15%) should return:
     *   tax_amount = 150.00
     *   total      = 1150.00
     *
     * This is the standard Saudi VAT calculation applied to a booking subtotal.
     */
    public function tax_is_calculated_correctly_on_subtotal(): void
    {
        BasicSettings::factory()->create(['tax_status' => true, 'tax_percentage' => 15.00]);

        $result = $this->service->calculateTax(1000.00);

        $this->assertEquals(1000.00, $result['subtotal']);
        $this->assertEquals(15.00,   $result['tax_percentage']);
        $this->assertEquals(150.00,  $result['tax_amount']);
        $this->assertEquals(1150.00, $result['total']);
    }

    /**
     * @test
     * Tax amounts are rounded to 2 decimal places.
     *
     * Example: 333.33 × 15% = 49.9995 SAR → should round to 50.00 SAR.
     * Without rounding, the booking total could have many decimal places,
     * which breaks payment gateway amount assertions.
     */
    public function tax_amounts_are_rounded_to_2_decimal_places(): void
    {
        BasicSettings::factory()->create(['tax_status' => true, 'tax_percentage' => 15.00]);

        $result = $this->service->calculateTax(333.33);

        $expected = round(333.33 * 0.15, 2);
        $this->assertEquals($expected, $result['tax_amount'],
            'Tax must be rounded to 2 decimal places to match payment gateway precision');
    }

    // =========================================================================
    // Section 3: Full Booking Total — calculateBookingTotal()
    // =========================================================================

    /**
     * @test
     * Full booking: rental=500 + delivery=50 + charges=20 = subtotal 570,
     * then 15% tax (85.50) → total 655.50 SAR.
     *
     * This covers the pricing formula from CLAUDE.md:
     *   subtotal = rental_fees + charges + delivery_fee
     *   tax      = subtotal × (tax_percentage / 100)
     *   total    = subtotal + tax
     */
    public function booking_total_sums_rental_delivery_charges_then_adds_tax(): void
    {
        BasicSettings::factory()->create(['tax_status' => true, 'tax_percentage' => 15.00]);

        $result = $this->service->calculateBookingTotal(500.00, 50.00, 20.00);

        $this->assertEquals(570.00, $result['subtotal'],
            'subtotal = rental(500) + delivery(50) + charges(20)');
        $this->assertEquals(85.50,  $result['tax_amount'],
            '570 × 15% = 85.50 SAR tax');
        $this->assertEquals(655.50, $result['total'],
            '570 + 85.50 = 655.50 SAR total');
    }

    /**
     * @test
     * Booking without delivery or extra charges: only rental + 15% tax.
     *
     * Example: Customer picks up the car from the branch (no delivery fee)
     * and no damage/extra charges apply.
     */
    public function booking_total_without_optional_fees_defaults_to_zero(): void
    {
        BasicSettings::factory()->create(['tax_status' => true, 'tax_percentage' => 15.00]);

        // No delivery or charges → default to 0
        $result = $this->service->calculateBookingTotal(500.00);

        $this->assertEquals(500.00, $result['subtotal']);
        $this->assertEquals(575.00, $result['total'],
            '500 + (500 × 15%) = 575.00 SAR');
    }

    // =========================================================================
    // Section 4: Balance Check — hasSufficientBalance()
    // =========================================================================

    /**
     * @test
     * User whose wallet balance exactly equals the required amount can pay.
     *
     * Example: Car costs exactly 500 SAR, user has exactly 500 SAR.
     * Edge-case: equal amounts must be treated as "sufficient" (≥, not >).
     */
    public function returns_true_when_balance_equals_required_amount(): void
    {
        $user = $this->createUserWithWallet(500.00);

        $this->assertTrue($this->service->hasSufficientBalance($user, 500.00),
            'Balance exactly equal to required amount should be accepted');
    }

    /**
     * @test
     * User with 100 SAR cannot pay a 500 SAR booking.
     *
     * This guards against allowing bookings that would result in a negative
     * wallet balance. The UI should show "Insufficient balance" at this point.
     */
    public function returns_false_when_balance_is_less_than_required(): void
    {
        $user = $this->createUserWithWallet(100.00);

        $this->assertFalse($this->service->hasSufficientBalance($user, 500.00),
            '100 SAR balance is insufficient for a 500 SAR booking');
    }
}

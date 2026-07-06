<?php

namespace Tests\Unit\Services;

use App\Models\Vendor\Cars\Car;
use App\Services\BookingBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for insurance calculation in BookingBalanceService.
 *
 * What we cover:
 *   1. Daily insurance type  → insurance_total = daily_insurance × rental_days
 *   2. Deductible type       → insurance_total = 0, deductible_insurance preserved
 *   3. Zero daily_insurance  → insurance_total = 0 (no charge, no error)
 *   4. calculateBookingTotal includes insurance in subtotal and total
 *   5. Deductible insurance  → does NOT affect subtotal or total
 *   6. Backward compat       → calculateBookingTotal without insurance arg unchanged
 */
class BookingInsuranceTest extends TestCase
{
    use RefreshDatabase;

    private BookingBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingBalanceService::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a minimal Car stub with only the fields the service reads.
     */
    private function makeCar(float $dailyInsurance, float $deductibleInsurance): Car
    {
        $car = new Car();
        $car->daily_insurance      = $dailyInsurance;
        $car->deductible_insurance = $deductibleInsurance;
        $car->price_per_day        = 200.00;
        $car->price_per_week       = 180.00;
        $car->price_per_month      = 150.00;
        return $car;
    }

    // =========================================================================
    // 1. calculateInsuranceTotal — daily type
    // =========================================================================

    /**
     * @test
     * Daily insurance: 20 SAR/day × 8 days = 160 SAR charged.
     * Deductible is 0 because daily insurance was chosen.
     */
    public function daily_insurance_type_charges_per_day(): void
    {
        $car = $this->makeCar(dailyInsurance: 20.00, deductibleInsurance: 3500.00);

        $result = $this->service->calculateInsuranceTotal($car, 8, 'daily');

        $this->assertEquals('daily',  $result['insurance_type']);
        $this->assertEquals(20.00,   $result['daily_insurance']);
        $this->assertEquals(160.00,  $result['insurance_total'],      '20 × 8 = 160');
        $this->assertEquals(0.0,     $result['deductible_insurance'], 'No deductible when daily chosen');
    }

    /**
     * @test
     * Single-day rental with daily insurance.
     */
    public function daily_insurance_single_day(): void
    {
        $car = $this->makeCar(dailyInsurance: 25.00, deductibleInsurance: 2000.00);

        $result = $this->service->calculateInsuranceTotal($car, 1, 'daily');

        $this->assertEquals(25.00,  $result['insurance_total'], '25 × 1 = 25');
    }

    // =========================================================================
    // 2. calculateInsuranceTotal — deductible type
    // =========================================================================

    /**
     * @test
     * Deductible type: nothing is charged, excess amount is stored for display.
     */
    public function deductible_insurance_type_charges_nothing(): void
    {
        $car = $this->makeCar(dailyInsurance: 20.00, deductibleInsurance: 3500.00);

        $result = $this->service->calculateInsuranceTotal($car, 8, 'deductible');

        $this->assertEquals('deductible', $result['insurance_type']);
        $this->assertEquals(0.0,    $result['daily_insurance'],      'No daily charge');
        $this->assertEquals(0.0,    $result['insurance_total'],      'Nothing added to total');
        $this->assertEquals(3500.0, $result['deductible_insurance'], 'Excess stored for display');
    }

    // =========================================================================
    // 3. Edge case — zero daily insurance
    // =========================================================================

    /**
     * @test
     * Car with daily_insurance = 0 → insurance_total = 0, no error.
     */
    public function zero_daily_insurance_results_in_zero_total(): void
    {
        $car = $this->makeCar(dailyInsurance: 0.00, deductibleInsurance: 1000.00);

        $result = $this->service->calculateInsuranceTotal($car, 5, 'daily');

        $this->assertEquals(0.0, $result['insurance_total']);
    }

    // =========================================================================
    // 4. calculateBookingTotal — daily insurance included in total
    // =========================================================================

    /**
     * @test
     * Grand total includes insurance when type = daily.
     *
     * Rental = 1600, Insurance = 160, Subtotal = 1760, Tax 15% = 264, Total = 2024
     */
    public function booking_total_includes_daily_insurance(): void
    {
        $car    = $this->makeCar(dailyInsurance: 20.00, deductibleInsurance: 3500.00);
        $result = $this->service->calculateInsuranceTotal($car, 8, 'daily');

        $breakdown = $this->service->calculateBookingTotal(
            rentalFees:     1600.00,
            deliveryPrice:  0,
            charges:        0,
            insuranceTotal: $result['insurance_total'],
        );

        $this->assertEquals(160.00,  $breakdown['insurance_total'], 'Insurance component');
        $this->assertEquals(1760.00, $breakdown['subtotal'],        'Rental + Insurance');
        // Tax on 1760 at default 15%
        $expectedTax   = round(1760.00 * 0.15, 2);
        $expectedTotal = round(1760.00 + $expectedTax, 2);
        $this->assertEquals($expectedTax,   $breakdown['tax_amount'], 'Tax on full subtotal');
        $this->assertEquals($expectedTotal, $breakdown['total'],      'Grand total');
    }

    // =========================================================================
    // 5. calculateBookingTotal — deductible does NOT affect total
    // =========================================================================

    /**
     * @test
     * When deductible type is selected, insurance_total = 0, so grand total
     * is identical to a booking with no insurance.
     *
     * Rental = 1600, Insurance = 0, Subtotal = 1600, Tax 15% = 240, Total = 1840
     */
    public function booking_total_unchanged_when_deductible_chosen(): void
    {
        $car    = $this->makeCar(dailyInsurance: 20.00, deductibleInsurance: 3500.00);
        $result = $this->service->calculateInsuranceTotal($car, 8, 'deductible');

        $breakdown = $this->service->calculateBookingTotal(
            rentalFees:     1600.00,
            deliveryPrice:  0,
            charges:        0,
            insuranceTotal: $result['insurance_total'],
        );

        $this->assertEquals(0.0,    $breakdown['insurance_total']);
        $this->assertEquals(1600.0, $breakdown['subtotal']);
        $expectedTax   = round(1600.0 * 0.15, 2);
        $expectedTotal = round(1600.0 + $expectedTax, 2);
        $this->assertEquals($expectedTotal, $breakdown['total']);
    }

    // =========================================================================
    // 6. Backward compatibility
    // =========================================================================

    /**
     * @test
     * Callers that do not pass $insuranceTotal still work — the default is 0.
     */
    public function booking_total_backward_compatible_without_insurance_arg(): void
    {
        $breakdown = $this->service->calculateBookingTotal(500.00, 50.00);

        $this->assertEquals(550.00, $breakdown['subtotal']);
        $this->assertArrayHasKey('total', $breakdown);
        $this->assertEquals(0.0, $breakdown['insurance_total']);
    }
}

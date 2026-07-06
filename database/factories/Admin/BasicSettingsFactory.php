<?php

namespace Database\Factories\Admin;

use App\Models\Admin\BasicSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * BasicSettings factory — provides the global app configuration row.
 *
 * BookingBalanceService::getTaxPercentage() calls BasicSettings::first().
 * Without a row in this table, the service falls back to 15% tax — but
 * tests that need a specific tax rate (e.g. 10%) must seed a row.
 *
 * Usage patterns:
 *   BasicSettings::factory()->create();                   // 15% tax enabled
 *   BasicSettings::factory()->noTax()->create();          // tax disabled
 *   BasicSettings::factory()->create(['tax_percentage' => 10.00]);
 */
class BasicSettingsFactory extends Factory
{
    protected $model = BasicSettings::class;

    public function definition(): array
    {
        return [
            'site_name'      => 'Dorra Alaseel Test',
            'tax_status'     => true,
            'tax_percentage' => 15.00,
        ];
    }

    /**
     * State: tax is disabled (percentage is irrelevant).
     *
     * Use when testing the "no-tax" code path in BookingBalanceService.
     */
    public function noTax(): static
    {
        return $this->state([
            'tax_status'     => false,
            'tax_percentage' => 0.00,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Admin\CancellationPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating CancellationPolicy test instances.
 *
 * Provides sensible defaults and named states for common
 * policy configurations used across test suites.
 */
class CancellationPolicyFactory extends Factory
{
    protected $model = CancellationPolicy::class;

    public function definition(): array
    {
        return [
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1.00,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10.00,
            'is_active'                 => true,
            'last_edit_by'              => null,
        ];
    }

    /**
     * Policy with no deduction (only service fee applies inside window).
     */
    public function noDeduction(): static
    {
        return $this->state([
            'deduction_type'  => 'none',
            'deduction_value' => 0.00,
        ]);
    }

    /**
     * Policy with fixed-amount deduction.
     */
    public function fixedDeduction(float $amount = 150.00): static
    {
        return $this->state([
            'deduction_type'  => 'fixed',
            'deduction_value' => $amount,
        ]);
    }

    /**
     * Policy with percentage-based deduction.
     */
    public function percentageDeduction(float $percent = 50.00): static
    {
        return $this->state([
            'deduction_type'  => 'percentage',
            'deduction_value' => $percent,
        ]);
    }

    /**
     * Policy with no service fee.
     */
    public function noServiceFee(): static
    {
        return $this->state([
            'service_fee_type'  => 'none',
            'service_fee_value' => 0.00,
        ]);
    }

    /**
     * Inactive (disabled) policy.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * Wide cancellation window — 24 hours.
     */
    public function wideWindow(): static
    {
        return $this->state(['cancellation_window_hours' => 24]);
    }
}

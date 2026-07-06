<?php

namespace App\Services;

use App\Constants\CancellationPolicyConst;
use App\DTO\CancellationRefundDTO;
use App\Models\Admin\CancellationPolicy;
use App\Models\CarBooking;
use Carbon\Carbon;
use RuntimeException;

/**
 * Calculates the refund amount for a booking cancellation
 * based on the global cancellation policy.
 *
 * Business Rules
 * ──────────────
 * 1. Fetch the single global CancellationPolicy record.
 * 2. Compute hours remaining until pickup.
 * 3. If hours_until_pickup >= cancellation_window_hours:
 *      → "outside window" — apply ONLY the service fee, no rental deduction.
 * 4. If hours_until_pickup < cancellation_window_hours:
 *      → "inside window"  — apply BOTH rental deduction AND service fee.
 * 5. refund = max(0, rental_amount − deduction − service_fee)
 *
 * The service is intentionally side-effect free — it only calculates.
 * Actual wallet credits are performed by the cancellation workflow.
 */
class CancellationRefundService
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Calculate the full refund breakdown for a booking cancellation.
     *
     * @throws RuntimeException When no active cancellation policy is configured.
     */
    public function calculate(CarBooking $booking): CancellationRefundDTO
    {
        $policy = CancellationPolicy::getActive();

        if ($policy === null) {
            throw new RuntimeException('No active cancellation policy found. Please configure one in the admin panel.');
        }

        $rentalAmount    = (float) $booking->amount;
        $hoursUntilPickup = $this->hoursUntilPickup($booking);
        $isWithinWindow  = $hoursUntilPickup < $policy->cancellation_window_hours;

        // Deduction only applies when cancellation is inside the window
        $deductionAmount = $isWithinWindow
            ? $this->calculateDeduction($policy, $booking, $rentalAmount)
            : 0.00;

        // Service fee always applies
        $serviceFeeAmount = $this->calculateServiceFee($policy, $rentalAmount);

        // Refund may never be negative
        $refundAmount = max(0.00, $rentalAmount - $deductionAmount - $serviceFeeAmount);

        return new CancellationRefundDTO(
            rental_amount: round($rentalAmount, 2),
            deduction_amount: round($deductionAmount, 2),
            service_fee_amount: round($serviceFeeAmount, 2),
            refund_amount: round($refundAmount, 2),
            is_within_window: $isWithinWindow,
            hours_until_pickup: round($hoursUntilPickup, 2),
            cancellation_window_hours: $policy->cancellation_window_hours,
        );
    }

    // -------------------------------------------------------------------------
    // Deduction Calculation
    // -------------------------------------------------------------------------

    /**
     * Calculate the rental deduction amount based on the policy's deduction type.
     *
     * Types:
     *   none       → 0
     *   fixed      → policy->deduction_value (fixed SAR)
     *   percentage → rental_amount × (deduction_value / 100)
     *   day        → (rental_amount / rental_days) × deduction_value
     *                uses the booking's daily rate at time of booking
     */
    public function calculateDeduction(
        CancellationPolicy $policy,
        CarBooking $booking,
        float $rentalAmount
    ): float {
        return match ($policy->deduction_type) {
            CancellationPolicyConst::DEDUCTION_NONE => 0.00,

            CancellationPolicyConst::DEDUCTION_FIXED => (float) $policy->deduction_value,

            CancellationPolicyConst::DEDUCTION_PERCENTAGE =>
            $rentalAmount * ((float) $policy->deduction_value / 100),

            CancellationPolicyConst::DEDUCTION_DAY =>
            $this->calculateDayDeduction($booking, $rentalAmount, (float) $policy->deduction_value),

            default => 0.00,
        };
    }

    /**
     * Calculate the service fee based on the policy's service fee type.
     *
     * Types:
     *   none       → 0
     *   fixed      → policy->service_fee_value (fixed SAR)
     *   percentage → rental_amount × (service_fee_value / 100)
     */
    public function calculateServiceFee(
        CancellationPolicy $policy,
        float $rentalAmount
    ): float {
        return match ($policy->service_fee_type) {
            CancellationPolicyConst::FEE_NONE => 0.00,

            CancellationPolicyConst::FEE_FIXED => (float) $policy->service_fee_value,

            CancellationPolicyConst::FEE_PERCENTAGE =>
            $rentalAmount * ((float) $policy->service_fee_value / 100),

            default => 0.00,
        };
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Calculate how many hours remain until the booking's pickup date/time.
     *
     * Returns a float (can be negative if pickup is in the past).
     */
    private function hoursUntilPickup(CarBooking $booking): float
    {
        // Combine pickup_date + pickup_time for full datetime precision
        $pickupDatetime = Carbon::parse(
            $booking->pickup_date . ' ' . ($booking->pickup_time ?? '00:00:00')
        );

        return now()->diffInMinutes($pickupDatetime, false) / 60.0;
    }

    /**
     * Deduction by rental days.
     *
     * Formula: daily_rate × days_to_deduct
     * daily_rate = total_rental_amount ÷ rental_days
     *
     * Guards against division-by-zero when rental_days is 0.
     * The deduction is capped at the full rental_amount.
     */
    private function calculateDayDeduction(
        CarBooking $booking,
        float $rentalAmount,
        float $daysToDeduct
    ): float {
        $rentalDays = (int) $booking->rental_days;

        if ($rentalDays <= 0 || $rentalAmount <= 0) {
            return 0.00;
        }

        $dailyRate = $rentalAmount / $rentalDays;
        $deduction = $dailyRate * $daysToDeduct;

        // Cap at rental amount so the combined deduction does not exceed 100% before clamping
        return min($deduction, $rentalAmount);
    }
}

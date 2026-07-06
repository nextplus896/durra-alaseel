<?php

namespace App\Constants;

/**
 * Constants for the global cancellation policy module.
 *
 * Deduction types control what is deducted from the rental amount
 * when a booking is cancelled inside the cancellation window.
 *
 * Service fee types control the fee always charged on cancellation
 * regardless of how early the customer cancels.
 */
class CancellationPolicyConst
{
    // -------------------------------------------------------------------------
    // Deduction Types (applied when cancellation is inside the window)
    // -------------------------------------------------------------------------

    /** No rental deduction — customer loses nothing from the rental amount */
    const DEDUCTION_NONE = 'none';

    /** Fixed SAR amount is deducted from the rental amount */
    const DEDUCTION_FIXED = 'fixed';

    /** Percentage of the total rental amount is deducted */
    const DEDUCTION_PERCENTAGE = 'percentage';

    /** One or more rental days are deducted (value = number of days) */
    const DEDUCTION_DAY = 'day';

    // -------------------------------------------------------------------------
    // Service Fee Types (always applied on any cancellation)
    // -------------------------------------------------------------------------

    /** No service fee */
    const FEE_NONE = 'none';

    /** Fixed SAR service fee */
    const FEE_FIXED = 'fixed';

    /** Percentage of total rental amount as service fee */
    const FEE_PERCENTAGE = 'percentage';

    // -------------------------------------------------------------------------
    // Arrays used for validation rules and admin UI dropdowns
    // -------------------------------------------------------------------------

    /**
     * All valid deduction type values.
     * Used in: validation in_array rules, Blade select options.
     */
    const DEDUCTION_TYPES = [
        self::DEDUCTION_NONE,
        self::DEDUCTION_FIXED,
        self::DEDUCTION_PERCENTAGE,
        self::DEDUCTION_DAY,
    ];

    /**
     * All valid service fee type values.
     * Used in: validation in_array rules, Blade select options.
     */
    const FEE_TYPES = [
        self::FEE_NONE,
        self::FEE_FIXED,
        self::FEE_PERCENTAGE,
    ];

    // -------------------------------------------------------------------------
    // Human-readable labels for admin form dropdowns
    // -------------------------------------------------------------------------

    const DEDUCTION_TYPE_LABELS = [
        self::DEDUCTION_NONE       => 'None',
        self::DEDUCTION_FIXED      => 'Fixed Amount',
        self::DEDUCTION_PERCENTAGE => 'Percentage',
        self::DEDUCTION_DAY        => 'Rental Days',
    ];

    const FEE_TYPE_LABELS = [
        self::FEE_NONE       => 'None',
        self::FEE_FIXED      => 'Fixed Amount',
        self::FEE_PERCENTAGE => 'Percentage',
    ];
}

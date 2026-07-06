<?php

namespace App\DTO;

/**
 * Immutable data transfer object for cancellation refund calculation results.
 *
 * All amounts are in SAR. The refund_amount is always >= 0 (never negative).
 * Produced by CancellationRefundService::calculate() and consumed by
 * API resources and the cancellation workflow.
 */
readonly class CancellationRefundDTO
{
    public function __construct(
        /** Original rental amount for the booking */
        public float $rental_amount,

        /** Deduction from the rental amount (0 when outside the window) */
        public float $deduction_amount,

        /** Service fee charged regardless of the cancellation window */
        public float $service_fee_amount,

        /** Final refund = rental_amount − deduction − service_fee (min 0) */
        public float $refund_amount,

        /**
         * True when the booking was cancelled at or after the free-cancel deadline
         * (i.e. remaining hours >= cancellation_window_hours).
         * Only the service fee is charged in this case.
         */
        public bool $is_within_window,

        /** Hours remaining before pickup at the time of calculation */
        public float $hours_until_pickup,

        /** The window threshold from the active policy */
        public int $cancellation_window_hours,
    ) {}
}

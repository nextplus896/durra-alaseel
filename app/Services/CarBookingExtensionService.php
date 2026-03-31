<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\CarBooking;
use App\Models\CarBookingTransaction;
use App\Models\Vendor\Cars\Car;
use App\Constants\CarBookingConst;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarBookingExtensionService
{
    public function __construct(
        protected BookingBalanceService $balanceService
    ) {}

    // -----------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------

    /**
     * Validate that this booking can be extended with the given number of days.
     *
     * @throws Exception
     */
    public function validateExtension(CarBooking $booking, int $extensionDays, int $authUserId): void
    {
        // Ownership
        if ((int) $booking->user_id !== $authUserId) {
            throw new Exception(__('You are not authorized to extend this booking.'));
        }

        // Status must be ONGOING
        if ((int) $booking->status !== CarBookingConst::STATUONGOING) {
            throw new Exception(__('Only ongoing bookings can be extended.'));
        }

        // Extension days range
        if ($extensionDays < 1 || $extensionDays > 90) {
            throw new Exception(__('Extension days must be between 1 and 90.'));
        }

        // Maximum total rental duration
        $newTotalDays = (int) $booking->rental_days + $extensionDays;
        if ($newTotalDays > 365) {
            throw new Exception(__('Total rental duration cannot exceed 365 days.'));
        }

        // Must extend at least 2 hours before current return date
        $returnDate = $booking->return_date
            ? Carbon::parse($booking->return_date)
            : $booking->calculateReturnDate();

        if ($returnDate->lessThanOrEqualTo(now()->addHours(2))) {
            throw new Exception(__('Extensions must be requested at least 2 hours before the return date.'));
        }
    }

    // -----------------------------------------------------------------
    // Availability
    // -----------------------------------------------------------------

    /**
     * Check whether the car is available for the extension window.
     *
     * @throws Exception when a conflict is detected
     */
    public function checkAvailability(CarBooking $booking, int $extensionDays): void
    {
        $currentReturnDate = $booking->return_date
            ? Carbon::parse($booking->return_date)->toDateString()
            : $booking->calculateReturnDate()->toDateString();

        $newReturnDate = Carbon::parse($currentReturnDate)
            ->addDays($extensionDays)
            ->toDateString();

        $hasConflict = CarBooking::hasConflictForExtension(
            (int) $booking->car_id,
            $currentReturnDate,
            $newReturnDate,
            (int) $booking->id
        );

        if ($hasConflict) {
            throw new Exception(__('The car is not available for the requested extension period. Another booking exists.'));
        }
    }

    // -----------------------------------------------------------------
    // Pricing
    // -----------------------------------------------------------------

    /**
     * Calculate the cost for the extension days using tiered pricing.
     */
    public function calculateExtensionCost(Car $car, int $extensionDays): array
    {
        $rentalFeeData  = $this->balanceService->calculateRentalFees($car, $extensionDays);
        $taxCalculation = $this->balanceService->calculateTax($rentalFeeData['rental_fees']);

        return [
            'extension_days'      => $extensionDays,
            'price_rule_applied'  => $rentalFeeData['price_rule_applied'],
            'base_price'          => (float) $rentalFeeData['base_price'],
            'rental_fees'         => (float) $rentalFeeData['rental_fees'],
            'tax_percentage'      => (float) $taxCalculation['tax_percentage'],
            'tax_amount'          => (float) $taxCalculation['tax_amount'],
            'total_cost'          => (float) $taxCalculation['total'],
        ];
    }

    // -----------------------------------------------------------------
    // Process
    // -----------------------------------------------------------------

    /**
     * Validate, check availability, and persist the extension — all in one transaction.
     * Payment type is cash: the extension cost will be collected on car return.
     *
     * @throws Exception
     */
    public function processExtension(CarBooking $booking, int $extensionDays, int $authUserId): CarBookingTransaction
    {
        $this->validateExtension($booking, $extensionDays, $authUserId);

        $car = $booking->cars()->firstOrFail();

        $costBreakdown = $this->calculateExtensionCost($car, $extensionDays);
        $totalCost     = $costBreakdown['total_cost'];

        $transaction = DB::transaction(function () use ($booking, $extensionDays, $costBreakdown, $totalCost, $authUserId) {
            // Re-lock the booking row to prevent race conditions
            $booking = CarBooking::lockForUpdate()->findOrFail($booking->id);

            // Re-validate status inside transaction
            if ((int) $booking->status !== CarBookingConst::STATUONGOING) {
                throw new Exception(__('Booking status changed. Extension is no longer possible.'));
            }

            // Re-check availability inside transaction (with lock)
            $this->checkAvailability($booking, $extensionDays);

            // Resolve dates
            $previousReturnDate = $booking->return_date
                ? Carbon::parse($booking->return_date)
                : $booking->calculateReturnDate();

            $newReturnDate = $previousReturnDate->copy()->addDays($extensionDays);

            // Retrieve parent rental transaction to copy daily_rate
            $parentRentalTrx = CarBookingTransaction::where('car_booking_id', $booking->id)
                ->where('type', CarBookingTransaction::TYPE_RENTAL)
                ->first();

            // Create the extension transaction directly (no separate extension record)
            $transaction = CarBookingTransaction::create([
                'car_booking_id'     => $booking->id,
                'type'               => CarBookingTransaction::TYPE_EXTENSION,
                'description'        => "+{$extensionDays} days extension",
                'amount'             => (float) $costBreakdown['rental_fees'],
                'tax_percentage'     => (float) $costBreakdown['tax_percentage'],
                'tax_amount'         => (float) $costBreakdown['tax_amount'],
                'total'              => (float) $totalCost,
                'daily_rate'         => $parentRentalTrx?->daily_rate,
                'extension_days'     => $extensionDays,
                'previous_return_date' => $previousReturnDate->toDateString(),
                'new_return_date'    => $newReturnDate->toDateString(),
                'transacted_at'      => now(),
            ]);

            // Update the parent booking
            $booking->update([
                'rental_days'          => (int) $booking->rental_days + $extensionDays,
                'return_date'          => $newReturnDate->toDateString(),
                'extension_count'      => (int) $booking->extension_count + 1,
                'total_extension_days' => (int) $booking->total_extension_days + $extensionDays,
                'total_amount'         => (float) $booking->total_amount + $totalCost,
            ]);

            return $transaction;
        });

        return $transaction;
    }
}

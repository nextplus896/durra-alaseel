<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\CarBooking;
use App\Models\BalanceTransaction;
use App\Models\Admin\BasicSettings;
use App\Models\Vendor\Cars\Car;
use App\DTO\WalletTransactionDTO;

class BookingBalanceService
{
    protected WalletService $walletService;

    public function __construct(?WalletService $walletService = null)
    {
        $this->walletService = $walletService ?? app(WalletService::class);
    }
    /**
     * Calculate rental fees based on tiered pricing (daily/weekly/monthly)
     * Golden Rule: Price is decided ONLY by rental_days
     */
    public function calculateRentalFees(Car $car, int $rentalDays): array
    {
        $priceRule = '';
        $basePrice = 0;

        if ($rentalDays <= 7) {
            // Daily pricing (1-7 days)
            $basePrice = $car->price_per_day;
            $priceRule = 'daily';
        } elseif ($rentalDays <= 30) {
            // Weekly pricing (8-30 days)
            $basePrice = $car->price_per_week;
            $priceRule = 'weekly';
        } else {
            // Monthly pricing (31+ days)
            $basePrice = $car->price_per_month;
            $priceRule = 'monthly';
        }

        $rentalFees = round($rentalDays * $basePrice, 2);

        return [
            'rental_fees' => $rentalFees,
            'price_rule_applied' => $priceRule,
            'base_price' => $basePrice,
            'rental_days' => $rentalDays,
        ];
    }

    /**
     * Get current active tax percentage
     */
    public function getTaxPercentage(): float
    {
        $basicSettings = BasicSettings::first();
        if ($basicSettings && $basicSettings->tax_status) {
            return floatval($basicSettings->tax_percentage);
        }
        return 15.00;
    }

    /**
     * Calculate tax amount for a given subtotal
     */
    public function calculateTax(float $subtotal): array
    {
        $taxPercentage = $this->getTaxPercentage();
        $taxAmount = ($subtotal * $taxPercentage) / 100;

        return [
            'subtotal' => $subtotal,
            'tax_percentage' => $taxPercentage,
            'tax_amount' => round($taxAmount, 2),
            'total' => round($subtotal + $taxAmount, 2),
        ];
    }

    /**
     * Calculate insurance amounts based on the customer's chosen insurance type.
     *
     * @param  \App\Models\Vendor\Cars\Car  $car
     * @param  int    $rentalDays
     * @param  string $insuranceType  'daily' or 'deductible'
     * @return array{insurance_type: string, daily_insurance: float, insurance_total: float, deductible_insurance: float}
     */
    public function calculateInsuranceTotal(Car $car, int $rentalDays, string $insuranceType): array
    {
        if ($insuranceType === 'daily') {
            $dailyInsurance   = (float) ($car->daily_insurance ?? 0);
            $insuranceTotal   = round($dailyInsurance * $rentalDays, 2);
            $deductible       = 0.0;
        } else {
            // 'deductible' — displayed as liability only, never charged
            $dailyInsurance   = 0.0;
            $insuranceTotal   = 0.0;
            $deductible       = round((float) ($car->deductible_insurance ?? 0), 2);
        }

        return [
            'insurance_type'       => $insuranceType,
            'daily_insurance'      => $dailyInsurance,
            'insurance_total'      => $insuranceTotal,
            'deductible_insurance' => $deductible,
        ];
    }

    /**
     * Calculate full booking amount including fees, delivery, tax, and insurance.
     *
     * The $insuranceTotal parameter defaults to 0 for backward compatibility
     * with existing callers that do not yet pass insurance data.
     */
    public function calculateBookingTotal(float $rentalFees, float $deliveryPrice = 0, float $charges = 0, float $insuranceTotal = 0): array
    {
        $subtotal = $rentalFees + $deliveryPrice + $charges + $insuranceTotal;
        $taxCalculation = $this->calculateTax($subtotal);

        return [
            'rental_fees'    => $rentalFees,
            'delivery_price' => $deliveryPrice,
            'charges'        => $charges,
            'insurance_total'=> $insuranceTotal,
            'subtotal'       => $subtotal,
            'tax_percentage' => $taxCalculation['tax_percentage'],
            'tax_amount'     => $taxCalculation['tax_amount'],
            'total'          => $taxCalculation['total'],
        ];
    }

    /**
     * Check if user has sufficient balance for a booking
     */
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        return $user->balance >= $amount;
    }

    /**
     * Deduct balance from user account for a booking
     * @throws Exception
     */
    public function deductBalanceForBooking(User $user, CarBooking $booking, float $amount): BalanceTransaction
    {
        $dto = WalletTransactionDTO::forWithdrawal(
            amount: $amount,
            description: __('Payment successfully: :amount SAR for booking #:trx', ['amount' => number_format($amount, 2), 'trx' => $booking->trip_id], 'ar'),
            bookingId: $booking->id,
            idempotencyKey: 'booking-deduct-' . $booking->id,
        );

        return $this->walletService->withdraw($user, $amount, $dto);
    }

    /**
     * Deduct balance from user account for a booking extension.
     * Uses a unique idempotency key per extension attempt (timestamp-based).
     * @throws Exception
     */
    public function deductBalanceForExtension(User $user, CarBooking $booking, float $amount, int $extensionDays = 0): BalanceTransaction
    {
        $arabicDays = match (true) {
            $extensionDays === 1                                      => 'يوم واحد',
            $extensionDays === 2                                      => 'يومَين',
            $extensionDays >= 3 && $extensionDays <= 10              => $extensionDays . ' أيام',
            default                                                   => $extensionDays . ' يوماً',
        };

        $dto = WalletTransactionDTO::forWithdrawal(
            amount: $amount,
            description: __('Booking #:trx has been extended by :days', ['trx' => $booking->trip_id, 'days' => $arabicDays], 'ar'),
            bookingId: $booking->id,
            idempotencyKey: 'extension-deduct-' . $booking->id . '-' . now()->timestamp,
        );

        return $this->walletService->withdraw($user, $amount, $dto);
    }

    /**
     * Refund balance to user account
     * @throws Exception
     */
    public function refundBalance(User $user, CarBooking $booking, float $amount, string $reason = ''): BalanceTransaction
    {
        $dto = WalletTransactionDTO::forRefund(
            amount: $amount,
            description: __(':amount SAR has been refunded for booking #:trx due to :reason', [
                'amount' => number_format($amount, 2),
                'trx'    => $booking->trip_id,
                'reason' => $reason ?: __('cancellation', [], 'ar'),
            ], 'ar'),
            referenceType: 'App\\Models\\CarBooking',
            referenceId: $booking->id,
            bookingId: $booking->id,
            idempotencyKey: 'booking-refund-' . $booking->id,
        );

        return $this->walletService->refund($user, $amount, $dto);
    }

    /**
     * Update booking with tax and balance payment information
     */
    public function updateBookingWithPaymentDetails(CarBooking $booking, array $paymentDetails): CarBooking
    {
        $booking->update([
            'tax_percentage' => $paymentDetails['tax_percentage'] ?? $this->getTaxPercentage(),
            'tax_amount' => $paymentDetails['tax_amount'] ?? 0,
            'delivery_price' => $paymentDetails['delivery_price'] ?? 0,
            'subtotal' => $paymentDetails['subtotal'] ?? $booking->amount,
            'total_amount' => $paymentDetails['total'] ?? $booking->amount,
            'paid_from_balance' => $paymentDetails['paid_from_balance'] ?? false,
            'balance_deducted' => $paymentDetails['balance_deducted'] ?? 0,
        ]);

        return $booking->fresh();
    }
}

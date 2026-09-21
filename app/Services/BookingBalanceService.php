<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\CarBooking;
use App\Models\BalanceTransaction;
use App\Models\Admin\BasicSettings;
use App\Models\Vendor\Cars\Car;
use App\DTO\WalletTransactionDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        // Cast to float explicitly — decimal fields can sometimes behave unexpectedly
        $dailyPrice   = (float) ($car->price_per_day ?? 0);
        $weeklyPrice  = (float) ($car->price_per_week ?? 0);
        $monthlyPrice = (float) ($car->price_per_month ?? 0);

        Log::info('calculateRentalFees', [
            'car_id'          => $car->id,
            'rental_days'     => $rentalDays,
            'price_per_day'   => $dailyPrice,
            'price_per_week'  => $weeklyPrice,
            'price_per_month' => $monthlyPrice,
        ]);

        if ($rentalDays <= 7) {
            // Daily pricing (1-7 days)
            $basePrice = $dailyPrice;
            $priceRule = 'daily';
        } elseif ($rentalDays <= 30) {
            // Weekly pricing (8-30 days) — fallback to daily if weekly price not set
            if ($weeklyPrice > 0) {
                $basePrice = $weeklyPrice;
                $priceRule = 'weekly';
            } else {
                $basePrice = $dailyPrice;
                $priceRule = 'daily_fallback_weekly_missing';
                Log::warning('calculateRentalFees: price_per_week is 0, falling back to daily', [
                    'car_id' => $car->id,
                    'rental_days' => $rentalDays,
                ]);
            }
        } else {
            // Monthly pricing (31+ days) — cascade fallback: monthly → weekly → daily
            if ($monthlyPrice > 0) {
                $basePrice = $monthlyPrice;
                $priceRule = 'monthly';
            } elseif ($weeklyPrice > 0) {
                $basePrice = $weeklyPrice;
                $priceRule = 'weekly_fallback_monthly_missing';
                Log::warning('calculateRentalFees: price_per_month is 0, falling back to weekly', [
                    'car_id' => $car->id,
                    'rental_days' => $rentalDays,
                ]);
            } else {
                $basePrice = $dailyPrice;
                $priceRule = 'daily_fallback_both_missing';
                Log::warning('calculateRentalFees: price_per_week AND price_per_month are 0, falling back to daily', [
                    'car_id' => $car->id,
                    'rental_days' => $rentalDays,
                ]);
            }
        }

        $rentalFees = round($rentalDays * $basePrice, 2);

        return [
            'rental_fees'       => $rentalFees,
            'price_rule_applied' => $priceRule,
            'base_price'        => $basePrice,
            'rental_days'       => $rentalDays,
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
            'insurance_total' => $insuranceTotal,
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
            idempotencyKey: 'extension-deduct-' . $booking->id . '-' . Str::uuid()->toString(),
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

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
     * Calculate full booking amount including fees, delivery, and tax
     */
    public function calculateBookingTotal(float $rentalFees, float $deliveryPrice = 0, float $charges = 0): array
    {
        $subtotal = $rentalFees + $deliveryPrice + $charges;
        $taxCalculation = $this->calculateTax($subtotal);

        return [
            'rental_fees' => $rentalFees,
            'delivery_price' => $deliveryPrice,
            'charges' => $charges,
            'subtotal' => $subtotal,
            'tax_percentage' => $taxCalculation['tax_percentage'],
            'tax_amount' => $taxCalculation['tax_amount'],
            'total' => $taxCalculation['total'],
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
            description: __('Booking payment') . ' - ' . $booking->trx_id,
            bookingId: $booking->id,
            idempotencyKey: 'booking-deduct-' . $booking->id,
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
            description: $reason ?: __('Booking refund') . ' - ' . $booking->trx_id,
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

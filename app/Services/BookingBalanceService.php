<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\CarBooking;
use App\Models\BalanceTransaction;
use App\Models\Admin\TaxSetting;
use Illuminate\Support\Facades\DB;

class BookingBalanceService
{
    /**
     * Get current active tax percentage
     */
    public function getTaxPercentage(): float
    {
        $taxSetting = TaxSetting::where('status', true)->first();
        return $taxSetting ? floatval($taxSetting->percentage) : 15.00;
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
        if (!$this->hasSufficientBalance($user, $amount)) {
            throw new Exception(__('Insufficient balance. Please recharge your account.'));
        }

        $balanceBefore = $user->balance;
        $balanceAfter = $balanceBefore - $amount;

        DB::beginTransaction();
        try {
            // Update user balance
            $user->update(['balance' => $balanceAfter]);

            // Create balance transaction record
            $transaction = BalanceTransaction::create([
                'user_id' => $user->id,
                'type' => 'booking_deduction',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'booking_id' => $booking->id,
                'description' => __('Booking payment') . ' - ' . $booking->trx_id,
                'payment_gateway' => 'balance',
                'reference' => $booking->trx_id,
            ]);

            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Refund balance to user account
     * @throws Exception
     */
    public function refundBalance(User $user, CarBooking $booking, float $amount, string $reason = ''): BalanceTransaction
    {
        $balanceBefore = $user->balance;
        $balanceAfter = $balanceBefore + $amount;

        DB::beginTransaction();
        try {
            // Update user balance
            $user->update(['balance' => $balanceAfter]);

            // Create balance transaction record
            $transaction = BalanceTransaction::create([
                'user_id' => $user->id,
                'type' => 'refund',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'booking_id' => $booking->id,
                'description' => $reason ?: __('Booking refund') . ' - ' . $booking->trx_id,
                'payment_gateway' => 'balance',
                'reference' => 'REFUND-' . $booking->trx_id,
            ]);

            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

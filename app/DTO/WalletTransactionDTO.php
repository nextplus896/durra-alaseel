<?php

namespace App\DTO;

/**
 * Data Transfer Object for Wallet Transactions.
 *
 * Provides a clean, type-safe way to pass transaction data
 * through the WalletService → WalletRepository pipeline.
 */
class WalletTransactionDTO
{
    public function __construct(
        public readonly string  $type,
        public readonly float   $amount,
        public readonly string  $description,
        public readonly ?string $referenceType = null,
        public readonly ?int    $referenceId = null,
        public readonly ?int    $bookingId = null,
        public readonly ?string $paymentMethod = 'balance',
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $moyasarInvoiceId = null,
    ) {}

    /**
     * Build a DTO for wallet deposit (top-up / recharge).
     */
    public static function forDeposit(
        float   $amount,
        string  $description,
        ?string $referenceType = null,
        ?int    $referenceId = null,
        ?string $idempotencyKey = null,
        ?string $moyasarInvoiceId = null,
    ): self {
        return new self(
            type: 'recharge',
            amount: $amount,
            description: $description,
            referenceType: $referenceType,
            referenceId: $referenceId,
            paymentMethod: 'moyasar',
            idempotencyKey: $idempotencyKey,
            moyasarInvoiceId: $moyasarInvoiceId,
        );
    }

    /**
     * Build a DTO for wallet withdrawal (booking deduction).
     */
    public static function forWithdrawal(
        float  $amount,
        string $description,
        int    $bookingId,
        ?string $idempotencyKey = null,
    ): self {
        return new self(
            type: 'booking_deduction',
            amount: $amount,
            description: $description,
            referenceType: 'App\\Models\\CarBooking',
            referenceId: $bookingId,
            bookingId: $bookingId,
            paymentMethod: 'balance',
            idempotencyKey: $idempotencyKey,
        );
    }

    /**
     * Build a DTO for wallet refund (booking cancel / reject).
     */
    public static function forRefund(
        float   $amount,
        string  $description,
        ?string $referenceType = null,
        ?int    $referenceId = null,
        ?int    $bookingId = null,
        ?string $idempotencyKey = null,
    ): self {
        return new self(
            type: 'refund',
            amount: $amount,
            description: $description,
            referenceType: $referenceType,
            referenceId: $referenceId,
            bookingId: $bookingId,
            paymentMethod: 'balance',
            idempotencyKey: $idempotencyKey,
        );
    }
}

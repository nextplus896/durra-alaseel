<?php

namespace Tests\Unit\DTO;

use App\DTO\WalletTransactionDTO;
use Tests\TestCase;

/**
 * Unit tests for WalletTransactionDTO.
 *
 * What we cover:
 *   1. forDeposit()    — sets correct type, payment method, idempotency key
 *   2. forWithdrawal() — sets correct type, booking/reference IDs
 *   3. forRefund()     — sets correct type and booking ID
 *   4. Immutability    — readonly properties cannot be reassigned
 *
 * Why a DTO test?
 * WalletService is wired to accept only a typed WalletTransactionDTO.
 * The factory methods are the public API that the rest of the codebase uses.
 * A wrong type string or payment method would silently create wrong ledger rows.
 * Testing the factories ensures callers get predictable, correct DTOs.
 *
 * Note: No RefreshDatabase — no DB reads/writes happen here.
 */
class WalletTransactionDTOTest extends TestCase
{
    // =========================================================================
    // forDeposit() — wallet top-up via Moyasar
    // =========================================================================

    /**
     * @test
     * forDeposit() creates a DTO with type='recharge' and paymentMethod='moyasar'.
     *
     * Example: User pays via Moyasar credit card → webhook → forDeposit() called.
     * The ledger row type must be 'recharge' so the history screen shows "Top-up".
     * The payment method must be 'moyasar' for reporting and reconciliation.
     */
    public function for_deposit_sets_recharge_type_and_moyasar_payment_method(): void
    {
        $dto = WalletTransactionDTO::forDeposit(
            amount: 100.00,
            description: 'Wallet top-up via Moyasar',
            idempotencyKey: 'moyasar-inv-abc123',
        );

        $this->assertEquals('recharge', $dto->type,
            'Deposits must use type=recharge so they appear correctly in transaction history');
        $this->assertEquals(100.00, $dto->amount);
        $this->assertEquals('moyasar', $dto->paymentMethod,
            'Deposits always originate from the Moyasar gateway');
        $this->assertEquals('moyasar-inv-abc123', $dto->idempotencyKey,
            'Idempotency key must pass through to the DTO unchanged');
    }

    /**
     * @test
     * forDeposit() with a moyasarInvoiceId stores it in the DTO.
     *
     * Why: The invoice ID links our BalanceTransaction back to the Moyasar
     * invoice for reconciliation. Without it, we cannot match refunds or
     * chargebacks back to the correct payment.
     */
    public function for_deposit_stores_moyasar_invoice_id(): void
    {
        $dto = WalletTransactionDTO::forDeposit(
            amount: 500.00,
            description: 'Top-up',
            moyasarInvoiceId: 'inv_12345',
        );

        $this->assertEquals('inv_12345', $dto->moyasarInvoiceId);
    }

    // =========================================================================
    // forWithdrawal() — balance deducted for a car booking
    // =========================================================================

    /**
     * @test
     * forWithdrawal() creates a DTO with type='booking_deduction',
     * paymentMethod='balance', and wires the booking ID into both
     * bookingId and referenceId.
     *
     * Example: User confirms car booking #42 and pays from their wallet.
     * The DTO must link back to booking 42 so the balance_transactions
     * row can be joined to the booking for admin reports and audit trails.
     */
    public function for_withdrawal_sets_booking_deduction_type_with_booking_link(): void
    {
        $dto = WalletTransactionDTO::forWithdrawal(
            amount: 200.00,
            description: 'Payment for booking #TRX123',
            bookingId: 42,
            idempotencyKey: 'booking-deduct-42',
        );

        $this->assertEquals('booking_deduction', $dto->type,
            'Booking payments must use type=booking_deduction');
        $this->assertEquals(42, $dto->bookingId,
            'bookingId must match so we can join to car_bookings');
        $this->assertEquals('balance', $dto->paymentMethod,
            'Wallet deductions always use paymentMethod=balance');
        $this->assertEquals('App\\Models\\CarBooking', $dto->referenceType,
            'referenceType must be the CarBooking FQCN for polymorphic lookups');
        $this->assertEquals(42, $dto->referenceId,
            'referenceId must match the booking ID for polymorphic queries');
    }

    // =========================================================================
    // forRefund() — money returned to user wallet
    // =========================================================================

    /**
     * @test
     * forRefund() creates a DTO with type='refund' and paymentMethod='balance'.
     *
     * Example: Vendor rejects booking #7, triggering an automatic refund.
     * The DTO type 'refund' ensures the transaction history shows a credit,
     * and paymentMethod='balance' means the money goes back to the wallet.
     */
    public function for_refund_sets_refund_type_with_correct_fields(): void
    {
        $dto = WalletTransactionDTO::forRefund(
            amount: 150.00,
            description: 'Refund for cancelled booking',
            bookingId: 7,
        );

        $this->assertEquals('refund', $dto->type,
            'Refunds must use type=refund so history shows a credit entry');
        $this->assertEquals('balance', $dto->paymentMethod,
            'Refunds credit the wallet, so paymentMethod=balance');
        $this->assertEquals(7, $dto->bookingId);
    }

    // =========================================================================
    // Immutability — PHP 8.1 readonly properties
    // =========================================================================

    /**
     * @test
     * DTO properties are readonly — cannot be changed after construction.
     *
     * Why: A DTO is a value object. If properties could be mutated after
     * creation, service code could silently change the type or amount mid-flight
     * (e.g., inside a DB transaction), leading to inconsistent ledger entries.
     *
     * PHP 8.1 enforces this with the `readonly` keyword, which throws \Error
     * on any write attempt.
     */
    public function dto_properties_are_readonly_after_construction(): void
    {
        $dto = new WalletTransactionDTO(
            type: 'recharge',
            amount: 50.00,
            description: 'Test',
        );

        $this->expectException(\Error::class);
        // @phpstan-ignore-line — intentional write to readonly property for test
        $dto->type = 'hacked';
    }
}

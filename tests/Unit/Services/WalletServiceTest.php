<?php

namespace Tests\Unit\Services;

use App\DTO\WalletTransactionDTO;
use App\Models\BalanceTransaction;
use App\Services\WalletService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Unit tests for WalletService.
 *
 * What we cover:
 *   1. deposit()  — increases balance, creates ledger row, guards zero/negative amounts
 *   2. withdraw() — decreases balance, guards zero/negative/insufficient amounts
 *   3. refund()   — increases balance (same as deposit but different type)
 *   4. Idempotency — same key used twice only processes once
 *   5. getBalance() — reads from UserWallet via User model
 *
 * Why "unit"? Each test isolates a single service method and its DB effects.
 * No HTTP, no auth, no routing — pure business logic verification.
 *
 * Setup: every test gets a fresh user+wallet via createUserWithWallet(),
 * which also creates the default SAR currency that WalletService needs.
 */
class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Fake notifications so wallet charge/deduct emails don't hit real SMTP.
        Notification::fake();
        $this->service = app(WalletService::class);
    }

    // =========================================================================
    // Helper — build a typed DTO quickly
    // =========================================================================

    private function dto(string $type, float $amount, ?string $key = null): WalletTransactionDTO
    {
        return new WalletTransactionDTO(
            type: $type,
            amount: $amount,
            description: 'Test transaction',
            idempotencyKey: $key,
        );
    }

    // =========================================================================
    // Section 1: deposit()
    // =========================================================================

    /**
     * @test
     * Depositing 200 SAR into a wallet that has 100 SAR results in 300 SAR.
     *
     * Example: User tops up their wallet via Moyasar payment gateway.
     * The service finds the default-currency wallet, locks it, adds the amount.
     */
    public function deposit_increases_wallet_balance(): void
    {
        $user = $this->createUserWithWallet(100.00);

        $this->service->deposit($user, 200.00, $this->dto('recharge', 200.00));

        $this->assertEquals(300.00, $user->fresh()->balance,
            'Balance should be 100 + 200 = 300 SAR after deposit');
    }

    /**
     * @test
     * Every deposit creates an entry in the balance_transactions ledger.
     *
     * Why: Financial integrity requires a complete audit trail. Every money
     * movement must have a transaction record with before/after balances.
     */
    public function deposit_creates_balance_transaction_record(): void
    {
        $user = $this->createUserWithWallet(0.00);

        $this->service->deposit($user, 50.00, $this->dto('recharge', 50.00));

        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $user->id,
            'type'    => 'recharge',
            'status'  => BalanceTransaction::STATUS_SUCCESS,
        ]);
    }

    /**
     * @test
     * Depositing 0 SAR throws an exception immediately — no DB writes happen.
     *
     * Why: A zero-amount deposit is almost certainly a programming error.
     * Guard early so callers get a clear error instead of a silent no-op.
     */
    public function deposit_throws_for_zero_amount(): void
    {
        $user = $this->createUserWithWallet(100.00);

        $this->expectException(Exception::class);
        $this->service->deposit($user, 0.00, $this->dto('recharge', 0.00));
    }

    /**
     * @test
     * Depositing a negative amount throws immediately.
     *
     * Example: A code bug accidentally passes -50 (e.g., a refund sent to the
     * wrong service method). The guard prevents a balance decrease disguised
     * as a deposit.
     */
    public function deposit_throws_for_negative_amount(): void
    {
        $user = $this->createUserWithWallet(100.00);

        $this->expectException(Exception::class);
        $this->service->deposit($user, -50.00, $this->dto('recharge', -50.00));
    }

    /**
     * @test
     * Calling deposit twice with the same idempotency key only charges once.
     *
     * Why: Moyasar or other gateways can send duplicate webhooks on timeout
     * or network retry. Idempotency ensures the user is only charged once
     * even if our webhook handler runs twice.
     *
     * Example: Moyasar fires a payment_paid webhook. Our handler processes it
     * and deposits 100 SAR. Moyasar retries (timeout). Second call finds the
     * existing transaction by key and returns early — balance stays at 100.
     */
    public function deposit_is_idempotent_with_same_key(): void
    {
        $user = $this->createUserWithWallet(0.00);
        $dto  = $this->dto('recharge', 100.00, 'key-abc-001');

        $this->service->deposit($user, 100.00, $dto);
        $this->service->deposit($user, 100.00, $dto); // duplicate webhook

        $this->assertEquals(100.00, $user->fresh()->balance,
            'Balance should only increase once despite two identical calls');
        $this->assertDatabaseCount('balance_transactions', 1);
    }

    // =========================================================================
    // Section 2: withdraw()
    // =========================================================================

    /**
     * @test
     * Withdrawing 200 SAR from a 500 SAR wallet leaves 300 SAR.
     *
     * Example: User pays for a 3-day car rental (500 SAR) with their wallet.
     * The service deducts 200 SAR for the booking fee component.
     */
    public function withdraw_decreases_wallet_balance(): void
    {
        $user = $this->createUserWithWallet(500.00);

        $this->service->withdraw($user, 200.00, $this->dto('booking_deduction', 200.00));

        $this->assertEquals(300.00, $user->fresh()->balance,
            'Balance should be 500 - 200 = 300 SAR after withdrawal');
    }

    /**
     * @test
     * Withdrawal creates a ledger row with type=booking_deduction.
     *
     * This record is what BookingBalanceService uses to link a deduction
     * back to a specific booking (via booking_id on the DTO).
     */
    public function withdraw_creates_balance_transaction_record(): void
    {
        $user = $this->createUserWithWallet(500.00);

        $this->service->withdraw($user, 150.00, $this->dto('booking_deduction', 150.00));

        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $user->id,
            'type'    => 'booking_deduction',
            'status'  => BalanceTransaction::STATUS_SUCCESS,
        ]);
    }

    /**
     * @test
     * Withdrawing more than the available balance throws an exception.
     *
     * Example: User has 50 SAR but tries to pay for a 200 SAR booking.
     * The exception message ("Insufficient balance") is shown in the app.
     * No balance change should occur — DB write is inside a transaction.
     */
    public function withdraw_throws_when_balance_is_insufficient(): void
    {
        $user = $this->createUserWithWallet(50.00);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient balance/i');

        $this->service->withdraw($user, 200.00, $this->dto('booking_deduction', 200.00));
    }

    /**
     * @test
     * Withdrawing 0 SAR throws — same guard as deposit.
     */
    public function withdraw_throws_for_zero_amount(): void
    {
        $user = $this->createUserWithWallet(100.00);

        $this->expectException(Exception::class);
        $this->service->withdraw($user, 0.00, $this->dto('booking_deduction', 0.00));
    }

    /**
     * @test
     * Withdraw is idempotent — second call with same key is a no-op.
     *
     * Example: BookingController deducts balance, then an exception occurs.
     * A retry mechanism calls withdraw again with the same booking key.
     * The idempotency guard finds the existing transaction and returns it
     * without deducting twice.
     */
    public function withdraw_is_idempotent_with_same_key(): void
    {
        $user = $this->createUserWithWallet(500.00);
        $dto  = $this->dto('booking_deduction', 100.00, 'booking-deduct-42');

        $this->service->withdraw($user, 100.00, $dto);
        $this->service->withdraw($user, 100.00, $dto); // retry

        $this->assertEquals(400.00, $user->fresh()->balance,
            'Only one deduction should occur despite two calls');
        $this->assertDatabaseCount('balance_transactions', 1);
    }

    // =========================================================================
    // Section 3: refund()
    // =========================================================================

    /**
     * @test
     * Refunding 50 SAR to a wallet with 100 SAR results in 150 SAR.
     *
     * Example: Vendor rejects a pending booking. The system refunds the
     * booking fee to the user's wallet automatically.
     */
    public function refund_increases_wallet_balance(): void
    {
        $user = $this->createUserWithWallet(100.00);

        $this->service->refund($user, 50.00, $this->dto('refund', 50.00));

        $this->assertEquals(150.00, $user->fresh()->balance,
            'Balance should be 100 + 50 = 150 SAR after refund');
    }

    /**
     * @test
     * Refunding 0 SAR throws — same guard as deposit/withdraw.
     */
    public function refund_throws_for_zero_amount(): void
    {
        $user = $this->createUserWithWallet(100.00);

        $this->expectException(Exception::class);
        $this->service->refund($user, 0.00, $this->dto('refund', 0.00));
    }

    /**
     * @test
     * Refund is idempotent — second call with same key is a no-op.
     *
     * Example: A cancellation webhook fires twice. Only one refund should
     * reach the user's wallet.
     */
    public function refund_is_idempotent_with_same_key(): void
    {
        $user = $this->createUserWithWallet(0.00);
        $dto  = $this->dto('refund', 75.00, 'booking-refund-99');

        $this->service->refund($user, 75.00, $dto);
        $this->service->refund($user, 75.00, $dto); // duplicate

        $this->assertEquals(75.00, $user->fresh()->balance,
            'Only one refund should credit the wallet');
        $this->assertDatabaseCount('balance_transactions', 1);
    }

    // =========================================================================
    // Section 4: getBalance()
    // =========================================================================

    /**
     * @test
     * getBalance() reads the current wallet balance through the service layer.
     *
     * This is a thin wrapper around User::getBalanceAttribute() but it's
     * worth testing to confirm the service delegates correctly and the
     * wallet-currency join works as expected in test conditions.
     */
    public function get_balance_returns_current_wallet_balance(): void
    {
        $user = $this->createUserWithWallet(250.00);

        $this->assertEquals(250.00, $this->service->getBalance($user),
            'getBalance() should return 250 SAR from the default-currency wallet');
    }
}

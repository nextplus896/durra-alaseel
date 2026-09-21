<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\BalanceTransaction;
use App\DTO\WalletTransactionDTO;
use App\Repositories\WalletRepository;
use App\Notifications\User\WalletChargedNotification;
use App\Notifications\User\WalletDeductedNotification;
use App\Notifications\User\WalletRefundNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    protected WalletRepository $repository;

    public function __construct(WalletRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Deposit funds into user wallet (top-up / recharge).
     *
     * @throws Exception
     */
    public function deposit(User $user, float $amount, WalletTransactionDTO $dto): BalanceTransaction
    {
        if ($amount <= 0) {
            throw new Exception(__('Deposit amount must be greater than zero.'));
        }

        // Idempotency guard — skip if already processed
        if ($dto->idempotencyKey) {
            $existing = $this->repository->findByIdempotencyKey($dto->idempotencyKey);
            if ($existing) {
                Log::info('WalletService::deposit — idempotent skip', [
                    'idempotency_key' => $dto->idempotencyKey,
                    'trx_id' => $existing->trx_id,
                ]);
                return $existing;
            }
        }

        return DB::transaction(function () use ($user, $amount, $dto) {
            return $this->creditWallet($user, $amount, $dto, WalletChargedNotification::class);
        });
    }

    /**
     * Withdraw funds from user wallet (booking deduction).
     *
     * @throws Exception
     */
    public function withdraw(User $user, float $amount, WalletTransactionDTO $dto): BalanceTransaction
    {
        if ($amount <= 0) {
            throw new Exception(__('Withdrawal amount must be greater than zero.'));
        }

        // Idempotency guard
        if ($dto->idempotencyKey) {
            $existing = $this->repository->findByIdempotencyKey($dto->idempotencyKey);
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($user, $amount, $dto) {
            // Lock the wallet row for the default currency to prevent concurrent updates
            $wallet = UserWallet::lockForUpdate()
                ->where('user_id', $user->id)
                ->whereHas('currency', fn($q) => $q->where('default', true))
                ->firstOrFail();

            $balanceBefore = (string) $wallet->balance;

            // bccomp returns 0 if equal, 1 if left > right, -1 if left < right
            if (bccomp($balanceBefore, (string) $amount, 8) < 0) {
                throw new Exception(__('Insufficient balance. Please recharge your account.'));
            }

            $balanceAfter = bcsub($balanceBefore, (string) $amount, 8);
            $wallet->update(['balance' => $balanceAfter]);

            $transaction = $this->repository->createTransaction($user, $dto, $balanceBefore, $balanceAfter);

            try {
                $user->notify((new WalletDeductedNotification($amount, (float) $balanceAfter, $dto->description))->locale('ar'));
            } catch (\Throwable $e) {
                Log::warning('WalletService: Deduction notification failed', ['error' => $e->getMessage()]);
            }

            return $transaction;
        });
    }

    /**
     * Refund funds to user wallet.
     *
     * @throws Exception
     */
    public function refund(User $user, float $amount, WalletTransactionDTO $dto): BalanceTransaction
    {
        if ($amount <= 0) {
            throw new Exception(__('Refund amount must be greater than zero.'));
        }

        // Idempotency guard
        if ($dto->idempotencyKey) {
            $existing = $this->repository->findByIdempotencyKey($dto->idempotencyKey);
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($user, $amount, $dto) {
            return $this->creditWallet($user, $amount, $dto, WalletRefundNotification::class);
        });
    }

    /**
     * Get user's current wallet balance from the authoritative source (user_wallets table).
     */
    public function getBalance(User $user): string
    {
        $wallet = UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn($q) => $q->where('default', true))
            ->value('balance');

        return $wallet ?? '0.00000000';
    }

    /**
     * Get the user's default wallet model instance.
     */
    public function getWallet(User $user): UserWallet
    {
        return UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn($q) => $q->where('default', true))
            ->firstOrFail();
    }

    // ─── Private Helpers ─────────────────────────────────────────

    /**
     * Shared credit logic used by both deposit() and refund().
     *
     * @param class-string $notificationClass
     */
    private function creditWallet(User $user, float $amount, WalletTransactionDTO $dto, string $notificationClass): BalanceTransaction
    {
        // Lock the wallet row for the default currency to prevent concurrent updates
        $wallet = UserWallet::lockForUpdate()
            ->where('user_id', $user->id)
            ->whereHas('currency', fn($q) => $q->where('default', true))
            ->firstOrFail();

        $balanceBefore = (string) $wallet->balance;
        $balanceAfter = bcadd($balanceBefore, (string) $amount, 8);

        $wallet->update(['balance' => $balanceAfter]);

        $transaction = $this->repository->createTransaction($user, $dto, $balanceBefore, $balanceAfter);

        // Queue notification
        try {
            $notificationMessage = $dto->description ?: __('Wallet credit');
            $user->notify((new $notificationClass($amount, (float) $balanceAfter, $notificationMessage))->locale('ar'));
        } catch (\Throwable $e) {
            Log::warning('WalletService: Credit notification failed', [
                'notification' => $notificationClass,
                'error' => $e->getMessage(),
            ]);
        }

        return $transaction;
    }
}

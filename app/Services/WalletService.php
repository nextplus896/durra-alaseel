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
            // Lock the wallet row for the default currency to prevent concurrent updates
            $wallet = UserWallet::lockForUpdate()
                ->where('user_id', $user->id)
                ->whereHas('currency', fn($q) => $q->where('default', true))
                ->firstOrFail();

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter  = $balanceBefore + $amount;

            $wallet->update(['balance' => $balanceAfter]);

            $user = User::find($user->id);
            $transaction = $this->repository->createTransaction($user, $dto, $balanceBefore, $balanceAfter);

            // Queue notification
            try {
                $user->notify((new WalletChargedNotification($amount, $balanceAfter))->locale('ar'));
            } catch (\Throwable $e) {
                Log::warning('WalletService: Charge notification failed', ['error' => $e->getMessage()]);
            }

            return $transaction;
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
            $wallet = UserWallet::lockForUpdate()
                ->where('user_id', $user->id)
                ->whereHas('currency', fn($q) => $q->where('default', true))
                ->firstOrFail();

            $balanceBefore = (float) $wallet->balance;

            if ($balanceBefore < $amount) {
                throw new Exception(__('Insufficient balance. Please recharge your account.'));
            }

            $balanceAfter = $balanceBefore - $amount;
            $wallet->update(['balance' => $balanceAfter]);

            $user = User::find($user->id);
            $transaction = $this->repository->createTransaction($user, $dto, $balanceBefore, $balanceAfter);

            try {
                $user->notify((new WalletDeductedNotification($amount, $balanceAfter, $dto->description))->locale('ar'));
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
            $wallet = UserWallet::lockForUpdate()
                ->where('user_id', $user->id)
                ->whereHas('currency', fn($q) => $q->where('default', true))
                ->firstOrFail();

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter  = $balanceBefore + $amount;

            $wallet->update(['balance' => $balanceAfter]);

            $user = User::find($user->id);
            $transaction = $this->repository->createTransaction($user, $dto, $balanceBefore, $balanceAfter);

            try {
                $user->notify((new WalletRefundNotification($amount, $balanceAfter, $dto->description))->locale('ar'));
            } catch (\Throwable $e) {
                Log::warning('WalletService: Refund notification failed', ['error' => $e->getMessage()]);
            }

            return $transaction;
        });
    }

    /**
     * Get user's current wallet balance.
     */
    public function getBalance(User $user): float
    {
        return (float) $user->balance;
    }
}

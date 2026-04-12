<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\BalanceTransaction;
use App\DTO\WalletTransactionDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class WalletRepository
{
    /**
     * Create a ledger entry in balance_transactions.
     *
     * Must be called inside an existing DB::transaction() — does NOT manage its own.
     */
    public function createTransaction(User $user, WalletTransactionDTO $dto, float $balanceBefore, float $balanceAfter): BalanceTransaction
    {
        return BalanceTransaction::create([
            'user_id'             => $user->id,
            'trx_id'              => $this->generateTrxId(),
            'type'                => $dto->type,
            'amount'              => $dto->amount,
            'balance_before'      => $balanceBefore,
            'balance_after'       => $balanceAfter,
            'payment_method'      => $dto->paymentMethod,
            'booking_id'          => $dto->bookingId,
            'description'         => $dto->description,
            'status'              => BalanceTransaction::STATUS_SUCCESS,
            'reference_type'      => $dto->referenceType,
            'reference_id'        => $dto->referenceId,
            'moyasar_invoice_id'  => $dto->moyasarInvoiceId,
            'idempotency_key'     => $dto->idempotencyKey,
        ]);
    }

    /**
     * Paginated transaction history for a user.
     */
    public function getTransactions(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return BalanceTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Check if a transaction with the given idempotency key already exists.
     */
    public function findByIdempotencyKey(string $key): ?BalanceTransaction
    {
        return BalanceTransaction::where('idempotency_key', $key)->first();
    }

    /**
     * Generate a unique transaction reference ID.
     */
    protected function generateTrxId(): string
    {
        do {
            $trxId = 'BAL-' . strtoupper(Str::random(10));
        } while (BalanceTransaction::where('trx_id', $trxId)->exists());

        return $trxId;
    }
}

<?php

/**
 * Temporary script to simulate Moyasar webhook locally.
 * Run: php credit_wallet.php
 * Delete after use.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// ─── CONFIG ─────────────────────────────────────────────
$userId = 4; // Change to your user ID
// ────────────────────────────────────────────────────────

use App\Models\User;
use App\Models\UserWallet;
use App\Models\PaymentTransaction;
use App\DTO\WalletTransactionDTO;
use App\Services\WalletService;

echo "=== Simulate Moyasar Webhook ===\n\n";

// 1. Find pending payment
$pt = PaymentTransaction::where('user_id', $userId)
    ->where('status', PaymentTransaction::STATUS_PENDING)
    ->latest()
    ->first();

if (!$pt) {
    echo "ERROR: No pending payment found for user {$userId}\n";
    echo "Make sure you completed the Moyasar payment from Flutter first.\n";
    exit(1);
}

echo "Found Payment:\n";
echo "  ID:          {$pt->id}\n";
echo "  Invoice:     {$pt->invoice_id}\n";
echo "  Amount:      {$pt->amount} SAR\n";
echo "  Status:      {$pt->status} (0=pending)\n\n";

// 2. Check balance before
$wallet = UserWallet::where('user_id', $userId)
    ->whereHas('currency', fn($q) => $q->where('default', true))
    ->first();

if (!$wallet) {
    echo "ERROR: No wallet found for user {$userId}\n";
    exit(1);
}

echo "Balance BEFORE: {$wallet->balance} SAR\n\n";

// 3. Credit wallet
$user = User::find($userId);

$dto = WalletTransactionDTO::forDeposit(
    amount: (float) $pt->amount,
    description: 'Wallet recharge',
    referenceType: 'App\Models\PaymentTransaction',
    referenceId: $pt->id,
    idempotencyKey: 'webhook-' . $pt->invoice_id,
    moyasarInvoiceId: $pt->invoice_id,
);

try {
    $walletService = app(WalletService::class);
    $walletService->deposit($user, (float) $pt->amount, $dto);

    $pt->markPaid('pay_simulated_local');

    // 4. Check balance after
    $wallet->refresh();

    echo "Transaction created successfully!\n";
    echo "Balance AFTER:  {$wallet->balance} SAR\n";
    echo "Payment status: {$pt->status} (1=paid)\n\n";

    echo "=== DONE — wallet credited! ===\n";
} catch (\Throwable $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

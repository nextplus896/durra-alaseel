<?php

$wallet = \App\Models\UserWallet::where('user_id', 1)
    ->whereHas('currency', fn($q) => $q->where('default', true))
    ->first();
echo "Wallet balance: {$wallet->balance}\n";

$btCount = \App\Models\BalanceTransaction::where('user_id', 1)
    ->where('type', 'refund')
    ->count();
echo "Refund transactions: {$btCount}\n";

$pt = \App\Models\PaymentTransaction::find(20);
echo "Payment status: {$pt->status} (should still be 1=paid)\n";

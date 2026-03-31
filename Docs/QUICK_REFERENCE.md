# 🚀 QUICK IMPLEMENTATION REFERENCE

## Copy-Paste Ready Code Snippets

This file contains the EXACT code you need to copy into each TODO section.

---

## 1. MoyasarService.php - createInvoice()

**Location:** Line ~40 in `app/Services/MoyasarService.php`

**Replace:** `throw new Exception('Not implemented yet. You will implement this!');`

**With:**

```php
$this->ensureConfigured();

// Convert amount to halalas (1 SAR = 100 halalas)
$amountInHalalas = (int)($amount * 100);

// Prepare callback URL
$callbackUrl = route('moyasar.webhook');

// Prepare request data
$data = [
    'amount' => $amountInHalalas,
    'currency' => config('moyasar.invoice.currency', 'SAR'),
    'description' => __('Wallet Recharge - :amount SAR', ['amount' => number_format($amount, 2)]),
    'callback_url' => $callbackUrl,
    'metadata' => array_merge($metadata, [
        'user_id' => $userId,
        'user_email' => $userEmail,
        'user_name' => $userName,
        'type' => 'wallet_recharge',
        'created_at' => now()->toDateTimeString(),
    ]),
];

// Make API call
return $this->makeRequest('POST', '/invoices', $data);
```

---

## 2. MoyasarService.php - verifyWebhookSignature()

**Location:** Line ~72 in `app/Services/MoyasarService.php`

**Replace:** `throw new Exception('Not implemented yet. You will implement this!');`

**With:**

```php
if (empty($this->webhookSecret)) {
    Log::warning('Moyasar webhook secret not configured');
    return false;
}

// Calculate expected signature using HMAC-SHA256
$expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

// Use hash_equals to prevent timing attacks
return hash_equals($expectedSignature, $signature);
```

---

## 3. MoyasarService.php - getInvoice()

**Location:** Line ~89 in `app/Services/MoyasarService.php`

**Replace:** `throw new Exception('Not implemented yet. You will implement this!');`

**With:**

```php
$this->ensureConfigured();
return $this->makeRequest('GET', '/invoices/' . $invoiceId);
```

---

## 4. WalletController.php - recharge()

**Location:** Line ~63 in `app/Http/Controllers/Api/V1/User/WalletController.php`

**Replace:** Everything in the `recharge()` method

**With:**

```php
// Step 1: Validate input
$validator = Validator::make($request->all(), [
    'amount' => [
        'required',
        'numeric',
        'min:' . config('moyasar.recharge.min_amount', 10),
        'max:' . config('moyasar.recharge.max_amount', 10000),
    ],
]);

if ($validator->fails()) {
    return Response::error($validator->errors()->all(), [], 400);
}

$validated = $validator->validate();
$user = Auth::guard('api')->user();
$amount = floatval($validated['amount']);

DB::beginTransaction();
try {
    // Step 2: Create Moyasar invoice
    $invoice = $this->moyasarService->createInvoice(
        $amount,
        $user->id,
        $user->email,
        $user->fullname,
        [
            'username' => $user->username,
            'mobile' => $user->mobile,
        ]
    );

    // Step 3: Create pending balance transaction
    $trxId = 'WLT-' . strtoupper(generate_unique_string('balance_transactions', 'trx_id', 16));

    $transaction = BalanceTransaction::create([
        'user_id' => $user->id,
        'trx_id' => $trxId,
        'type' => BalanceTransaction::TYPE_RECHARGE,
        'amount' => $amount,
        'balance_before' => $user->balance,
        'balance_after' => $user->balance,
        'payment_method' => 'moyasar_invoice',
        'description' => __('Wallet recharge via Moyasar invoice - Amount: :amount SAR', ['amount' => number_format($amount, 2)]),
        'status' => BalanceTransaction::STATUS_PENDING,
        'details' => json_encode([
            'moyasar_invoice_id' => $invoice['id'],
            'moyasar_invoice_url' => $invoice['url'] ?? null,
            'moyasar_status' => $invoice['status'] ?? 'pending',
            'created_at' => now()->toDateTimeString(),
        ]),
    ]);

    DB::commit();

    // Step 4: Return invoice URL
    return Response::success(
        [__('Recharge request created successfully. Please complete payment via the invoice link.')],
        [
            'transaction_id' => $transaction->id,
            'trx_id' => $trxId,
            'amount' => number_format($amount, 2),
            'currency' => get_default_currency_code(),
            'invoice_url' => $invoice['url'] ?? null,
            'invoice_id' => $invoice['id'] ?? null,
            'status' => 'pending',
            'expires_at' => isset($invoice['expires_at']) ? $invoice['expires_at'] : null,
        ],
        201
    );
} catch (\Throwable $e) {
    DB::rollBack();

    Log::error('Wallet Recharge Error', [
        'user_id' => $user->id,
        'amount' => $amount,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return Response::error(
        [__('Failed to create recharge request. Please try again later.')],
        ['error' => config('app.debug') ? $e->getMessage() : 'Internal server error'],
        500
    );
}
```

---

## 5. MoyasarWebhookController.php - handle()

**Location:** Line ~37 in `app/Http/Controllers/Webhooks/MoyasarWebhookController.php`

**Replace:** Everything in the `handle()` method

**With:**

```php
// Step 1: Get raw request body and signature
$payload = $request->getContent();
$signature = $request->header('X-Moyasar-Signature');

if (!$signature) {
    Log::warning('Moyasar Webhook: Missing signature header');
    return response()->json(['error' => 'Missing signature'], 400);
}

// Step 2: Verify signature
try {
    if (!$this->moyasarService->verifyWebhookSignature($payload, $signature)) {
        Log::warning('Moyasar Webhook: Invalid signature');
        return response()->json(['error' => 'Invalid signature'], 403);
    }
} catch (\Exception $e) {
    Log::error('Moyasar Webhook: Signature verification failed', [
        'error' => $e->getMessage(),
    ]);
    return response()->json(['error' => 'Signature verification failed'], 403);
}

// Step 3: Parse webhook data
$data = json_decode($payload, true);

if (!isset($data['type'], $data['data'])) {
    Log::error('Moyasar Webhook: Invalid payload structure');
    return response()->json(['error' => 'Invalid payload'], 400);
}

$eventType = $data['type'];
$invoiceData = $data['data'];

Log::info('Moyasar Webhook: Event received', [
    'type' => $eventType,
    'invoice_id' => $invoiceData['id'] ?? 'unknown',
]);

// Step 4: Handle invoice.paid event
if ($eventType === 'invoice.paid') {
    try {
        $this->handleInvoicePaid($invoiceData);

        return response()->json([
            'message' => 'Webhook processed successfully',
            'event' => $eventType,
        ], 200);
    } catch (\Throwable $e) {
        Log::error('Moyasar Webhook Processing Error', [
            'event' => $eventType,
            'invoice_id' => $invoiceData['id'] ?? null,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'message' => 'Webhook received but processing failed',
            'error' => $e->getMessage(),
        ], 200);
    }
}

// Other event types
Log::info('Moyasar Webhook: Unhandled event type', ['type' => $eventType]);
return response()->json(['message' => 'Event type not handled'], 200);
```

---

## 6. MoyasarWebhookController.php - handleInvoicePaid()

**Location:** Add this NEW method after `handle()` in `app/Http/Controllers/Webhooks/MoyasarWebhookController.php`

```php
/**
 * Handle invoice.paid event - update user balance
 */
protected function handleInvoicePaid(array $invoiceData): void
{
    $invoiceId = $invoiceData['id'];
    $amountPaid = $invoiceData['amount'] / 100; // Convert halalas to SAR
    $metadata = $invoiceData['metadata'] ?? [];
    $userId = $metadata['user_id'] ?? null;

    if (!$userId) {
        throw new Exception('Missing user_id in invoice metadata');
    }

    Log::info('Moyasar Webhook: Processing invoice payment', [
        'invoice_id' => $invoiceId,
        'user_id' => $userId,
        'amount' => $amountPaid,
    ]);

    DB::beginTransaction();
    try {
        // Find user
        $user = User::findOrFail($userId);

        // Find pending transaction
        $transaction = BalanceTransaction::where('user_id', $userId)
            ->where('type', BalanceTransaction::TYPE_RECHARGE)
            ->where('status', BalanceTransaction::STATUS_PENDING)
            ->where('details', 'LIKE', '%' . $invoiceId . '%')
            ->first();

        if (!$transaction) {
            throw new Exception('Transaction not found for invoice: ' . $invoiceId);
        }

        // Prevent duplicate processing
        if ($transaction->status === BalanceTransaction::STATUS_SUCCESS) {
            Log::info('Moyasar Webhook: Transaction already processed', [
                'transaction_id' => $transaction->id,
            ]);
            DB::rollBack();
            return;
        }

        // Update user balance
        $oldBalance = $user->balance;
        $newBalance = $oldBalance + $amountPaid;
        $user->update(['balance' => $newBalance]);

        // Update transaction
        $details = json_decode($transaction->details, true) ?? [];
        $transaction->update([
            'status' => BalanceTransaction::STATUS_SUCCESS,
            'balance_before' => $oldBalance,
            'balance_after' => $newBalance,
            'details' => json_encode(array_merge($details, [
                'moyasar_paid_at' => $invoiceData['paid_at'] ?? now()->toDateTimeString(),
                'moyasar_amount_paid' => $amountPaid,
                'processed_at' => now()->toDateTimeString(),
            ])),
        ]);

        DB::commit();

        Log::info('Moyasar Webhook: Balance updated successfully', [
            'user_id' => $userId,
            'amount' => $amountPaid,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}
```

---

## 7. Environment Variables (.env)

Add these to your `.env` file:

```env
# Moyasar Payment Gateway
MOYASAR_API_KEY=sk_test_YOUR_KEY_HERE
MOYASAR_PUBLISHABLE_KEY=pk_test_YOUR_KEY_HERE
MOYASAR_WEBHOOK_SECRET=whsec_YOUR_SECRET_HERE
MOYASAR_MODE=test
MOYASAR_CURRENCY=SAR
```

---

## 🎯 Implementation Order

1. ✅ Copy all 3 methods to `MoyasarService.php`
2. ✅ Copy `recharge()` to `WalletController.php`
3. ✅ Copy `handle()` and `handleInvoicePaid()` to `MoyasarWebhookController.php`
4. ✅ Add environment variables to `.env`
5. ✅ Run: `php artisan config:clear`
6. ✅ Test with Postman!

---

## 📝 Testing Commands

```bash
# Clear config cache
php artisan config:clear

# Check routes
php artisan route:list --name=wallet

# Check logs
tail -f storage/logs/laravel.log
```

---

**That's it! Copy, paste, test, learn! 🎓**

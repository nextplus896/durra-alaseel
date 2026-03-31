# 🎓 WALLET SYSTEM IMPLEMENTATION - YOUR LEARNING CHECKLIST

## ✅ COMPLETED SETUP

### Files Created:

1. ✅ `app/Services/MoyasarService.php` - Moyasar API integration
2. ✅ `app/Http/Controllers/Api/V1/User/WalletController.php` - API endpoints
3. ✅ `app/Http/Controllers/Webhooks/MoyasarWebhookController.php` - Payment webhooks
4. ✅ `config/moyasar.php` - Configuration file
5. ✅ Routes added to `routes/api/v1/user.php` and `routes/api.php`

### Database:

- ✅ `balance_transactions` table already exists
- ✅ `users.balance` field already exists
- ✅ `car_bookings` has balance payment support
- ✅ No new migrations needed!

---

## 📝 TODO: YOUR IMPLEMENTATION TASKS

### TASK 1: Complete MoyasarService.php

**Location:** `app/Services/MoyasarService.php`

**Methods to Implement:**

#### 1.1 createInvoice() - Line ~40

```php
public function createInvoice(
    float $amount,
    int $userId,
    string $userEmail,
    string $userName,
    array $metadata = []
): array {
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
}
```

#### 1.2 verifyWebhookSignature() - Line ~72

```php
public function verifyWebhookSignature(string $payload, string $signature): bool
{
    if (empty($this->webhookSecret)) {
        Log::warning('Moyasar webhook secret not configured');
        return false;
    }

    // Calculate expected signature using HMAC-SHA256
    $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

    // Use hash_equals to prevent timing attacks
    return hash_equals($expectedSignature, $signature);
}
```

#### 1.3 getInvoice() - Line ~89

```php
public function getInvoice(string $invoiceId): array
{
    $this->ensureConfigured();
    return $this->makeRequest('GET', '/invoices/' . $invoiceId);
}
```

**Test After Implementation:**

- Routes should load without errors
- Service should handle missing config gracefully

---

### TASK 2: Complete WalletController.php

**Location:** `app/Http/Controllers/Api/V1/User/WalletController.php`

#### 2.1 Implement recharge() Method - Line ~63

**Replace the TODO with:**

```php
public function recharge(Request $request)
{
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
            'balance_after' => $user->balance, // Not changed yet
            'payment_method' => 'moyasar_invoice',
            'description' => __('Wallet recharge via Moyasar invoice - Amount: :amount SAR', ['amount' => number_format($amount, 2)]),
            'status' => BalanceTransaction::STATUS_PENDING, // Waiting for payment
            'details' => json_encode([
                'moyasar_invoice_id' => $invoice['id'],
                'moyasar_invoice_url' => $invoice['url'] ?? null,
                'moyasar_status' => $invoice['status'] ?? 'pending',
                'created_at' => now()->toDateTimeString(),
            ]),
        ]);

        DB::commit();

        // Step 4: Return invoice URL to user
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
}
```

---

### TASK 3: Complete MoyasarWebhookController.php

**Location:** `app/Http/Controllers/Webhooks/MoyasarWebhookController.php`

#### 3.1 Implement handle() Method - Line ~37

**Replace the TODO with:**

```php
public function handle(Request $request)
{
    // Step 1: Get raw request body and signature
    $payload = $request->getContent(); // Raw JSON string
    $signature = $request->header('X-Moyasar-Signature');

    if (!$signature) {
        Log::warning('Moyasar Webhook: Missing signature header');
        return response()->json(['error' => 'Missing signature'], 400);
    }

    // Step 2: Verify signature to prevent fake webhooks
    try {
        if (!$this->moyasarService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Moyasar Webhook: Invalid signature', [
                'signature_received' => substr($signature, 0, 20) . '...',
            ]);
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
        Log::error('Moyasar Webhook: Invalid payload structure', [
            'payload' => $payload,
        ]);
        return response()->json(['error' => 'Invalid payload'], 400);
    }

    $eventType = $data['type']; // e.g., "invoice.paid"
    $invoiceData = $data['data'];

    Log::info('Moyasar Webhook: Event received', [
        'type' => $eventType,
        'invoice_id' => $invoiceData['id'] ?? 'unknown',
    ]);

    // Step 4: Handle different event types
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
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Moyasar retrying
            // (We've logged the error for manual review)
            return response()->json([
                'message' => 'Webhook received but processing failed',
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    // Other event types (invoice.expired, invoice.failed, etc.)
    Log::info('Moyasar Webhook: Unhandled event type', [
        'type' => $eventType,
        'invoice_id' => $invoiceData['id'] ?? null,
    ]);

    return response()->json(['message' => 'Event type not handled'], 200);
}
```

#### 3.2 Add handleInvoicePaid() Method

**Add this method after handle():**

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

        // Prevent duplicate processing (idempotency)
        if ($transaction->status === BalanceTransaction::STATUS_SUCCESS) {
            Log::info('Moyasar Webhook: Transaction already processed', [
                'transaction_id' => $transaction->id,
                'invoice_id' => $invoiceId,
            ]);
            DB::rollBack();
            return; // Already processed, skip
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
                'moyasar_payment_source' => $invoiceData['source'] ?? null,
                'processed_at' => now()->toDateTimeString(),
            ])),
        ]);

        DB::commit();

        Log::info('Moyasar Webhook: Balance updated successfully', [
            'user_id' => $userId,
            'transaction_id' => $transaction->id,
            'amount' => $amountPaid,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
        ]);

        // TODO: Send notification to user (email/push)
        // You can implement this later!
        // Example: Mail::to($user->email)->send(new WalletRechargeSuccess($transaction));

    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Moyasar Webhook: Failed to update balance', [
            'invoice_id' => $invoiceId,
            'user_id' => $userId,
            'error' => $e->getMessage(),
        ]);

        throw $e;
    }
}
```

---

## 🔧 CONFIGURATION SETUP

### Step 4: Add Environment Variables

**Location:** `.env` file

Add these lines:

```env
# Moyasar Payment Gateway Configuration
MOYASAR_API_KEY=sk_test_YOUR_SECRET_KEY_HERE
MOYASAR_PUBLISHABLE_KEY=pk_test_YOUR_PUBLISHABLE_KEY_HERE
MOYASAR_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET_HERE
MOYASAR_MODE=test
MOYASAR_CURRENCY=SAR
```

**How to Get These:**

1. Sign up at https://moyasar.com
2. Go to Dashboard → Settings → API Keys
3. Copy Test Keys for development
4. For Webhook Secret: Dashboard → Settings → Webhooks → Create Webhook → Copy signing secret

---

## 🧪 TESTING YOUR IMPLEMENTATION

### Test 1: Verify Routes Load

```bash
php artisan route:list --name=wallet
```

**Expected Output:**

```
GET    /api/v1/user/wallet           api.user.wallet.index
POST   /api/v1/user/wallet/recharge  api.user.wallet.recharge
GET    /api/v1/user/wallet/transactions api.user.wallet.transactions
```

### Test 2: Check Webhook Route

```bash
php artisan route:list --name=moyasar
```

**Expected Output:**

```
POST   /api/webhooks/moyasar         moyasar.webhook
```

### Test 3: API Testing with Postman/Insomnia

#### 3.1 Get Wallet Balance

```http
GET {{base_url}}/api/v1/user/wallet
Headers:
  Authorization: Bearer {{your_access_token}}
```

**Expected Response:**

```json
{
    "success": true,
    "message": ["Wallet data fetched successfully"],
    "data": {
        "balance": "0.00",
        "currency": "SAR",
        "recent_transactions": []
    }
}
```

#### 3.2 Request Recharge

```http
POST {{base_url}}/api/v1/user/wallet/recharge
Headers:
  Authorization: Bearer {{your_access_token}}
  Content-Type: application/json
Body:
{
  "amount": 100
}
```

**Expected Response:**

```json
{
    "success": true,
    "message": ["Recharge request created successfully..."],
    "data": {
        "transaction_id": 123,
        "trx_id": "WLT-ABC123...",
        "amount": "100.00",
        "currency": "SAR",
        "invoice_url": "https://moyasar.com/i/inv_xxx",
        "status": "pending"
    }
}
```

### Test 4: Simulate Moyasar Webhook (Local Testing)

Create a test webhook payload:

```json
{
    "type": "invoice.paid",
    "data": {
        "id": "inv_test_123",
        "status": "paid",
        "amount": 10000,
        "paid_at": "2026-02-04T15:30:00Z",
        "metadata": {
            "user_id": 1,
            "type": "wallet_recharge"
        }
    }
}
```

Calculate signature:

```php
$payload = '{"type":"invoice.paid",...}';
$signature = hash_hmac('sha256', $payload, 'your_webhook_secret');
```

Send request:

```http
POST {{base_url}}/api/webhooks/moyasar
Headers:
  X-Moyasar-Signature: {calculated_signature}
  Content-Type: application/json
Body: {payload}
```

---

## 🐛 DEBUGGING TIPS

### Problem: "Moyasar API key not configured"

**Solution:**

1. Check `.env` file has `MOYASAR_API_KEY=...`
2. Run `php artisan config:clear`
3. Restart Laravel server

### Problem: "Invalid signature" in webhook

**Solution:**

1. Verify webhook secret matches Moyasar dashboard
2. Check `X-Moyasar-Signature` header is being sent
3. Use raw request body (not parsed JSON)

### Problem: Transaction not found in webhook

**Solution:**

1. Check `balance_transactions.details` column stores invoice ID correctly
2. Use JSON search: `WHERE JSON_EXTRACT(details, '$.moyasar_invoice_id') = ?`
3. Verify user_id in metadata matches

---

## 📚 LEARNING RESOURCES

### Moyasar API Documentation

- Main Docs: https://moyasar.com/docs/api/
- Invoices: https://moyasar.com/docs/api/#create-an-invoice
- Webhooks: https://moyasar.com/docs/webhooks/

### Laravel Resources

- HTTP Client: https://laravel.com/docs/10.x/http-client
- Database Transactions: https://laravel.com/docs/10.x/database#database-transactions
- Validation: https://laravel.com/docs/10.x/validation

---

## ✅ COMPLETION CHECKLIST

- [ ] All 3 TODO methods in `MoyasarService.php` implemented
- [ ] `WalletController@recharge()` implemented
- [ ] `MoyasarWebhookController@handle()` implemented
- [ ] `handleInvoicePaid()` method added
- [ ] Environment variables added to `.env`
- [ ] Config cache cleared: `php artisan config:clear`
- [ ] Routes verified: `php artisan route:list --name=wallet`
- [ ] API tested with Postman
- [ ] Webhook tested (or simulated)
- [ ] Logs checked: `storage/logs/laravel.log`

---

## 🎯 NEXT STEPS (Optional Enhancements)

1. **Add Email Notifications** when balance is updated
2. **Create Admin Panel** to view all wallet transactions
3. **Add Refund Feature** for cancelled bookings
4. **Implement Auto-retry** for failed webhooks
5. **Add Balance Limits** (min/max balance per user)
6. **Create Wallet Statement** PDF export
7. **Add Multi-currency Support** (USD, EUR, etc.)

---

**Good luck with your implementation! 🚀**

If you encounter any issues, check the logs in `storage/logs/laravel.log` and feel free to ask questions!

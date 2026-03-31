# Flutter Wallet System Handoff Guide

This document is the mobile integration handoff for the Wallet System in the Car Rental app.

Stack context:

- Backend: Laravel (REST API)
- Auth: Bearer token (`auth:api`)
- Payments: Moyasar
- Wallet ledger: `balance_transactions`
- Payment ledger: `payment_transactions`

---

## 1) API Overview

| Endpoint                                   | Method | Description                                                  |
| ------------------------------------------ | ------ | ------------------------------------------------------------ |
| `/api/v1/user/wallet`                      | GET    | Get wallet balance + recent transactions                     |
| `/api/v1/user/wallet/transactions`         | GET    | Get paginated wallet transaction history                     |
| `/api/v1/user/wallet/recharge`             | POST   | Create Moyasar invoice for wallet topup                      |
| `/api/webhooks/moyasar`                    | POST   | Moyasar webhook (backend-only, Flutter does not call)        |
| `/api/v1/user/payments/{paymentId}/refund` | POST   | Refund card payment and reverse wallet credit                |
| `/api/v1/user/car-booking/cancel`          | POST   | Cancel pending booking with auto wallet refund (if deducted) |

Common headers for authenticated APIs:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

Response envelope pattern used by backend helpers:

```json
{
    "message": {
        "success": ["..."]
    },
    "data": {},
    "type": "success"
}
```

Error envelope:

```json
{
    "message": {
        "error": ["..."]
    },
    "data": [],
    "type": "error"
}
```

---

## 2) Get Wallet Balance

Endpoint:

- `GET /api/v1/user/wallet`

Headers:

- `Authorization: Bearer <token>`
- `Accept: application/json`

Success response example:

```json
{
    "message": {
        "success": ["Wallet data fetched successfully"]
    },
    "data": {
        "wallet_id": 1,
        "balance": "250.00",
        "currency": "SAR",
        "updated_at": "2026-03-01T10:00:00Z",
        "recent_transactions": [
            {
                "id": 15,
                "trx_id": "BAL-8K3P1XQZ0M",
                "type": "recharge",
                "amount": "100.00000000",
                "balance_before": "150.00000000",
                "balance_after": "250.00000000",
                "reference_type": "App\\Models\\PaymentTransaction",
                "reference_id": 22,
                "description": "Wallet recharge via Moyasar",
                "created_at": "2026-03-01T10:00:00.000000Z"
            }
        ]
    },
    "type": "success"
}
```

Error cases:

- `401`: unauthenticated token
- `500`: unexpected backend failure

Flutter display guidance:

- Show a wallet summary card with `balance` + `currency`.
- Render `recent_transactions` under the card.
- Refresh on screen open, pull-to-refresh, and app resume.

---

## 3) Wallet Transactions

Endpoint:

- `GET /api/v1/user/wallet/transactions?per_page=15`

Success response example:

```json
{
    "message": {
        "success": ["Transactions fetched successfully"]
    },
    "data": {
        "transactions": [
            {
                "id": 15,
                "type": "recharge",
                "amount": "100.00000000",
                "reference_type": "App\\Models\\PaymentTransaction",
                "reference_id": 22,
                "description": "Wallet topup",
                "created_at": "2026-03-01T10:00:00.000000Z"
            },
            {
                "id": 16,
                "type": "booking_deduction",
                "amount": "200.00000000",
                "reference_type": "App\\Models\\CarBooking",
                "reference_id": 91,
                "description": "Booking payment - TRX123456",
                "created_at": "2026-03-01T11:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total": 42,
            "per_page": 15,
            "last_page": 3
        }
    },
    "type": "success"
}
```

Flutter wallet history screen guidance:

- Group by date (`Today`, `Yesterday`, `Earlier`).
- Color coding:
    - `recharge` / `refund`: green (+)
    - `booking_deduction`: red (-)
- Add infinite scroll using `pagination`.
- Display reference tags from `reference_type` + `reference_id`.

---

## 4) Wallet Topup

Topup flow:

- Flutter -> Wallet Recharge API -> Moyasar Invoice URL -> Payment Page -> Webhook updates wallet -> Flutter refreshes balance.

Endpoint:

- `POST /api/v1/user/wallet/recharge`

Request example:

```json
{
    "amount": 150
}
```

Success response example:

```json
{
    "message": {
        "success": ["Invoice created successfully. Please complete payment."]
    },
    "data": {
        "invoice_id": "inv_93822",
        "payment_url": "https://pay.moyasar.com/invoices/xxxx",
        "expires_at": "2026-03-01T10:20:00Z",
        "amount": "150.00",
        "currency": "SAR"
    },
    "type": "success"
}
```

Notes:

- Backend currently returns `invoice_id`, `payment_url`, `amount`, `currency`.
- `expires_at` can be treated as optional on mobile if not present.

Flutter payment-page integration:

- Open `payment_url` with `url_launcher` (external browser or in-app webview).
- Do not assume wallet is credited immediately after redirect return.
- After return, poll wallet endpoint for status update (2-3 sec interval, up to ~60 sec).

---

## 5) Payment Webhook (Backend)

Endpoint:

- `POST /api/webhooks/moyasar`

Important:

- Flutter **does NOT call this API**.
- This is called by Moyasar servers.

Backend webhook behavior:

1. Verify signature (`X-Moyasar-Signature`) using HMAC SHA-256.
2. Parse payload (`invoice_id`, `status`, `payment_id`).
3. Find matching `payment_transactions` row.
4. Enforce idempotency (`already paid` => no duplicate credit).
5. If paid:
    - mark payment as paid
    - add wallet balance
    - create wallet transaction type `recharge`

Example webhook payload:

```json
{
    "type": "payment.updated",
    "data": {
        "id": "pay_123",
        "invoice_id": "inv_93822",
        "status": "paid",
        "amount": 15000,
        "currency": "SAR"
    }
}
```

---

## 6) Booking Wallet Deduction

When booking uses wallet payment, backend deducts automatically.

Example booking success response:

```json
{
    "message": {
        "success": ["Booking Successful! Amount deducted from your balance."]
    },
    "data": {
        "booking_id": 91,
        "amount_paid": 200,
        "payment_method": "wallet",
        "wallet_balance_after": 150,
        "amount_deducted": 200,
        "new_balance": 150
    },
    "type": "success"
}
```

Flutter UI guidance:

- Show booking success sheet with wallet deduction summary.
- Immediately refresh wallet card and history list.

---

## 7) Booking Refund

Refund scenarios:

- User cancels pending booking (`POST /api/v1/user/car-booking/cancel`)
- Vendor rejects booking (backend-triggered auto refund)

User cancel request:

```json
{
    "booking_id": 91
}
```

User cancel response example:

```json
{
    "message": {
        "success": ["Booking cancelled successfully."]
    },
    "data": {
        "refund_id": 22,
        "booking_id": 91,
        "amount": 200,
        "method": "wallet",
        "wallet_balance_after": 350,
        "refunded": "200.00"
    },
    "type": "success"
}
```

Flutter UI flow:

- Show confirmation: `Refund initiated/completed to wallet`.
- Refresh wallet + transactions after cancellation success.
- Show push/in-app notification when refund arrives.

---

## 8) Payment Refund to Card (Moyasar)

Endpoint:

- `POST /api/v1/user/payments/{paymentId}/refund`

Purpose:

- Refund card payment via Moyasar.
- Reverse the corresponding wallet credit.

Success response example:

```json
{
    "message": {
        "success": ["Payment refunded successfully."]
    },
    "data": {
        "refund_status": "success",
        "payment_id": 22,
        "amount": "100.00",
        "provider": "moyasar",
        "status": "refunded"
    },
    "type": "success"
}
```

Flutter note:

- This operation can reduce wallet balance because it reverses previously credited funds.

---

## 9) Notifications

Wallet notification events:

- `wallet_charged`
- `wallet_deducted`
- `wallet_refund`

Example notification payload:

```json
{
    "type": "wallet_refund",
    "amount": 200,
    "message": "200 SAR has been refunded to your wallet"
}
```

Flutter notification handling:

- Foreground: show in-app banner/snackbar.
- Background tap: deep link to wallet history screen.
- On any wallet notification: trigger wallet refresh.

---

## 10) Error Responses

Standard error envelope:

```json
{
    "message": {
        "error": ["Your wallet balance is not enough"]
    },
    "data": [],
    "type": "error"
}
```

Example cases:

Insufficient balance (`422`):

```json
{
    "error": "insufficient_balance",
    "message": "Your wallet balance is not enough"
}
```

Payment failed (`500`):

```json
{
    "error": "payment_failed",
    "message": "Payment was not completed"
}
```

Validation failed (`400`):

```json
{
    "message": {
        "error": ["The amount must be at least 10."]
    },
    "data": [],
    "type": "error"
}
```

Flutter recommendation:

- Map backend messages to app-level error codes.
- Handle `401` by forcing re-login.
- Handle `422` as user-actionable validation/business errors.

---

## 11) Flutter Integration Flow

### Wallet Topup Flow

1. User opens Wallet screen.
2. Flutter calls `GET /api/v1/user/wallet`.
3. User taps Topup and enters amount.
4. Flutter calls `POST /api/v1/user/wallet/recharge`.
5. Flutter opens `payment_url`.
6. Moyasar completes payment and triggers webhook.
7. Flutter refreshes wallet and transaction history.

### Booking Flow

1. User selects car.
2. User confirms booking using wallet.
3. Backend deducts wallet and stores transaction.
4. Flutter receives booking success with `amount_deducted` and `new_balance`.
5. Flutter updates Wallet and Booking screens.

### Refund Flow

1. User cancels booking or vendor rejects booking.
2. Backend processes refund to wallet.
3. Notification is sent.
4. Flutter refreshes wallet + wallet history.

---

## Mobile Integration Checklist

- Parse API envelope (`type`, `message`, `data`) globally.
- Keep wallet transaction model aligned with backend fields.
- Add retry/polling after payment return.
- Refresh wallet on notification receipt.
- Add fallback UI state: `Payment under processing`.
- Track and display transaction references for support/debug.

---

## Backend Safety Rules (for Mobile Awareness)

These are enforced server-side and should guide mobile UX:

1. Wallet cannot be negative.
2. Every balance change creates a transaction record.
3. Refunds reference original booking/payment.
4. Webhooks are idempotent.
5. All balance updates run inside DB transactions.

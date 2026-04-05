# Flutter Handoff — Wallet Booking & Refund

**Date:** 2026-03-31
**Scope:** Book a car via wallet balance, cancel booking with auto-refund, and push notification types.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Common Headers & Response Envelope](#2-common-headers--response-envelope)
3. [Booking Status Reference](#3-booking-status-reference)
4. [Book a Car via Wallet](#4-book-a-car-via-wallet)
5. [Cancel Booking & Refund Wallet](#5-cancel-booking--refund-wallet)
6. [Push Notification Types](#6-push-notification-types)
7. [Wallet Transaction Types](#7-wallet-transaction-types)
8. [Error Codes Reference](#8-error-codes-reference)
9. [End-to-End Flow Diagram](#9-end-to-end-flow-diagram)

---

## 1) Overview

Both endpoints already exist. No new routes were added. This document covers the full integration details plus two fixes shipped on 2026-03-31:

| Change | Detail |
|--------|--------|
| **Bug fix** | `POST /cancel` previously rejected wallet-paid bookings (status=1). Now allows cancellation of both status=0 (pending) and status=1 (booked). |
| **New notification** | User now receives a `BOOKING-CONFIRMED` in-app + push after paying via wallet. |
| **New notification** | User now receives a `BOOKING-CANCELLED` in-app + push after cancellation. |

---

## 2) Common Headers & Response Envelope

All authenticated endpoints require:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

**Success envelope:**

```json
{
    "message": { "success": ["..."] },
    "data": {},
    "type": "success"
}
```

**Error envelope:**

```json
{
    "message": { "error": ["..."] },
    "data": {},
    "type": "error"
}
```

---

## 3) Booking Status Reference

| Value | Name | Meaning |
|-------|------|---------|
| `0` | pending | Created but not yet confirmed (gateway/manual payment pending) |
| `1` | booked | Confirmed and paid — vendor notified |
| `2` | ongoing | Vendor has started the rental |
| `3` | completed | Rental finished |
| `4` | rejected | Rejected by vendor |
| `5` | user_cancelled | Cancelled by user |

> **Wallet bookings are created directly as status `1`** (payment is instant). Cash/gateway bookings may start as `0`.

---

## 4) Book a Car via Wallet

### Step 1 — Store temp booking

**POST** `/api/v1/user/car-booking/temp/store`

Required before calling confirm when you don't already have a `token`.

```json
{
    "car_id": 42,
    "car_slug": "toyota-camry-2024",
    "location": "Riyadh",
    "rental_days": 3,
    "pickup_date": "2026-04-10",
    "pickup_time": "09:00",
    "credentials": "user@example.com",
    "mobile": "0501234567",
    "include_delivery": false
}
```

Response `data.identifier` — pass this as `token` in the confirm call.

---

### Step 2 — Preview pricing

**GET** `/api/v1/user/car-booking/preview`

```
?car_id=42&rental_days=3&token=<identifier>&include_delivery=false
```

Use this to show the price breakdown to the user before they confirm.

---

### Step 3 — Confirm booking with wallet

**POST** `/api/v1/user/car-booking/confirm`

#### Request Body

```json
{
    "car_id": 42,
    "car_slug": "toyota-camry-2024",
    "location": "Riyadh",
    "rental_days": 3,
    "credentials": "user@example.com",
    "mobile": "0501234567",
    "token": "<identifier_from_temp_store>",
    "payment": "balance",
    "include_delivery": false,
    "delivery_price": 0,
    "message": "Please have the car clean."
}
```

> Set `"payment": "balance"` to use the wallet. The backend recalculates all amounts server-side — never pass a price in this request.

#### Optional — Delivery

```json
{
    "include_delivery": true,
    "delivery_price": 50.00,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh"
    }
}
```

#### Success Response `200`

```json
{
    "message": { "success": ["Booking Successful! Amount deducted from your balance."] },
    "data": {
        "booking_id": 91,
        "trx_id": "A1B2C3D4E5F6G7H8",
        "amount_deducted": 632.50,
        "new_balance": 117.50
    },
    "type": "success"
}
```

#### Error — Insufficient Balance `400`

```json
{
    "message": { "error": ["Insufficient balance. Your balance is 100.00, required amount is 632.50"] },
    "data": {
        "current_balance": 100.00,
        "required_amount": 632.50,
        "shortfall": 532.50
    },
    "type": "error"
}
```

Use `data.shortfall` to tell the user how much they need to top up.

#### Flutter implementation notes

- Show a confirmation dialog with the pricing breakdown from the preview step before calling this endpoint.
- Display `new_balance` immediately after success to update the wallet widget.
- Store `booking_id` and `trx_id` locally for the booking history screen.
- After success: refresh wallet balance and booking history.

---

## 5) Cancel Booking & Refund Wallet

**POST** `/api/v1/user/car-booking/cancel`

### Request Body

```json
{
    "booking_id": 91
}
```

### Cancellation Rules

| Booking Status | Can Cancel? | Refund? |
|---------------|-------------|---------|
| `0` — pending | Yes | Yes, if `paid_from_balance = true` |
| `1` — booked | Yes | Yes, if `paid_from_balance = true` |
| `2` — ongoing | **No** | — |
| `3` — completed | **No** | — |
| `4` — rejected | **No** | — |

> Refund goes back to the wallet instantly. The user also receives a `WalletRefundNotification` push (separate from the `BOOKING-CANCELLED` push).

### Success Response `200`

```json
{
    "message": { "success": ["Booking cancelled successfully."] },
    "data": {
        "booking_id": 91,
        "refunded": "632.50"
    },
    "type": "success"
}
```

`refunded` is `"0.00"` for cash bookings (no wallet refund needed).

### Error — Cannot Cancel `422`

```json
{
    "message": { "error": ["Only pending or unstarted bookings can be cancelled."] },
    "data": [],
    "type": "error"
}
```

### Error — Booking Not Found `404`

```json
{
    "message": { "error": ["Booking not found."] },
    "data": [],
    "type": "error"
}
```

### Flutter implementation notes

- Check `booking.status` locally before showing the cancel button:
  - Show cancel button only for status `0` or `1`.
  - Hide it for status `2`, `3`, `4`, `5`.
- After successful cancellation:
  - Refresh wallet balance (user gets money back).
  - Update booking status to `5` in local state.
  - Show a snackbar: "Booking cancelled. {refunded} SAR returned to your wallet."
- `refunded` comes back as a string — parse with `double.parse(data['refunded'])`.

---

## 6) Push Notification Types

The backend sends push notifications via Pusher Beams. Listen on the user's beam channel.

All notification payloads arrive as `UserNotification` records (fetched from the notifications API) and also as Pusher push payloads.

### `BOOKING-CONFIRMED`

Sent when user confirms a booking via wallet.

```json
{
    "type": "BOOKING-CONFIRMED",
    "title": "Booking Confirmed",
    "message": "Your booking is confirmed. Car: Toyota Camry, Pickup: 2026-04-10, Ref: A1B2C3D4",
    "time": "just now",
    "image": "<profile_default_url>"
}
```

Navigate to: **Booking Details screen** for the relevant `trx_id`.

---

### `BOOKING-CANCELLED`

Sent when user cancels a booking.

```json
{
    "type": "BOOKING-CANCELLED",
    "title": "Booking Cancelled",
    "message": "Your booking A1B2C3D4 has been cancelled. 632.50 SAR refunded to your wallet.",
    "time": "just now",
    "image": "<profile_default_url>"
}
```

Navigate to: **Booking History screen**.

---

### `WALLET-DEDUCTED`

Sent automatically when any wallet withdrawal occurs (e.g., booking payment, extension).

```json
{
    "type": "WALLET-DEDUCTED",
    "amount": 632.50,
    "balance": 117.50,
    "reason": "Booking payment - A1B2C3D4",
    "message": "632.50 SAR deducted from your wallet"
}
```

Navigate to: **Wallet screen**.

---

### `WALLET-REFUND`

Sent automatically when a refund is processed.

```json
{
    "type": "WALLET-REFUND",
    "amount": 632.50,
    "balance": 750.00,
    "reason": "Auto-refund: Booking cancelled by user",
    "message": "632.50 SAR refunded to your wallet"
}
```

Navigate to: **Wallet screen**.

---

### `WALLET-CHARGED`

Sent when user tops up wallet via Moyasar.

```json
{
    "type": "WALLET-CHARGED",
    "amount": 500.00,
    "balance": 617.50,
    "message": "Wallet topped up by 500.00 SAR"
}
```

Navigate to: **Wallet screen**.

---

### Notification dispatch order for a wallet booking

When user books via wallet, these notifications fire in this order:

1. `WALLET-DEDUCTED` — wallet layer (always fires)
2. `BOOKING-CONFIRMED` — booking layer (fires after commit)

Both arrive as Pusher push + `UserNotification` DB record.

When user cancels:

1. `WALLET-REFUND` — wallet layer (fires if `paid_from_balance = true`)
2. `BOOKING-CANCELLED` — booking layer (always fires)

---

## 7) Wallet Transaction Types

When reading `balance_transactions`, use `type` to render the correct UI label and icon:

| `type` | Label | Icon suggestion |
|--------|-------|-----------------|
| `recharge` | Wallet Top-up | arrow_downward (green) |
| `booking_deduction` | Booking Payment | car_rental (red) |
| `refund` | Refund | undo (blue) |
| `adjustment` | Adjustment | tune (grey) |

---

## 8) Error Codes Reference

| HTTP Code | Cause | Action |
|-----------|-------|--------|
| `400` | Insufficient balance | Show shortfall, offer "Top Up" button |
| `401` | Token expired / missing | Redirect to login |
| `404` | Booking not found | Show "Booking not found" message |
| `422` | Validation error or wrong booking status | Show the error message from `message.error[0]` |
| `500` | Server error | Show generic retry message |

---

## 9) End-to-End Flow Diagram

```
User taps "Pay with Wallet"
        │
        ▼
POST /temp/store  ──►  returns token
        │
        ▼
GET /preview  ──►  show price breakdown to user
        │
        ▼
User confirms
        │
        ▼
POST /confirm { payment: "balance" }
        │
        ├── 400 Insufficient balance ──► show shortfall, offer top-up
        │
        └── 200 Success
                │
                ├── DB: CarBooking (status=1, paid_from_balance=true)
                ├── DB: BalanceTransaction (type=booking_deduction)
                ├── Push to VENDOR: "New booking request"
                ├── Push to USER: WALLET-DEDUCTED
                └── Push to USER: BOOKING-CONFIRMED


User taps "Cancel Booking"
        │
        ▼
POST /cancel { booking_id }
        │
        ├── 422 status=2/3/4 ──► hide cancel button
        │
        └── 200 Success
                │
                ├── DB: CarBooking (status=5)
                ├── DB: BalanceTransaction (type=refund)  ← only if paid_from_balance
                ├── Push to USER: WALLET-REFUND  ← only if paid_from_balance
                └── Push to USER: BOOKING-CANCELLED
```

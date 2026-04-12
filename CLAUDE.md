# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Dorra Alaseel is a **multi-role car rental & booking platform** built with Laravel 10, featuring three distinct user roles (users, vendors, admins), a wallet/balance payment system, multiple payment gateways, KYC verification, and real-time notifications.

## Commands

```bash
# Development server
php artisan serve --host=192.168.1.211 --port=8001

# Database
php artisan migrate:fresh --seed       # Reset with seeders
php artisan passport:install           # Generate OAuth2 tokens

# Dependencies
composer update
npm install

# Frontend assets
npm run dev        # Vite dev server
npm run build      # Production build

# Tests
php artisan test                        # All tests
php artisan test --filter=TestName      # Single test
```

## Architecture

### Three-Role System

The app uses **three separate authentication guards** with distinct routing and controllers:

| Role   | Guard               | Web Controller             | API Controller                    | Routes File         |
| ------ | ------------------- | -------------------------- | --------------------------------- | ------------------- |
| User   | `auth` / `auth:api` | `Http/Controllers/User/`   | `Http/Controllers/Api/V1/User/`   | `routes/user.php`   |
| Vendor | `auth:vendor`       | `Http/Controllers/Vendor/` | `Http/Controllers/Api/V1/Vendor/` | `routes/vendor.php` |
| Admin  | `auth:admin`        | `Http/Controllers/Admin/`  | —                                 | `routes/admin.php`  |

**Critical:** Always verify which guard/route group you're modifying. Web routes return Blade views; API routes (`/api/v1/`) return JSON. Same business logic is shared via `App\Services\`.

### Booking Status Flow

```
pending (0) → booked (1) → ongoing (2) → completed (3)
pending (0) → rejected (4)
```

- Booking IDs must be 7 digits, unique, non-sequential: 2-digit year + 5 random digits (e.g., `2614837`)
- Car availability must be checked at every step to prevent double-booking
- All multi-step writes must be wrapped in `DB::transaction()`

### Pricing Formula

```
subtotal     = rental_fees + charges + delivery_fee
tax_amount   = subtotal × (tax_percentage / 100)   [default 15%]
total_amount = subtotal + tax_amount
```

Use `BookingBalanceService` for all pricing — never calculate inline in controllers.

### Payment Methods

1. **Balance** — Deduct from `User.balance`, create `BalanceTransaction`, set `paid_from_balance = true`
2. **Cash** — Create booking with `payment_type = 'cash'`
3. **Gateway** — Redirect via `PaymentGatewayHelper::init()` (PayPal, Stripe, Authorize.net, PayTabs, Moyasar)

Extension daily rate must always use the **original booking's daily rate**, even if the car price has changed since.

### Branch & Delivery

Delivery is **per-vendor per-branch** via `BranchDeliverySetting (branch_id, vendor_id)`.

- `$car->isDeliveryAvailable()` / `$car->getDeliveryPrice()` — use these helpers
- `Car::query()->with(['type', 'carModel'])` does NOT eager-load delivery — add `'branch.deliverySettings'` explicitly if needed
- Distance uses Haversine formula via `Branch->calculateDistance($lat, $lng)`

## Key Classes

| Class                    | Location            | Purpose                            |
| ------------------------ | ------------------- | ---------------------------------- |
| `BookingBalanceService`  | `App\Services\`     | Pricing, tax, balance calculations |
| `WalletService`          | `App\Services\`     | Wallet deductions and refunds      |
| `TwilioService`          | `App\Services\`     | SMS/WhatsApp OTP                   |
| `MoyasarService`         | `App\Services\`     | Moyasar payment gateway            |
| `PayTabsService`         | `App\Services\`     | PayTabs payment gateway            |
| `Response`               | `App\Http\Helpers\` | JSON response formatting           |
| `PaymentGatewayHelper`   | `App\Http\Helpers\` | Gateway initialization             |
| `PushNotificationHelper` | `App\Http\Helpers\` | Push notifications                 |

## Conventions

### Constants — Always use, never hardcode

- `CarBookingConst` — Booking statuses, messages
- `GlobalConst` — `VERIFIED (1)`, `PENDING (2)`, `REJECTED (3)`
- `PaymentGatewayConst` — Gateway names, payment types
- `NotificationConst` — Notification types
- `AdminRoleConst` — Admin role names

### API Response Format

```php
Response::success(['message'], ['data' => $value], 200)
// { "message": { "success": [...] }, "data": {...}, "type": "success" }

Response::error(['error'], ['details' => $value], 400)
// { "message": { "error": [...] }, "data": {...}, "type": "error" }
```

### Notifications (three-layer)

1. Create `VendorNotification` or `UserNotification` DB record
2. Send email via `Notification::send()`
3. Send push via `PushNotificationHelper::send()`

### KYC Verification

`User.kyc_verified`: `0=unverified`, `1=approved`, `2=pending`, `3=rejected`

Access data via `User->kyc()` → `UserKycData.data` (JSON field). Middleware: `kyc.verification.guard`.

## Priority Order for All Changes

1. **Availability Integrity** — Car must never be double-booked
2. **Financial Integrity** — Every deduction/charge must have a transaction record
3. **Audit Trail** — Every booking mutation must be logged with old/new values
4. **Data Consistency** — All multi-step writes inside `DB::transaction()`
5. **Clean Architecture** — Business logic in services, not controllers

## Documentation

- `Docs/FLUTTER_API_DOCS.md` — Mobile API reference
- `Docs/POSTMAN_TESTING_GUIDE.md` — API testing
- `Dorra_Alaseel_Complete_API.postman_collection.json` — Full Postman collection
- `.github/instructions/copilot-instructions.md` — Extended architecture details

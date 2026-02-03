# Dorra Alaseel - AI Coding Assistant Instructions

## Project Overview

Dorra Alaseel is a **multi-role car rental & booking platform** built with Laravel 10, featuring users, vendors, admins, and a complex payment system. The platform handles car bookings with balance/wallet payments, multiple payment gateways (PayPal, Stripe, PayStack, Authorize.net, Coinbase), KYC verification, and real-time notifications.

**Key Stats:** 1200+ line booking controller, 3-role authentication (user/vendor/admin), 250+ line User model, multi-tenant vendor architecture.

---

## Architecture Patterns

### Role-Based Application Structure

The app uses **three distinct role systems** with separate authentication guards and routing:

1. **User** - Car renters with wallet balance, KYC status, booking history
    - Guard: `auth:api` (API) and `auth` (web)
    - Routes: `routes/user.php`, `routes/api.php` (user prefix)
    - Controllers: `App\Http\Controllers\User\` and `App\Http\Controllers\Api\V1\User\`

2. **Vendor** - Car owners/managers who list cars and accept/reject bookings
    - Guard: `auth:vendor`
    - Routes: `routes/vendor.php` (web prefix)
    - Controllers: `App\Http\Controllers\Vendor\`
    - Key: Vendor cars filtered via `vendor_id` on Car model

3. **Admin** - System administrators managing settings, payments, KYC, extensions
    - Guard: `auth:admin`
    - Routes: `routes/admin.php` (web prefix)
    - Controllers: `App\Http\Controllers\Admin\`

**Critical:** Always check which guard/route group you're modifying. API vs web routes use different controllers.

### Dual API Architecture: Web Views + API Endpoints

- **Web routes** return Blade views for admin/vendor dashboards
- **API routes** (`/api/v1/`) return JSON for mobile/external apps
- **Same business logic** shared via Service classes (`App\Services\`)
- Example: `CarBookingController` (web) mirrors `CarBookingController` (API) with different response formats

### Database: PostgreSQL with Timestamps & Polymorphic Relationships

- **Naming convention:** `snake_case` tables, migrations in `database/migrations/`
- **Key patterns:**
    - Models: `App\Models\` (User, CarBooking, Car, Vendor)
    - Relationships: `belongsTo()`, `hasMany()`, `hasOne()`
    - Casts: Explicit type casting in `$casts` array (User: `balance => 'decimal:8'`)
    - Scopes: Query builder shortcuts (`User::kycVerified()`, `User::active()`)

---

## Core Domain: Car Booking System

### Data Flow: Booking Creation

```
1. User searches cars       → CarBooking/search/car     → Store in TemporaryData
2. Preview booking          → CarBooking/preview        → Calculate pricing + tax + delivery
3. Confirm booking          → CarBooking/confirm        → Create CarBooking record
4. Handle payment           → PaymentGatewayHelper      → Balance deduction or gateway redirect
5. Create notifications     → VendorNotification        → Push + Email to vendor
6. Vendor accept/reject     → BookingRequestController  → Update CarBooking.status
```

### Critical Models & Relationships

**CarBooking** (car_bookings table)

- Columns: `id`, `car_id`, `user_id`, `email` (booking email), `phone` (booking phone), `location`, `destination`, `pickup_date`, `pickup_time`, `amount`, `charges`, `delivery_fee`, `tax_amount`, `tax_percentage`, `subtotal`, `total_amount`, `status`, `payment_type`, `trx_id`, `is_delivery`, `paid_from_balance`
- Status codes: `0=pending`, `1=booked`, `2=ongoing`, `3=completed`, `4=rejected`
- Relations: `belongsTo(Car)`, `belongsTo(User)`, `belongsTo(Branch)`, `hasOne(BalanceTransaction)`

**User** (users table)

- Core fields: `firstname`, `lastname`, `email`, `mobile` + `mobile_code`, `password`
- Wallet: `balance` (decimal:8) - deducted when paying from balance
- KYC: `kyc_verified` (0=unverified, 1=approved, 2=pending, 3=rejected), `kyc()` relationship to UserKycData
- Status: `status` (1=active, 0=banned)
- Relations: `hasMany(CarBooking)`, `hasOne(UserKycData)`, `hasMany(BalanceTransaction)`

**Car** (cars table in Vendor namespace)

- Links to Vendor: `vendor_id` (FK)
- Relations: `belongsTo(Vendor)`, `hasMany(CarBooking)`

### Payment System: Three Methods

1. **Balance Payment** - Deduct from `User.balance`, create `BalanceTransaction`, set `CarBooking.paid_from_balance = true`
2. **Cash Payment** - Create booking with `payment_type = 'cash'`, no balance deduction
3. **Gateway Payment** - Redirect to PayPal/Stripe/PayStack/Authorize.net/Coinbase via `PaymentGatewayHelper::init()`

**Service:** `BookingBalanceService`

- `calculateBookingTotal()` - Returns: `rental_fees`, `delivery_price`, `charges`, `subtotal`, `tax_percentage`, `tax_amount`, `total`
- `hasSufficientBalance()` - Checks if user can pay from balance
- `deductBalanceForBooking()` - Creates BalanceTransaction and updates user balance

### Pricing Calculation

```
subtotal = amount + charges + delivery_fee
tax_amount = subtotal * (tax_percentage / 100)
total_amount = subtotal + tax_amount
tax_percentage = TaxSetting.percentage (default 15%)
```

---

## Development Workflows

### Running the Application

```bash
# Start Laravel server
php artisan serve --host=192.168.1.211 --port=8001

# Test at http://192.168.1.211:8001

# Database setup
php artisan migrate:fresh --seed       # Reset with seeders
composer update                        # Update packages
php artisan passport:install           # Generate API tokens for Passport
```

### API Testing

- Postman collections: `Dorra_Alaseel_Complete_API.postman_collection.json`, `Twilio_OTP_Postman_Collection.json`
- OTP flow documented in `TWILIO_SETUP_SUMMARY.md`
- Booking flow documented in `FLUTTER_API_DOCS.md`

### Common Tasks

**Add a new endpoint:**

1. Create method in controller (`app/Http/Controllers/Api/V1/*/` for API or `app/Http/Controllers/*/` for web)
2. Add route in `routes/api.php`, `routes/user.php`, `routes/vendor.php`, or `routes/admin.php`
3. Use `Response::success()` or `Response::error()` helpers from `App\Http\Helpers\Response`
4. Validate input with `Validator::make()` using Illuminate validation rules
5. Return JSON or Blade view depending on route type

**Add a booking feature:**

1. Check `CarBooking` model for required relationships
2. Update in `CarBookingController` (API) or booking request handler (web)
3. Call `BookingBalanceService` if balance/pricing involved
4. Create notifications via `VendorNotification::create()` or `UserNotification::create()`
5. Trigger push notifications via `PushNotificationHelper`

**Handle KYC verification:**

1. Check `User.kyc_verified` status (0/1/2/3)
2. Access KYC data via `User->kyc()` relationship → `UserKycData.data` (JSON field)
3. Admin middleware: `kyc.verification.guard` for gated features

---

## Project-Specific Conventions

### Constants Over Magic Strings

Always use constants from `App\Constants\`:

- `CarBookingConst` - Booking statuses, messages
- `GlobalConst` - VERIFIED (1), PENDING (2), REJECTED (3)
- `PaymentGatewayConst` - Gateway names, payment types
- `NotificationConst` - Notification types
- `AdminRoleConst` - Admin role names

**Example:** Use `CarBookingConst::ACCEPTED` instead of hardcoding `1`

### Response Format: Consistent JSON Structure

All API responses follow:

```php
// Success
Response::success(['message1', 'message2'], ['data' => $value], 200)
// Returns: { "message": { "success": [...] }, "data": {...}, "type": "success" }

// Error
Response::error(['error1'], ['details' => $value], 400)
// Returns: { "message": { "error": [...] }, "data": {...}, "type": "error" }
```

### Model Relationships: Eager Load to Avoid N+1

- **Bad:** `CarBooking::all()->map(fn($b) => $b->cars->name)` (N+1 queries)
- **Good:** `CarBooking::with(['cars'])->get()` (1 query)
- List all relations in each model and use `->with()` in controllers

### Validation: Validate Before Create/Update

```php
$rules = [
    'email' => 'required|email',
    'mobile' => 'nullable|numeric',
    'amount' => 'required|numeric|min:0.01'
];
Validator::make($request->all(), $rules)->validate(); // Throws ValidationException
```

### Notifications: Email + Push + Database Records

When notifying vendors/users:

1. Create `VendorNotification` or `UserNotification` record (database)
2. Send email via `Notification::send()`
3. Send push via `PushNotificationHelper::send()`

### File Organization

- **Controllers:** Group by role (User, Vendor, Admin) or feature
- **Models:** One model per file, relationships defined inline
- **Services:** Business logic extracted from controllers
- **Helpers:** Reusable functions (Response, PushNotification, PaymentGateway)
- **Traits:** Shared behaviors (ControlDynamicInputFields, Authorize)
- **Constants:** All magic values here

---

## Integration Points

### External Services

- **Twilio:** OTP via SMS (TwilioService, configured in routes/api.php)
- **Payment Gateways:** PayPal, Stripe, PayStack, Authorize.net, Coinbase (PaymentGatewayHelper)
- **Push Notifications:** Firebase or Pusher (PushNotificationHelper)
- **Email:** Laravel Mail (User/Vendor notifications)

### Key Helper Classes

- `App\Http\Helpers\Response` - JSON response formatting
- `App\Http\Helpers\PaymentGateway` - Gateway initialization and routing
- `App\Http\Helpers\PushNotificationHelper` - Push notifications
- `App\Services\BookingBalanceService` - Pricing, tax, balance calculations
- `App\Services\TwilioService` - SMS OTP

---

## When Adding Features

1. **Always check the route guard** - Is it user, vendor, or admin?
2. **Eager load relationships** - Include `->with(['relation1', 'relation2'])`
3. **Use services for business logic** - Don't put complex math in controllers
4. **Use constants for magic values** - CarBookingConst, GlobalConst, PaymentGatewayConst
5. **Validate input early** - `Validator::make()->validate()`
6. **Follow response format** - `Response::success()` or `Response::error()`
7. **Check for KYC verification** - Use `User.kyc_verified` or middleware
8. **Create notifications for user-facing actions** - Booking, payment, cancellation
9. **Test with actual role** - Test via `auth:api`, `auth:vendor`, `auth:admin` guards

---

## Documentation References

- **Booking system:** BOOKING_HISTORY_SCHEMA.md, FLUTTER_API_DOCS.md
- **Twilio OTP:** TWILIO_SETUP_SUMMARY.md
- **Payment gateways:** TWILIO_INTEGRATION_GUIDE.md
- **API testing:** POSTMAN_TESTING_GUIDE.md
- **System setup:** QUICK_START_GUIDE.md

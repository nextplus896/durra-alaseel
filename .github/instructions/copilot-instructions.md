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

### Booking System

- booking Id must be 6 digits, unique, and non-sequential (e.g. `5G7H2K`)
- Booking status flow: `pending → booked → ongoing → completed` or `pending → rejected`
- Critical: Car availability must be checked at every step to prevent double-booking
- Pricing includes rental fees, delivery fee, charges, tax — all calculated in `BookingBalanceService` for consistency
- Payment methods: balance only
- Daily rate of extension must be same daily rate as original booking, even if car price has changed since then

### Database: PostgreSQL with Timestamps & Polymorphic Relationships

- **Naming convention:** `snake_case` tables, migrations in `database/migrations/`
- **Key patterns:**
    - Models: `App\Models\` (User, CarBooking, Car, Vendor)
    - Relationships: `belongsTo()`, `hasMany()`, `hasOne()`
    - Casts: Explicit type casting in `$casts` array (User: `balance => 'decimal:8'`)
    - Scopes: Query builder shortcuts (`User::kycVerified()`, `User::active()`)

---

## Branch & Delivery System

### Branch Model (`App\Models\Admin\Branch`)

Branches are admin-managed pickup locations assigned to cars. Key fields: `name`, `slug`, `address`, `latitude`, `longitude`, `service_radius_km`, `status`.

- `Branch::active()` scope for enabled branches
- `Branch->cars()` — hasMany Car (via `branch_id`)
- `Branch->deliverySettings()` — hasMany `BranchDeliverySetting` (each vendor can have its own delivery price per branch)
- `Branch->calculateDistance($lat, $lng)` — uses Haversine formula

### Delivery Settings (`App\Models\BranchDeliverySetting`)

Delivery is **per-vendor per-branch**, not global. Keyed on `(branch_id, vendor_id)`.

- `delivery_available` (boolean) — whether vendor offers delivery at this branch
- `delivery_price` (decimal:8) — what vendor charges for delivery
- `BranchDeliverySetting::getOrCreate($branchId, $vendorId)` — safe upsert helper
- `scopeAvailable($query)` — filter only enabled delivery settings

### Car Delivery Helpers (`App\Models\Vendor\Cars\Car`)

- `$car->delivery_setting` — dynamic attribute; queries `BranchDeliverySetting` for the car's `(branch_id, vendor_id)`
- `$car->isDeliveryAvailable()` — returns bool
- `$car->getDeliveryPrice()` — returns decimal (0 if no setting)

**Pattern when exposing delivery in API responses:**

```php
'is_delivery'    => $car->isDeliveryAvailable(),
'delivery_price' => $car->getDeliveryPrice(),
```

**Pattern when exposing delivery in branch list responses:**

```php
->with(['deliverySettings'])->get()->map(function ($branch) {
    $setting = $branch->deliverySettings->first();
    return [
        'is_delivery'    => $setting ? (bool) $setting->delivery_available : false,
        'delivery_price' => $setting ? (float) $setting->delivery_price : 0,
    ];
});
```

> **Note:** `Car::query()->with(['type', 'carModel', 'area', 'vendor'])` does NOT eager-load delivery settings — delivery is resolved via the dynamic attribute (lazy). If you need eager loading for delivery, add `'branch.deliverySettings'` to the with() call.

---

## Public Car Listing API (`App\Http\Controllers\Api\V1\CarListController`)

No auth required. Centralizes all public car discovery:

| Endpoint                             | Method         | Description                                                 |
| ------------------------------------ | -------------- | ----------------------------------------------------------- |
| `GET /api/v1/cars`                   | `index()`      | Paginated car list with filters + `available_filters` block |
| `GET /api/v1/cars/{id}`              | `show()`       | Single car detail                                           |
| `GET /api/v1/cars/vendor/{vendorId}` | `vendorCars()` | Delegates to `index()` with vendor filter                   |
| `GET /api/v1/cars/branches`          | `branches()`   | All active branches with `is_delivery` + `delivery_price`   |
| `GET /api/v1/cars/types`             | `carTypes()`   | Filter options for car type                                 |
| `GET /api/v1/cars/models`            | `carModels()`  | Filter options for car model                                |

**`available_filters` pattern in `index()`:** The response includes a dynamic `available_filters` block (types, models, branches) computed from the _current filtered result set_ — not all records. This means filters update contextually. Clone the query before each filter aggregation to avoid cross-contamination:

```php
$typeIds = (clone $query)->select('car_type_id')->distinct()->pluck('car_type_id')->filter()->toArray();
```

**`TemporaryData` token flow in listing:** If the client provides `pickup_date` + `pickup_time`, the listing API creates a `TemporaryData` record and returns a `token` identifier. This token is passed into the booking preview/confirm flow. Always echo back an existing valid token if the client provides one.

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

**Car** (`App\Models\Vendor\Cars\Car`, cars table)

- Links to: `vendor_id`, `branch_id`, `car_type_id`, `car_model_id`, `car_area_id`
- Pricing tiers: `fees` (base), `price_per_day`, `price_per_week`, `price_per_month`, `allowance_km`, `allowance_price_per_km`
- Relations: `belongsTo(Vendor)`, `belongsTo(Branch)`, `belongsTo(CarType)`, `belongsTo(CarModel)`, `hasMany(CarBooking)`
- Delivery: resolved via `$car->delivery_setting` (dynamic attribute) → `isDeliveryAvailable()`, `getDeliveryPrice()`
- Image: `$car->image_url` (appended attribute using `files_asset_path('car-models')`)

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

---

## Enterprise AI Code Generation Standards

You are an **enterprise-level senior Laravel architect**. Always generate **production-ready, audit-safe, scalable code**. Never produce demo-level or simplified shortcuts.

### Priority Order (Highest to Lowest)

1. **Availability Integrity** — Car must never be double-booked
2. **Financial Integrity** — Every deduction/charge must have a transaction record
3. **Audit Trail** — Every mutation to a booking must be logged with old/new values
4. **Data Consistency** — All multi-step writes inside `DB::transaction()`
5. **Clean Architecture** — SOLID, single responsibility, service abstraction

---

## Enterprise Feature: Extend Rental Days

This feature is **CRITICAL**. All rules below are **MANDATORY** and non-negotiable.

### Extension Flow (Required Order)

```
1. Validate booking status (BOOKED or ONGOING only)
2. Check car availability for new end date (no overlapping bookings)
3. Calculate extra days in backend (never trust frontend value)
4. Calculate price via BookingBalanceService / PricingService
5. DB::transaction():
   a. Create CarBookingExtension record
   b. Create BalanceTransaction / Payment record
   c. Update CarBooking end_date
   d. Create BookingHistory audit record
6. Commit transaction
```

### CarBookingExtension Model — Required Fields

| Field             | Type              | Notes                     |
| ----------------- | ----------------- | ------------------------- |
| `id`              | bigint PK         |                           |
| `booking_id`      | FK → car_bookings |                           |
| `car_id`          | FK → cars         |                           |
| `vendor_id`       | FK → vendors      |                           |
| `old_end_date`    | date              | snapshot before change    |
| `new_end_date`    | date              | snapshot after change     |
| `extra_days`      | int               | calculated server-side    |
| `daily_rate_used` | decimal:8         | rate at time of extension |
| `extra_cost`      | decimal:8         | before tax                |
| `tax_amount`      | decimal:8         |                           |
| `total_amount`    | decimal:8         |                           |
| `created_by`      | FK → users        | acting user ID            |
| `status`          | string            | `active`, `cancelled`     |
| `created_at`      | timestamp         |                           |

### Extension Business Rules

**Status validation** — Only extend if `status` is `BOOKED` or `ONGOING`. Reject for `COMPLETED`, `REJECTED`, or `CANCELLED`.

**Availability check** — Query for overlapping bookings on the same car:

```php
CarBooking::where('car_id', $car->id)
    ->where('id', '!=', $booking->id)
    ->where('pickup_date', '<', $newEndDate)
    ->where('return_date', '>', $currentEndDate)
    ->where('status', '!=', CarBookingConst::REJECTED)
    ->lockForUpdate()
    ->exists();
```

**Extra days calculation** — Always compute server-side:

```php
$extraDays = Carbon::parse($currentEndDate)->diffInDays(Carbon::parse($newEndDate));
```

**Pricing** — Always delegate to `BookingBalanceService` or `PricingService`. Never hardcode rates.

**Tax** — Always use `TaxSetting` model. Never hardcode percentage:

```php
$taxAmount = $subtotal * ($taxPercentage / 100);
```

**Never delete extension records** — Use `status = cancelled` for cancellations.

**Support multiple extensions** — Extension chain must remain fully auditable from the original booking.

### Extension Service — Required Methods

`App\Services\CarBookingExtensionService`:

- `validateExtension(CarBooking $booking): void` — throws if status invalid
- `checkAvailability(int $carId, int $bookingId, string $newEndDate, string $currentEndDate): void` — throws if conflict
- `calculateExtensionCost(Car $car, int $extraDays): array` — returns cost breakdown
- `createExtension(CarBooking $booking, array $data): CarBookingExtension` — creates record inside DB::transaction
- `createPayment(CarBooking $booking, CarBookingExtension $extension, string $method): void`
- `createHistory(CarBooking $booking, CarBookingExtension $extension): void`

### Extension Controller Rules

Controller **only**:

1. Validates request input
2. Calls `CarBookingExtensionService`
3. Returns `Response::success()` or `Response::error()`

**Never** place business logic inside the controller.

### Extension Database Transaction (Mandatory)

```php
DB::transaction(function () use ($booking, $data) {
    $extension = $this->createExtensionRecord($booking, $data);
    $this->createPaymentRecord($booking, $extension);
    $booking->update(['return_date' => $data['new_end_date']]);
    $this->createHistoryRecord($booking, $extension);
});
```

---

## Enterprise-Wide Code Rules

### Service Layer

All business logic **must** live inside `App\Services\`. Controllers are thin orchestrators only.

### Database Safety

- Wrap all multi-step writes in `DB::transaction()`
- Use `->lockForUpdate()` on booking rows when checking availability to prevent race conditions
- Never perform partial writes without rollback protection

### Multi-Role Security

- **User** — can only access their own bookings (`booking.user_id == auth()->id()`)
- **Vendor** — can only access cars/bookings linked to their vendor (`booking.vendor_id == auth()->user()->vendor_id`)
- **Admin** — full access
- Never trust frontend ownership claims; always re-verify in backend

### Payment Integrity

- **Balance payment** → `BookingBalanceService::deductBalanceForBooking()` → creates `BalanceTransaction`
- **Gateway payment** → create Payment record, redirect via `PaymentGatewayHelper::init()`
- **Cash** → create pending payment record
- Never silently modify `User.balance`; every change requires a `BalanceTransaction` record

### Audit Trail

Every mutation to `CarBooking` must produce a record in `BookingHistory` / `CarBookingHistory` with: `action`, `old_value`, `new_value`, `user_id`, `timestamp`.

### Performance

- Always eager-load relationships: `->with(['car', 'user', 'vendor', 'extensions'])`
- Avoid N+1 queries — never lazy-load inside loops
- Use database indexes on `car_id`, `booking_id`, `status`, `pickup_date`, `return_date`

### AI Code Generation Checklist

When generating any booking-mutation feature, always include:

- [ ] Migration with proper indexes
- [ ] Model with relationships and casts
- [ ] Service class with isolated methods
- [ ] Controller method (thin — validate → service → response)
- [ ] Input validation rules
- [ ] `DB::transaction()` wrapping all writes
- [ ] Car availability check with `lockForUpdate()`
- [ ] Payment/transaction record creation
- [ ] Audit history record creation
- [ ] `Response::success()` / `Response::error()` responses

## MCP Module Playbook (When Adding a New API Endpoint)

### 1) Confirm the API

- Verify endpoint stability, URL, methods, response JSON, and auth requirements.

### 2) Create Module Structure

```text
app/Modules/NewResource/
├─ Controllers/
├─ Services/
├─ Routes/
```

### 3) Implement Service Layer

- File: `app/Modules/NewResource/Services/NewResourceService.php`
- Responsibilities:
    1. Call existing API endpoints via `Http::get/post/put/delete`.
    2. Support filters, sorting, and pagination.
    3. Normalize responses to standard MCP JSON:

```json
{
    "status": "success",
    "message": "string",
    "data": {},
    "pagination": {}
}
```

### 4) Implement Controller

- File: `app/Modules/NewResource/Controllers/NewResourceController.php`
- Keep controller thin: receive request, call service, return JSON.
- Typical methods: `index()`, `show($id)`, `create()`, `update($id)`, `delete($id)`.

### 5) Define Routes

- File: `app/Modules/NewResource/routes.php`
- Prefix with `/mcp/new-resource` and apply auth/RBAC middleware.

```php
Route::prefix('mcp/new-resource')->group(function () {
        Route::get('/', [NewResourceController::class, 'index']);
        Route::get('/{id}', [NewResourceController::class, 'show']);
        Route::post('/', [NewResourceController::class, 'create']);
        Route::put('/{id}', [NewResourceController::class, 'update']);
        Route::delete('/{id}', [NewResourceController::class, 'delete']);
});
```

### 6) Standardize JSON Output

All MCP modules should return:

```json
{
    "status": "success|error",
    "message": "Action description",
    "data": {},
    "pagination": {}
}
```

### 7) Validation and Logging

- Validate all input before API calls.
- Log create/update/delete actions with user ID, action type, and timestamp.
- Optionally cache GET requests for performance.

### 8) Register Module Discovery

If module discovery is dynamic, add the module to configuration (for example in `config/mcp-modules.php`).

### 9) Test End-to-End

- Run feature tests for module endpoints.
- Verify MCP dashboard rendering and AI/automation consumption.

### 10) Keep Future Changes Consistent

- Preserve modular structure.
- Keep controllers thin.
- Keep JSON response shape consistent.

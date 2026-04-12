# Dorra Alaseel — Flutter Frontend Hand-off

Single authoritative reference for the Flutter AI agent implementing the Dorra Alaseel mobile app.

---

## 1. Setup

| Item            | Value                                                 |
| --------------- | ----------------------------------------------------- |
| Base URL        | `https://{domain}/api/v1`                             |
| Auth header     | `Authorization: Bearer {token}`                       |
| Content-Type    | `application/json`                                    |
| User API prefix | `/api/v1/user/` (all require `auth:api` unless noted) |

---

## 2. Universal Response Envelope

Every endpoint returns the same wrapper:

```json
// Success
{
  "message": { "success": ["Human-readable message"] },
  "data": { ... },
  "type": "success"
}

// Error / Validation
{
  "message": { "error": ["Error message(s)"] },
  "data": null,
  "type": "error"
}

// Warning
{
  "message": { "error": ["Warning message"] },
  "data": null,
  "type": "warning"
}
```

**HTTP status codes:**

| Code | Meaning              |
| ---- | -------------------- |
| 200  | Success              |
| 400  | Business logic error |
| 422  | Validation failure   |
| 404  | Resource not found   |
| 500  | Server error         |

`message` is always an array — handle multiple strings.

---

## 3. Global / Public Endpoints (No Auth)

### App Settings

| Method | Path                        | Purpose                         |
| ------ | --------------------------- | ------------------------------- |
| GET    | `/settings/basic-settings`  | App config, tax rate, site name |
| GET    | `/settings/splash-screen`   | Splash screen content           |
| GET    | `/settings/onboard-screens` | Onboarding slides               |
| GET    | `/settings/languages`       | Available locales               |
| GET    | `/settings/countries`       | Country + dial-code list        |

### Car Listing (Public Browse)

| Method | Path                      | Query Params                                                                           |
| ------ | ------------------------- | -------------------------------------------------------------------------------------- |
| GET    | `/cars`                   | `sort` (price_asc\|price_desc), `car_type_id`, `car_model_id`, `branch_id`, `per_page` |
| GET    | `/cars/types`             | —                                                                                      |
| GET    | `/cars/models`            | `car_type_id`                                                                          |
| GET    | `/cars/branches`          | — (active branches with delivery info)                                                 |
| GET    | `/cars/vendor/{vendorId}` | `sort`                                                                                 |
| GET    | `/cars/{id}`              | —                                                                                      |

---

## 4. Authentication

All auth routes share middleware `api.user.auth.guard`.  
Full prefix: `/api/v1/`

### Register

`POST /register`

```json
{
    "firstname": "string (required, max:60)",
    "lastname": "string (required, max:60)",
    "email": "string (required, email)",
    "password": "string (required, confirmed)",
    "password_confirmation": "string",
    "mobile_code": "string (e.g. +966)",
    "mobile": "string"
}
```

### Login

`POST /login`

```json
{
    "credentials": "email or username",
    "password": "string"
}
```

**Response `data`:**

```json
{
    "token": "passport_access_token",
    "user_info": {
        "id": 1,
        "firstname": "John",
        "lastname": "Doe",
        "fullname": "John Doe",
        "username": "johndoe",
        "email": "john@example.com",
        "mobile_code": "+966",
        "mobile": "501234567",
        "full_mobile": "+966501234567",
        "email_verified": 0,
        "kyc_verified": 0,
        "two_factor_status": 0,
        "two_factor_verified": 0,
        "two_factor_secret": "base32_secret_or_null"
    },
    "authorization": {
        "status": true,
        "token": "email_verify_token_if_unverified_or_null"
    }
}
```

After login: if `email_verified = 0` → trigger email verification before allowing bookings.

### Password Reset Flow

| Step | Method | Path                           | Body                                         |
| ---- | ------ | ------------------------------ | -------------------------------------------- |
| 1    | POST   | `/password/forgot/find/user`   | `credentials` (email/username)               |
| 2    | POST   | `/password/forgot/verify/code` | `token`, `code`                              |
| 3    | GET    | `/password/forgot/resend/code` | —                                            |
| 4    | POST   | `/password/forgot/reset`       | `token`, `password`, `password_confirmation` |

### Email Verification (requires `auth:api`)

| Method | Path                          |
| ------ | ----------------------------- |
| GET    | `/authorize/mail/send/code`   |
| GET    | `/authorize/mail/resend/code` |
| POST   | `/authorize/mail/verify/code` |

---

## 5. Profile

Prefix: `/user/profile/`

### Get Profile

`GET /user/profile/info`

**Response `data`:**

```json
{
  "instructions": { "kyc_verified": "0=Default, 1=Approved, 2=Pending, 3=Rejected" },
  "user_info": {
    "id": 1, "firstname": "...", "lastname": "...", "username": "...",
    "email": "...", "mobile_code": "...", "mobile": "...",
    "image": "filename_or_null",
    "kyc_verified": 0,
    "country": "...", "city": "...", "state": "...", "postal_code": "...", "address": "...",
    "kyc": { "data": [], "reject_reason": null }
  },
  "image_paths": {
    "base_url": "https://...",
    "path_location": "images/user/profile/",
    "default_image": "default.png"
  },
  "countries": [...]
}
```

Profile image URL = `base_url + path_location + image` (fall back to `base_url + path_location + default_image`)

### Update Profile

`POST /user/profile/info/update` — `multipart/form-data`

| Field       | Type    | Rule                                  |
| ----------- | ------- | ------------------------------------- |
| firstname   | string  | required, max:60                      |
| lastname    | string  | required, max:60                      |
| mobile_code | string  | nullable, max:20                      |
| mobile      | string  | required, max:20                      |
| country     | string  | nullable, max:50                      |
| state       | string  | nullable, max:50                      |
| city        | string  | nullable, max:50                      |
| postal_code | numeric | nullable                              |
| address     | string  | nullable, max:250                     |
| image       | file    | nullable, jpg/png/svg/webp, max 10 MB |

### Update Password

`POST /user/profile/password/update`

```json
{
    "current_password": "string",
    "password": "string (min:6, confirmed)",
    "password_confirmation": "string"
}
```

### Other Profile Actions

| Method | Path                           | Notes                |
| ------ | ------------------------------ | -------------------- |
| POST   | `/user/profile/delete-account` | Soft-deletes account |
| POST   | `/user/logout`                 | Revokes Bearer token |

---

## 6. KYC / Identification

| Method | Path                                          | Notes                  |
| ------ | --------------------------------------------- | ---------------------- |
| GET    | `/user/identification/info`                   | Current KYC status     |
| GET    | `/user/identification/all`                    | All submitted docs     |
| POST   | `/user/identification/national-id/upload`     | Upload national ID     |
| DELETE | `/user/identification/national-id/delete`     | Remove national ID     |
| POST   | `/user/identification/driving-license/upload` | Upload driving licence |
| DELETE | `/user/identification/driving-license/delete` | Remove driving licence |

**KYC status values on `user.kyc_verified`:**

| Value | Meaning              |
| ----- | -------------------- |
| 0     | Unverified (default) |
| 1     | Approved             |
| 2     | Pending review       |
| 3     | Rejected             |

---

## 7. Dashboard & Notifications

| Method | Path                    | Notes                    |
| ------ | ----------------------- | ------------------------ |
| GET    | `/user/dashboard`       | Summary stats            |
| GET    | `/user/notifications`   | Notification list        |
| GET    | `/user/transaction/log` | Full transaction history |

---

## 8. Balance & Wallet

### Balance

| Method | Path                          | Body / Notes                             |
| ------ | ----------------------------- | ---------------------------------------- |
| GET    | `/user/balance/`              | Current balance                          |
| GET    | `/user/balance/history`       | Transaction history                      |
| POST   | `/user/balance/recharge`      | `amount`, `currency`, `gateway_currency` |
| GET    | `/user/balance/tax-settings`  | Current tax config                       |
| POST   | `/user/balance/calculate-tax` | `amount` → returns tax breakdown         |
| POST   | `/user/balance/check`         | `amount` → confirms sufficient funds     |

### Wallet

| Method | Path                        |
| ------ | --------------------------- |
| GET    | `/user/wallet/`             |
| POST   | `/user/wallet/recharge`     |
| GET    | `/user/wallet/transactions` |

---

## 9. Branches & Delivery

| Method | Path                                | Body                                 |
| ------ | ----------------------------------- | ------------------------------------ |
| GET    | `/user/branches/`                   | —                                    |
| GET    | `/user/branches/{id}`               | —                                    |
| POST   | `/user/branches/check-service-area` | `latitude`, `longitude`              |
| POST   | `/user/branches/cars-with-delivery` | `latitude`, `longitude`              |
| POST   | `/user/branches/delivery-price`     | `branch_id`, `latitude`, `longitude` |
| POST   | `/user/check-delivery-area`         | Check delivery eligibility           |

---

## 10. Car Booking — Full Flow

### Booking Status Reference

| Value | Label     | Can Cancel | Can Extend |
| ----- | --------- | ---------- | ---------- |
| 0     | Pending   | Yes        | No         |
| 1     | Booked    | Yes        | No         |
| 2     | Ongoing   | No         | **Yes**    |
| 3     | Completed | No         | No         |
| 4     | Rejected  | No         | No         |

---

### Step 1 — Search Cars

`POST /user/car-booking/search/car`

```json
{
    "car_type": 1,
    "pickup_date": "2026-04-10",
    "pickup_time": "14:00",
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "Riyadh, Saudi Arabia"
    },
    "round_pickup_date": null,
    "round_pickup_time": null
}
```

Rules:

- `pickup_date` + `pickup_time` must be in the future
- `round_pickup_date` must be after initial pickup if provided

**Response `data`:**

```json
{
    "token": "unique_search_token",
    "cars": [
        /* CarResource array */
    ],
    "data_path": { "base_url": "...", "image_path": "..." }
}
```

Save `token` — pass it to preview and confirm.

---

### Step 1.5 — Validate Pickup Date/Time Against Branch Working Hours

`POST /user/car-booking/validate-datetime`

Call this **after the user selects a car** (you have `car_id`) and **before navigating to preview**. It confirms the chosen pickup slot is within the branch's operating hours.

**Request:**

```json
{
    "car_id": 5,
    "pickup_date": "2026-04-15",
    "pickup_time": "09:30 AM"
}
```

| Field         | Type        | Notes                                                                |
| ------------- | ----------- | -------------------------------------------------------------------- |
| `car_id`      | integer     | Required. Must exist in `cars` table.                                |
| `pickup_date` | date string | Required. Any parseable date: `YYYY-MM-DD`, `DD-MM-YYYY`, etc.       |
| `pickup_time` | string      | Required. 12-hour (`09:30 AM`) or 24-hour (`09:30`) — both accepted. |

**Success (200):**

```json
{
    "message": {
        "success": ["Pickup date and time are within branch working hours."]
    },
    "data": {
        "is_valid": true,
        "day_name": "Wednesday",
        "open_time": "08:00 AM",
        "close_time": "08:00 PM",
        "pickup_date": "15-04-2026",
        "pickup_time": "09:30 AM"
    },
    "type": "success"
}
```

**Error — Branch closed that day (422):**

```json
{
    "message": { "error": ["Branch is closed on Friday."] },
    "data": {
        "is_valid": false,
        "day_of_week": 5,
        "day_name": "Friday"
    },
    "type": "error"
}
```

**Error — Outside working hours (422):**

```json
{
    "message": {
        "error": [
            "Pickup time must be between 08:00 AM and 06:00 PM on Wednesday."
        ]
    },
    "data": {
        "is_valid": false,
        "day_name": "Wednesday",
        "open_time": "08:00 AM",
        "close_time": "06:00 PM"
    },
    "type": "error"
}
```

**UI guidance:**

- Show `open_time` / `close_time` from the error payload as a hint below the time picker (e.g. _"Branch hours: 08:00 AM – 06:00 PM"_).
- For a closed-day error show: _"Branch is closed on [day_name]."_
- Do **not** let the user proceed to Step 2 while `is_valid` is false.
- This endpoint is read-only — safe to call on every time-picker change (debounce ~500 ms).

---

### Step 2 — Preview Booking

`GET /user/car-booking/preview`

Query params: `token`, `car_id`, `rental_days`, `include_delivery` (0|1)

**Response `data`:**

```json
{
  "token": "string|null",
  "booking_details": { ... },
  "booking_currency": "SAR",
  "car": { /* CarResource */ },
  "user": { "id", "firstname", "lastname", "email", "mobile", "balance" },
  "user_balance": { "balance": 500.00, "has_sufficient_balance": true },
  "delivery_info": { "available": true, "price": 50.00 },
  "pricing_breakdown": {
    "rental_days": 3,
    "allowance_km": 300,
    "subtotal": 450.00,
    "tax_percentage": 15.0,
    "tax_amount": 67.50,
    "total": 517.50,
    "price_rule_applied": "standard"
  },
  "payment-type": ["balance", "cash", "online-payment"],
  "payment_gateways": [
    { "id": 1, "name": "Moyasar", "type": "automatic", "currencies": [...] }
  ]
}
```

**IMPORTANT:** Always display pricing from preview response — never calculate on client.

---

### Step 3 — Confirm Booking

`POST /user/car-booking/confirm`

```json
{
    "car_id": 5,
    "car_slug": "toyota-camry-2024",
    "location": "Riyadh",
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "Riyadh, Saudi Arabia"
    },
    "credentials": "user@example.com",
    "mobile": "+966501234567",
    "round_pickup_date": null,
    "round_pickup_time": null,
    "message": null,
    "token": "search_token_from_step_1",
    "payment": "balance",
    "include_delivery": false,
    "delivery_price": null,
    "gateway_currency": null,
    "gateway_type": null
}
```

`payment` field values:

- `"balance"` — deduct from user wallet
- `"cash"` — pay on return
- gateway slug (e.g. `"moyasar"`) — online payment

**Response `data`:**

```json
{
    "booking_id": 123,
    "trx_id": "2614837",
    "total_amount": 517.5
}
```

Backend recalculates **all** pricing at confirm time. Submitted amounts are ignored.

---

### Step 4 — Payment Methods (Online)

| Method | Path                                                            | Notes                                                                    |
| ------ | --------------------------------------------------------------- | ------------------------------------------------------------------------ |
| GET    | `/user/car-booking/manual/input-fields?alias=`                  | Dynamic form fields for manual gateways                                  |
| POST   | `/user/car-booking/manual/submit`                               | Submit manual payment + file uploads                                     |
| GET    | `/user/car-booking/re-manual/input-fields?trx_id=`              | Re-submit fields (rejected payment)                                      |
| POST   | `/user/car-booking/repayment/submit`                            | Re-submit rejected manual payment                                        |
| GET    | `/user/car-booking/payment-gateway/additional-fields?currency=` | Dynamic gateway fields (e.g. bank list for Gpay)                         |
| POST   | `/user/car-booking/authorize-payment-submit`                    | Authorize.net: `identifier`, `card_number`, `date` (MM/YY), `code` (CVV) |
| POST   | `/user/car-booking/payment/crypto/confirm/{trx_id}`             | Crypto: `transaction_hash`                                               |
| GET    | `/user/car-booking/success/response/{gateway}`                  | **No auth** — gateway callback                                           |
| POST   | `/user/car-booking/success/response/{gateway}`                  | **No auth** — gateway callback                                           |
| GET    | `/user/car-booking/cancel/response/{gateway}`                   | **No auth** — gateway cancel                                             |
| POST   | `/user/car-booking/cancel/response/{gateway}`                   | **No auth** — gateway cancel                                             |
| GET    | `/user/car-booking/redirect/btn/checkout/{gateway}`             | **No auth** — button pay                                                 |

**Do not send `Authorization` header to the callback/cancel/redirect routes.**

---

### Booking History & Cancel

| Method | Path                                | Notes                       |
| ------ | ----------------------------------- | --------------------------- |
| GET    | `/user/car-booking/booking/history` | All bookings for auth user  |
| POST   | `/user/car-booking/cancel`          | Body: `{"booking_id": 123}` |

Cancel rules:

- Only status **0** (pending) or **1** (booked) can be cancelled
- If `paid_from_balance = true`, wallet is auto-refunded immediately

---

## 11. Rental Extension (Ongoing Bookings Only)

### Business Rules (enforced server-side)

| Rule               | Detail                                                          |
| ------------------ | --------------------------------------------------------------- |
| Status gate        | Booking must be **status 2** (ongoing)                          |
| Timing             | Must request **≥ 2 hours** before `return_date`                 |
| Per-request limit  | 1 – 90 days                                                     |
| Total duration cap | Original + all extensions ≤ 365 days                            |
| Daily rate         | Uses **original booking's daily rate** — not current car price  |
| Payment            | Collected **on car return** (no upfront charge)                 |
| Availability       | Car conflict-checked for the extension window before confirming |

Use `is_extendable` (bool) from `CarBookingResource` as the definitive UI gate.

---

### Preview Extension (read-only, no side effects)

`GET /user/car-booking/extend/preview?booking_id=123&extension_days=3`

**Response `data`:**

```json
{
    "booking_id": 123,
    "trx_id": "2614837",
    "car_model": "Toyota Camry 2024",
    "current_rental_days": 3,
    "extension_days": 3,
    "new_total_rental_days": 6,
    "current_return_date": "2026-04-13",
    "new_return_date": "2026-04-16",
    "is_available": true,
    "availability_message": "Car is available for extension.",
    "pricing": {
        "price_rule_applied": "standard",
        "base_price": 150.0,
        "extension_days": 3,
        "rental_fees": 450.0,
        "tax_percentage": 15.0,
        "tax_amount": 67.5,
        "total_cost": 517.5
    }
}
```

Show this to the user before committing. If `is_available = false`, display `availability_message` and block the action.

---

### Confirm Extension

`POST /user/car-booking/extend`

```json
{
    "booking_id": 123,
    "extension_days": 3
}
```

**Response `data`:**

```json
{
    "booking": {
        /* CarBookingResource — updated with new return_date and rental_days */
    },
    "extension": {
        /* CarBookingExtensionResource */
    }
}
```

---

### Extension History

`GET /user/car-booking/{bookingId}/extensions`

**Response `data`:**

```json
{
    "booking_id": 123,
    "trx_id": "2614837",
    "extension_count": 2,
    "total_extension_days": 5,
    "current_return_date": "2026-04-18",
    "extensions": [
        /* CarBookingExtensionResource array */
    ]
}
```

---

## 12. CarBookingResource — Full Shape

```json
{
    "id": 123,
    "booking_id": "2614837",
    "car_id": 5,
    "user_id": 1,
    "branch_id": 2,
    "slug": "booking-slug",
    "rental_days": 3,
    "allowance_km": 300,
    "car_model": "Toyota Camry 2024",
    "car_number": "ABC-1234",
    "location": "Riyadh",
    "pickup_latitude": 24.7136,
    "pickup_longitude": 46.6753,
    "pickup_address": "Riyadh, Saudi Arabia",
    "pickup_time": "14:00",
    "pickup_date": "2026-04-10",
    "round_pickup_time": null,
    "round_pickup_date": null,
    "destination": "string",
    "phone": "+966501234567",
    "email": "user@example.com",
    "type": "string",
    "message": null,
    "status": 2,
    "payment_type": "balance",
    "trx_id": "2614837",
    "amount": 450.0,
    "charges": 0.0,
    "delivery_fee": 50.0,
    "tax_amount": 75.0,
    "tax_percentage": 15.0,
    "subtotal": 500.0,
    "total_amount": 575.0,
    "distance": 12.5,
    "balance_deducted": 575.0,
    "is_delivery": true,
    "paid_from_balance": true,
    "return_date": "2026-04-13",
    "original_rental_days": 3,
    "extension_count": 0,
    "total_extension_days": 0,
    "is_extendable": true,
    "days_remaining": 5,
    "total_extension_cost": 0.0,
    "rejection_reason": null,
    "created_at": "2026-04-08T10:00:00.000000Z",
    "updated_at": "2026-04-08T10:00:00.000000Z",
    "cars": {
        /* CarResource */
    },
    "extensions": []
}
```

---

## 13. CarBookingExtensionResource — Full Shape

```json
{
    "id": 45,
    "car_booking_id": 123,
    "extension_days": 3,
    "previous_return_date": "2026-04-13",
    "new_return_date": "2026-04-16",
    "rental_fees": 450.0,
    "tax_percentage": 15.0,
    "tax_amount": 67.5,
    "total_cost": 517.5,
    "daily_rate": 150.0,
    "transacted_at": "2026-04-08T10:00:00.000000Z",
    "created_at": "2026-04-08T10:00:00.000000Z"
}
```

---

## 14. Push Notifications (Pusher Beams)

`GET /user/pusher/beams-auth`

Returns a Beams token for the mobile SDK. The publishable ID is `"user-{user_id}"`.

**Flutter setup:**

1. After login, call this endpoint to get the Beams auth token
2. Register the device with `flutter_pusher_beams` package
3. Subscribe to the `"user-{id}"` interest

---

## 15. Quick-Reference: Discovery Endpoints

| Method | Path                            | Use Case                                        |
| ------ | ------------------------------- | ----------------------------------------------- |
| GET    | `/user/car-booking/area`        | Area picker                                     |
| GET    | `/user/car-booking/type`        | Car type picker                                 |
| POST   | `/user/car-booking/area/types`  | Types filtered by area                          |
| GET    | `/user/car-booking/car/details` | Car detail view (unauthenticated search result) |

---

## 16. Pricing Formula

```
subtotal      = rental_fees + charges + delivery_fee
tax_amount    = subtotal × (tax_percentage / 100)    ← default 15%
total_amount  = subtotal + tax_amount
```

**Never compute this on the client.** Always call `/preview` and display the server response.

---

## 17. Critical Rules for Flutter Implementation

1. **Pricing is server-authoritative** — call preview; display what the backend returns
2. **Search token flow** — `searchCar` → save `token` → pass to `preview` → pass to `confirm`
3. **Booking ID (`trip_id`)** is always a 7-digit string (e.g. `"2614837"`), not an auto-increment ID
4. **Time format** — send `HH:MM` (24-hour); backend accepts both 12h and 24h
5. **Extension guard** — check `is_extendable` on `CarBookingResource` before showing the extend button
6. **Delivery** — delivery availability and price are per-branch per-vendor; read from preview response
7. **Balance refund** — cancellation auto-refunds if `paid_from_balance = true`; no extra call needed
8. **Payment callbacks are unauthenticated** — do NOT add `Authorization` header to `/success/response/` or `/cancel/response/` routes
9. **KYC gate** — middleware blocks booking actions if `kyc_verified ≠ 1`; check on login and show KYC flow if needed
10. **Extension payment** — collected on car return, not upfront; inform the user in the UI

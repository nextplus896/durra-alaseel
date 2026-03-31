# Flutter Car Booking API Documentation

Complete API documentation for car booking preview and confirmation flows for the Flutter mobile application.

---

## Table of Contents

1. [Base URL & Authentication](#base-url--authentication)
2. [Car Booking Preview API](#car-booking-preview-api)
3. [Car Booking Confirmation API](#car-booking-confirmation-api)
4. [Payment Methods](#payment-methods)
5. [Error Handling](#error-handling)
6. [Implementation Examples](#implementation-examples)

---

## Base URL & Authentication

### Base URL

```
https://your-domain.com/api/v1
```

### Headers (All Authenticated Requests)

```
Content-Type: application/json
Authorization: Bearer {access_token}
```

### Getting Access Token

Use the authentication endpoints to obtain a Bearer token:

- **POST** `/register` - For new users
- **POST** `/login` - For existing users

---

## Car Booking Preview API

### Endpoint

**GET** `/user/car-booking/preview`

### Description

Calculates and previews booking details including pricing breakdown, taxes, and delivery options before user confirms the booking.

### Request Parameters

#### Query Parameters (Option 1 - Using Token from Search)

```json
{
    "token": "abc123def456",
    "car_id": 15,
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh, Saudi Arabia"
    }
}
```

#### Query Parameters (Option 2 - Direct Inline Data)

```json
{
    "car_id": 15,
    "car_type": 1,
    "pickup_date": "2026-02-15",
    "pickup_time": "10:30",
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh, Saudi Arabia"
    },
    "include_delivery": false
}
```

### Request Body

```json
{
    "token": "abc123def456789xyz",
    "car_id": 15,
    "car_type": 1,
    "pickup_date": "2026-02-15",
    "pickup_time": "10:30",
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh, Saudi Arabia"
    },
    "include_delivery": false
}
```

### Example Request

```bash
curl -X GET "https://your-domain.com/api/v1/user/car-booking/preview" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "token": "abc123def456",
    "car_id": 15,
    "rental_days": 3
  }'
```

### Success Response (200 OK)

```json
{
    "message": ["Booking data stored in the temporary table"],
    "data": {
        "token": "abc123def456789xyz",
        "booking_details": {
            "car_type": 1,
            "pickup_date": "2026-02-15",
            "pickup_time": "10:30",
            "rental_days": 3,
            "pickup_location": {
                "latitude": 24.7136,
                "longitude": 46.6753,
                "address": "King Fahd Road, Riyadh, Saudi Arabia"
            },
            "round_pickup_date": null,
            "round_pickup_time": null
        },
        "booking_currency": "USD",
        "car": {
            "id": 15,
            "vendor_id": 3,
            "branch_id": 1,
            "car_title": "Luxury Sedan - Premium Experience",
            "car_model": "Camry 2024",
            "car_number": "ABC-1234",
            "seat": 5,
            "year": 2024,
            "fees": "250.00",
            "price": "250.00",
            "price_per_day": "250.00",
            "price_per_week": "1500.00",
            "price_per_month": "5000.00",
            "image": "car-image-1234.jpg",
            "image_url": "https://domain.com/backend/car-models/car-image-1234.jpg",
            "status": 1,
            "approval": true,
            "car_type": {
                "id": 1,
                "name": "Sedan",
                "slug": "sedan"
            },
            "car_model_info": {
                "id": 1,
                "name": "Toyota Camry",
                "image_url": "https://domain.com/backend/car-models/camry.jpg"
            },
            "area": {
                "id": 2,
                "name": "Riyadh"
            },
            "branch": {
                "id": 1,
                "name": "Downtown Branch",
                "slug": "downtown-branch",
                "address": "123 Main Street, City Center",
                "status": 1
            },
            "vendor": {
                "id": 3,
                "name": "John Doe",
                "username": "johndoe"
            },
            "created_at": "2026-01-20T10:30:00+00:00"
        },
        "user": {
            "id": 1,
            "firstname": "Ahmed",
            "lastname": "Ali",
            "fullname": "Ahmed Ali",
            "email": "ahmed@example.com",
            "mobile": "+966501234567",
            "email_verified": true,
            "kyc_verified": true,
            "two_factor_verified": false
        },
        "user_balance": {
            "balance": "1500.00",
            "has_sufficient_balance": true
        },
        "delivery_info": {
            "available": true,
            "price": "50.00"
        },
        "pricing_breakdown": {
            "rental_days": 3,
            "rental_fees": "750.00",
            "delivery_price": "0.00",
            "charges": "0.00",
            "subtotal": "750.00",
            "tax_percentage": 15.0,
            "tax_amount": "112.50",
            "total": "862.50",
            "price_rule_applied": "daily",
            "tax_rate": 15.0
        },
        "payment-type": {
            "online-payment": "online-payment",
            "cash": "cash",
            "balance": "balance"
        },
        "payment_gateways": [
            {
                "id": 1,
                "name": "PayPal",
                "status": 1,
                "currencies": [
                    {
                        "id": 1,
                        "name": "USD",
                        "code": "USD"
                    }
                ]
            },
            {
                "id": 2,
                "name": "Stripe",
                "status": 1,
                "currencies": [
                    {
                        "id": 1,
                        "name": "USD",
                        "code": "USD"
                    }
                ]
            }
        ]
    }
}
```

### Error Response (400/500)

```json
{
    "message": ["Something Went Wrong! Please try again."],
    "data": []
}
```

---

## Car Booking Confirmation API

### Endpoint

**POST** `/user/car-booking/confirm`

### Description

Confirms the car booking and processes payment. Supports three payment methods: balance, cash, or online payment gateways.

### Request Headers

```
Content-Type: application/json
Authorization: Bearer {access_token}
```

### Request Body

```json
{
    "car_id": 15,
    "car_slug": "toyota-camry-2024",
    "location": "Downtown Branch",
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh, Saudi Arabia"
    },
    "credentials": "ahmed@example.com",
    "mobile": "+966501234567",
    "message": "Please prepare the car with extra fuel",
    "token": "abc123def456789xyz",
    "payment": "balance",
    "include_delivery": false,
    "delivery_price": null,
    "round_pickup_date": null,
    "round_pickup_time": null
}
```

### Request Parameters Description

| Parameter           | Type    | Required | Description                                        | Example              |
| ------------------- | ------- | -------- | -------------------------------------------------- | -------------------- |
| `car_id`            | integer | ✓        | Car ID to book                                     | `15`                 |
| `car_slug`          | string  | ✓        | Car slug identifier                                | `toyota-camry-2024`  |
| `location`          | string  | ✓        | Pickup location/branch name                        | `Downtown Branch`    |
| `rental_days`       | integer | ✓        | Number of rental days (min: 1)                     | `3`                  |
| `pickup_location`   | object  | ✓        | Map coordinates and address for pickup location    | See below            |
| `credentials`       | email   | ✓        | Booking contact email                              | `ahmed@example.com`  |
| `mobile`            | string  | ✗        | Booking contact phone                              | `+966501234567`      |
| `message`           | string  | ✗        | Special message/notes for vendor                   | `Extra fuel needed`  |
| `token`             | string  | ✗        | Booking session token from preview                 | `abc123def456789xyz` |
| `payment`           | string  | ✓        | Payment method: `balance`, `cash`, or gateway slug | `balance`            |
| `include_delivery`  | boolean | ✗        | Add delivery service                               | `false`              |
| `delivery_price`    | decimal | ✗        | Delivery cost (if included)                        | `50.00`              |
| `round_pickup_date` | date    | ✗        | Return date (YYYY-MM-DD)                           | `2026-02-18`         |
| `round_pickup_time` | time    | ✗        | Return time (HH:mm)                                | `10:30`              |

#### Pickup Location Object Structure

```json
{
    "latitude": 24.7136,
    "longitude": 46.6753,
    "address": "King Fahd Road, Riyadh, Saudi Arabia"
}
```

| Field       | Type   | Required | Description                             | Data Type     |
| ----------- | ------ | -------- | --------------------------------------- | ------------- |
| `latitude`  | double | ✓        | GPS latitude coordinate                 | `double`      |
| `longitude` | double | ✓        | GPS longitude coordinate                | `double`      |
| `address`   | string | ✓        | Human-readable address from Google Maps | `string(500)` |

### Payment Methods

#### 1. Balance Payment

**Request:**

```json
{
    "car_id": 15,
    "car_slug": "toyota-camry-2024",
    "location": "Downtown Branch",
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh, Saudi Arabia"
    },
    "credentials": "ahmed@example.com",
    "mobile": "+966501234567",
    "token": "abc123def456789xyz",
    "payment": "balance",
    "include_delivery": false
}
```

**Success Response (200):**

```json
{
    "message": ["Booking Successful! Amount deducted from your balance."],
    "data": {
        "booking_id": 125,
        "trx_id": "CBK12345678ABCDEF",
        "amount_deducted": "862.50",
        "new_balance": "637.50"
    }
}
```

**Error Response (Insufficient Balance):**

```json
{
    "message": [
        "Insufficient balance. Your balance is 500.00, required amount is 862.50"
    ],
    "data": {
        "current_balance": "500.00",
        "required_amount": "862.50",
        "shortfall": "362.50"
    }
}
```

#### 2. Cash Payment

**Request:**

```json
{
    "car_id": 15,
    "car_slug": "toyota-camry-2024",
    "location": "Downtown Branch",
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh, Saudi Arabia"
    },
    "credentials": "ahmed@example.com",
    "mobile": "+966501234567",
    "token": "abc123def456789xyz",
    "payment": "cash",
    "include_delivery": false
}
```

**Success Response (200):**

```json
{
    "message": ["Booking Successful!"],
    "data": {
        "booking_id": 125,
        "trx_id": "CBK12345678ABCDEF",
        "total_amount": "862.50"
    }
}
```

#### 3. Online Payment Gateway

**Request (for Automatic Gateway - Auto Redirect):**

```json
{
    "car_id": 15,
    "car_slug": "toyota-camry-2024",
    "location": "Downtown Branch",
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh, Saudi Arabia"
    },
    "credentials": "ahmed@example.com",
    "mobile": "+966501234567",
    "token": "abc123def456789xyz",
    "payment": "paypal",
    "include_delivery": false,
    "gateway_type": "automatic",
    "gateway_currency": "USD"
}
```

**Success Response (200):**

```json
{
    "message": ["Payment gateway response successful"],
    "data": {
        "redirect_url": "https://paypal.com/checkout?token=ABC123",
        "redirect_links": {
            "approval_url": "https://paypal.com/approve?token=ABC123",
            "return_url": "https://your-domain.com/api/v1/user/car-booking/success/response/paypal?token=XYZ789"
        },
        "action_type": "redirect",
        "address_info": {
            "country": "US",
            "state": "CA",
            "city": "San Francisco"
        },
        "identifier": "TEMP_BOOKING_12345"
    }
}
```

**Request (for Manual Gateway - Manual Submission):**

```json
{
    "car_id": 15,
    "car_slug": "toyota-camry-2024",
    "location": "Downtown Branch",
    "rental_days": 3,
    "pickup_location": {
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh, Saudi Arabia"
    },
    "credentials": "ahmed@example.com",
    "mobile": "+966501234567",
    "token": "abc123def456789xyz",
    "payment": "bank-transfer",
    "include_delivery": false,
    "gateway_type": "manual",
    "gateway_currency": "USD",
    "transaction_id": "TRX-2026-01-24-12345"
}
```

**Success Response (200):**

```json
{
    "message": ["Booking Successful!"],
    "data": {
        "booking_id": 125,
        "trx_id": "CBK12345678ABCDEF",
        "total_amount": "862.50"
    }
}
```

### Error Responses

#### Validation Error (422)

```json
{
    "message": [
        "The car id field is required.",
        "The credentials field must be a valid email."
    ],
    "data": []
}
```

#### Invalid Token (400)

```json
{
    "message": ["Something went wrong! Please try again"],
    "data": []
}
```

#### Insufficient Balance (400)

```json
{
    "message": [
        "Insufficient balance. Your balance is 500.00, required amount is 862.50"
    ],
    "data": {
        "current_balance": "500.00",
        "required_amount": "862.50",
        "shortfall": "362.50"
    }
}
```

---

## Payment Methods

### 1. Balance Payment

- Deducts amount directly from user wallet
- No additional charges
- Requires sufficient balance
- Instant confirmation

### 2. Cash Payment

- Payment collected at pickup
- Includes transaction charges (fixed + percentage)
- No balance deduction
- Requires vendor confirmation

### 3. Online Payment Gateways

Supported gateways (check availability):

- **PayPal** (slug: `paypal`)
- **Stripe** (slug: `stripe`)
- **PayStack** (slug: `paystack`)
- **Authorize.net** (slug: `authorize`)
- **Coinbase** (slug: `coinbase`)

---

## Pricing Breakdown Logic

### Rental Fee Calculation (Tiered Pricing)

The system applies different pricing rules based on rental duration:

```
1-7 days    → Daily rate (price_per_day)
8-30 days   → Weekly rate (price_per_week)
31+ days    → Monthly rate (price_per_month)

Example:
- 3 days rental = 3 × $250 (daily rate) = $750
- 10 days rental = 10 × $214.29 (weekly rate) = $2,142.90
- 35 days rental = 35 × $166.67 (monthly rate) = $5,833.45
```

### Total Amount Calculation

```
Subtotal = Rental Fees + Delivery Price + Charges

Tax Amount = Subtotal × (Tax Percentage / 100)

Total = Subtotal + Tax Amount

Example:
Rental Fees: $750.00
Delivery: $0.00
Charges: $0.00
Subtotal: $750.00
Tax (15%): $112.50
Total: $862.50
```

---

## Error Handling

### Common Error Status Codes

| Code | Meaning          | Example                                    |
| ---- | ---------------- | ------------------------------------------ |
| 200  | Success          | Booking confirmed                          |
| 400  | Bad Request      | Invalid parameters or insufficient balance |
| 422  | Validation Error | Missing required fields                    |
| 500  | Server Error     | Unexpected server error                    |

### Error Response Format

```json
{
    "message": ["Error message 1", "Error message 2"],
    "data": []
}
```

### Handling Validation Errors

```json
{
    "message": [
        "The car id field is required.",
        "The rental days field must be at least 1.",
        "The credentials field must be a valid email."
    ],
    "data": []
}
```

---

## Implementation Examples

### Flutter Implementation

#### 1. Preview Booking

```dart
Future<void> previewBooking() async {
  try {
    final response = await http.get(
      Uri.parse('$baseUrl/user/car-booking/preview').replace(
        queryParameters: {
          'car_id': '15',
          'rental_days': '3',
          'token': 'abc123def456',
        },
      ),
      headers: {
        'Authorization': 'Bearer $accessToken',
        'Content-Type': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final pricingBreakdown = data['data']['pricing_breakdown'];

      print('Rental Fees: ${pricingBreakdown['rental_fees']}');
      print('Tax: ${pricingBreakdown['tax_amount']}');
      print('Total: ${pricingBreakdown['total']}');
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

#### 2. Confirm Booking with Balance

```dart
Future<void> confirmBooking() async {
  try {
    final response = await http.post(
      Uri.parse('$baseUrl/user/car-booking/confirm'),
      headers: {
        'Authorization': 'Bearer $accessToken',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'car_id': 15,
        'car_slug': 'toyota-camry-2024',
        'location': 'Downtown Branch',
        'rental_days': 3,
        'pickup_location': {
          'latitude': 24.7136,
          'longitude': 46.6753,
          'address': 'King Fahd Road, Riyadh, Saudi Arabia',
        },
        'credentials': 'ahmed@example.com',
        'mobile': '+966501234567',
        'token': 'abc123def456',
        'payment': 'balance',
        'include_delivery': false,
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final bookingId = data['data']['booking_id'];
      final trxId = data['data']['trx_id'];
      final newBalance = data['data']['new_balance'];

      print('Booking confirmed! ID: $bookingId');
      print('Transaction ID: $trxId');
      print('New balance: $newBalance');
    } else if (response.statusCode == 400) {
      final error = jsonDecode(response.body);
      print('Error: ${error['message'][0]}');
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

#### 3. Confirm Booking with Online Payment

```dart
Future<void> confirmBookingOnlinePayment() async {
  try {
    final response = await http.post(
      Uri.parse('$baseUrl/user/car-booking/confirm'),
      headers: {
        'Authorization': 'Bearer $accessToken',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'car_id': 15,
        'car_slug': 'toyota-camry-2024',
        'location': 'Downtown Branch',
        'rental_days': 3,
        'pickup_location': {
          'latitude': 24.7136,
          'longitude': 46.6753,
          'address': 'King Fahd Road, Riyadh, Saudi Arabia',
        },
        'credentials': 'ahmed@example.com',
        'mobile': '+966501234567',
        'token': 'abc123def456',
        'payment': 'paypal',
        'gateway_currency': 'USD',
        'gateway_type': 'automatic',
        'include_delivery': false,
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final redirectUrl = data['data']['redirect_url'];

      // Launch payment URL in browser or WebView
      if (await canLaunch(redirectUrl)) {
        await launch(redirectUrl);
      }
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

---

## Important Notes

1. **Pricing Calculation**: All pricing calculations are done on the backend. Never trust frontend-calculated amounts for confirmation.

2. **Token Management**: Use the `token` returned from preview for confirmation to maintain booking state.

3. **Balance Validation**: Always check `has_sufficient_balance` before attempting balance payment.

4. **Tax Rate**: Tax percentage is dynamic and retrieved from admin settings. Always use the `tax_percentage` from the response.

5. **Delivery Service**: Check `delivery_info.available` before offering delivery option to users.

6. **Payment Gateway Response**: After online payment, the system handles callback and updates booking status automatically.

7. **Error Handling**: Always parse error messages from the `message` array for user-friendly display.

---

## Additional Resources

- [Authentication API Docs](FLUTTER_API_DOCS.md)
- [Cars API Documentation](CARS_API_FLUTTER_INTEGRATION.md)
- [Postman Collection](Dorra_Alaseel_Complete_API.postman_collection.json)

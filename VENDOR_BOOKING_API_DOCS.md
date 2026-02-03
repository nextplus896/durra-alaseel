# Vendor Booking Request API Documentation

This document describes the Vendor Booking Request API endpoints with enhanced user information fields including name, phone, email, driving license, and KYC status.

## Base URL

```
http://192.168.1.211:8001/api/v1
```

---

## Authentication

All vendor endpoints require authentication using Sanctum/Passport token.

**Header Required:**

```
Authorization: Bearer {vendor_api_token}
```

**Middleware:** `auth:vendor_api`, `kyc.verification.guard`

---

## Vendor Booking Requests

### Get All Booking Requests

Retrieves all pending and ongoing booking requests for the authenticated vendor's cars with complete user information.

**Endpoint:** `GET /vendor/booking/requests`

**Authentication:** Required (Vendor API Token)

**Query Parameters:** None

---

### Request Example

```http
GET /api/v1/vendor/booking/requests HTTP/1.1
Host: 192.168.1.211:8001
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
Accept: application/json
```

---

### Response (200 OK)

```json
{
    "message": ["Requests fetch successfully"],
    "data": {
        "bookings": [
            {
                "id": 1,
                "car_id": 5,
                "branch_id": 2,
                "user_id": 2,
                "slug": "sedan",
                "phone": "1112",
                "email": "aymansaaddev1@gmail.com",
                "trip_id": null,
                "location": "Riyadh, King Fahd Road",
                "destination": "Jeddah, Al Hamra District",
                "payment_type": "cash",
                "trx_id": "TRX-20260123-001",
                "amount": "500.00000000",
                "charges": "50.00000000",
                "delivery_fee": "100.00000000",
                "tax_amount": "97.50000000",
                "tax_percentage": "15.00",
                "subtotal": "650.00000000",
                "total_amount": "747.50000000",
                "is_delivery": 1,
                "paid_from_balance": 0,
                "distance": "950.00000000",
                "pickup_time": "10:30:00",
                "round_pickup_time": null,
                "pickup_date": "2026-01-25",
                "round_pickup_date": null,
                "message": "Please ensure the car is clean and fueled",
                "status": 1,
                "created_at": "2026-01-23T14:30:00.000000Z",
                "updated_at": "2026-01-23T14:30:00.000000Z",
                "cars": {
                    "id": 5,
                    "vendor_id": 3,
                    "car_type_id": 2,
                    "car_model_id": 8,
                    "name": "Toyota Camry 2024",
                    "slug": "toyota-camry-2024",
                    "image": "cars/camry-2024.jpg",
                    "price": "500.00000000",
                    "fees": "50.00000000",
                    "status": 1
                },
                "user_info": {
                    "name": "ayman saad",
                    "firstname": "ayman",
                    "lastname": "saad",
                    "email": "aymansaaddev1@gmail.com",
                    "phone": "201099613699",
                    "mobile_code": "",
                    "driving_license": "SA-12345678",
                    "kyc_status": 1,
                    "kyc_status_string": "Verified"
                }
            },
            {
                "id": 2,
                "car_id": 7,
                "branch_id": 1,
                "user_id": 1,
                "slug": "suv",
                "phone": "01099613699",
                "email": "aymansaaddev@gmail.com",
                "trip_id": null,
                "location": "Dammam, Corniche",
                "destination": "Khobar, Prince Turkey Street",
                "payment_type": "wallet",
                "trx_id": "TRX-20260123-002",
                "amount": "750.00000000",
                "charges": "75.00000000",
                "delivery_fee": "0.00000000",
                "tax_amount": "123.75000000",
                "tax_percentage": "15.00",
                "subtotal": "825.00000000",
                "total_amount": "948.75000000",
                "is_delivery": 0,
                "paid_from_balance": 1,
                "distance": "25.00000000",
                "pickup_time": "14:00:00",
                "round_pickup_time": "18:00:00",
                "pickup_date": "2026-01-24",
                "round_pickup_date": "2026-01-24",
                "message": null,
                "status": 0,
                "created_at": "2026-01-23T15:00:00.000000Z",
                "updated_at": "2026-01-23T15:00:00.000000Z",
                "cars": {
                    "id": 7,
                    "vendor_id": 3,
                    "car_type_id": 3,
                    "car_model_id": 12,
                    "name": "Nissan Patrol 2023",
                    "slug": "nissan-patrol-2023",
                    "image": "cars/patrol-2023.jpg",
                    "price": "750.00000000",
                    "fees": "75.00000000",
                    "status": 1
                },
                "user_info": {
                    "name": "ayman saad",
                    "firstname": "ayman",
                    "lastname": "saad",
                    "email": "aymansaaddev@gmail.com",
                    "phone": "201099613699",
                    "mobile_code": "",
                    "driving_license": null,
                    "kyc_status": 0,
                    "kyc_status_string": "Unverified"
                }
            },
            {
                "id": 3,
                "car_id": 5,
                "branch_id": null,
                "user_id": null,
                "slug": "sedan",
                "phone": "0555123456",
                "email": "guest@example.com",
                "trip_id": null,
                "location": "Mecca, Aziziyah",
                "destination": "Taif, Al Hawiyah",
                "payment_type": "cash",
                "trx_id": "TRX-20260123-003",
                "amount": "300.00000000",
                "charges": "30.00000000",
                "delivery_fee": "50.00000000",
                "tax_amount": "57.00000000",
                "tax_percentage": "15.00",
                "subtotal": "380.00000000",
                "total_amount": "437.00000000",
                "is_delivery": 1,
                "paid_from_balance": 0,
                "distance": "85.00000000",
                "pickup_time": "08:00:00",
                "round_pickup_time": null,
                "pickup_date": "2026-01-26",
                "round_pickup_date": null,
                "message": "Guest booking",
                "status": 0,
                "created_at": "2026-01-23T16:00:00.000000Z",
                "updated_at": "2026-01-23T16:00:00.000000Z",
                "cars": {
                    "id": 5,
                    "vendor_id": 3,
                    "car_type_id": 2,
                    "car_model_id": 8,
                    "name": "Toyota Camry 2024",
                    "slug": "toyota-camry-2024",
                    "image": "cars/camry-2024.jpg",
                    "price": "300.00000000",
                    "fees": "30.00000000",
                    "status": 1
                },
                "user_info": {
                    "name": "Guest",
                    "firstname": "Guest",
                    "lastname": null,
                    "email": "guest@example.com",
                    "phone": "0555123456",
                    "mobile_code": null,
                    "driving_license": null,
                    "kyc_status": 0,
                    "kyc_status_string": "Unverified"
                }
            }
        ],
        "image-path": {
            "base_url": "http://192.168.1.211:8001",
            "image_path": "public/frontend/images/site-section"
        }
    }
}
```

---

## Response Fields

### Booking Object

| Field               | Type          | Description                                                |
| ------------------- | ------------- | ---------------------------------------------------------- |
| `id`                | integer       | Booking ID                                                 |
| `car_id`            | integer       | Car ID (FK to cars table)                                  |
| `branch_id`         | integer/null  | Branch ID (FK to branches table)                           |
| `user_id`           | integer/null  | User ID (null for guest bookings)                          |
| `slug`              | string        | Car type slug (sedan, suv, etc.)                           |
| `phone`             | string        | Booking contact phone                                      |
| `email`             | string        | Booking contact email                                      |
| `trip_id`           | string/null   | Trip identifier                                            |
| `location`          | string        | Pickup location                                            |
| `destination`       | string        | Drop-off destination                                       |
| `payment_type`      | string        | Payment method: `cash`, `wallet`, `paypal`, `stripe`, etc. |
| `trx_id`            | string        | Transaction ID                                             |
| `amount`            | decimal(28,8) | Base rental amount                                         |
| `charges`           | decimal(28,8) | Service charges                                            |
| `delivery_fee`      | decimal(28,8) | Delivery fee (if `is_delivery` = 1)                        |
| `tax_amount`        | decimal(28,8) | Tax amount calculated                                      |
| `tax_percentage`    | decimal(5,2)  | Tax percentage applied                                     |
| `subtotal`          | decimal(28,8) | Subtotal before tax                                        |
| `total_amount`      | decimal(28,8) | Final total amount                                         |
| `is_delivery`       | boolean       | Whether delivery is required (1=yes, 0=no)                 |
| `paid_from_balance` | boolean       | Whether paid from wallet balance (1=yes, 0=no)             |
| `distance`          | decimal(28,8) | Distance in kilometers                                     |
| `pickup_time`       | time          | Pickup time                                                |
| `round_pickup_time` | time/null     | Return pickup time (round trip)                            |
| `pickup_date`       | date          | Pickup date (YYYY-MM-DD)                                   |
| `round_pickup_date` | date/null     | Return date (round trip)                                   |
| `message`           | text/null     | Customer message/notes                                     |
| `status`            | integer       | Booking status (see status codes below)                    |
| `created_at`        | timestamp     | Creation timestamp                                         |
| `updated_at`        | timestamp     | Last update timestamp                                      |

### Status Codes

| Code | Status    | Description                         |
| ---- | --------- | ----------------------------------- |
| `0`  | Pending   | New booking, awaiting vendor action |
| `1`  | Booked    | Vendor confirmed the booking        |
| `2`  | Ongoing   | Trip is in progress                 |
| `3`  | Completed | Trip completed successfully         |
| `4`  | Rejected  | Vendor rejected the booking         |

**Note:** The API only returns bookings with status `0` (Pending) or `1` (Booked). Completed (`3`) and Rejected (`4`) bookings are excluded.

---

### User Info Object (NEW)

The `user_info` object provides complete user identity information for each booking.

| Field               | Type        | Description                                             |
| ------------------- | ----------- | ------------------------------------------------------- |
| `name`              | string      | Full name (firstname + lastname)                        |
| `firstname`         | string      | User's first name                                       |
| `lastname`          | string/null | User's last name                                        |
| `email`             | string      | User's email address                                    |
| `phone`             | string/null | User's mobile number (full_mobile or mobile)            |
| `mobile_code`       | string/null | Country mobile code                                     |
| `driving_license`   | string/null | **NEW** - User's driving license number                 |
| `kyc_status`        | integer     | **NEW** - KYC verification status (see KYC codes below) |
| `kyc_status_string` | string      | **NEW** - Human-readable KYC status                     |

### KYC Status Codes

| Code | Status String | Description                              |
| ---- | ------------- | ---------------------------------------- |
| `0`  | Unverified    | User has not submitted KYC documents     |
| `1`  | Verified      | KYC approved by admin                    |
| `2`  | Pending       | KYC documents submitted, awaiting review |
| `3`  | Rejected      | KYC documents rejected                   |

---

### Guest Bookings

When `user_id` is `null` (guest booking), the `user_info` object will contain:

```json
{
    "name": "Guest",
    "firstname": "Guest",
    "lastname": null,
    "email": "guest@example.com",
    "phone": "0555123456",
    "mobile_code": null,
    "driving_license": null,
    "kyc_status": 0,
    "kyc_status_string": "Unverified"
}
```

Email and phone are pulled from the booking record (`booking.email`, `booking.phone`).

---

### Car Object

| Field          | Type          | Description                       |
| -------------- | ------------- | --------------------------------- |
| `id`           | integer       | Car ID                            |
| `vendor_id`    | integer       | Vendor who owns this car          |
| `car_type_id`  | integer       | Car type ID (sedan, suv, etc.)    |
| `car_model_id` | integer       | Car model ID                      |
| `name`         | string        | Car name/title                    |
| `slug`         | string        | URL-friendly slug                 |
| `image`        | string        | Car image path (relative)         |
| `price`        | decimal(28,8) | Base rental price                 |
| `fees`         | decimal(28,8) | Service fees                      |
| `status`       | integer       | Car status (1=active, 0=inactive) |

---

## Error Responses

### 401 Unauthorized

Missing or invalid authentication token.

```json
{
    "message": ["Unauthenticated"]
}
```

### 403 Forbidden

Vendor account requires KYC verification.

```json
{
    "message": ["Please complete KYC verification to access bookings"]
}
```

### 500 Server Error

```json
{
    "message": ["Oops! Something went wrong! Please try again"]
}
```

---

## Additional Vendor Booking Actions

### Accept Booking Request

**Endpoint:** `GET /vendor/booking/accept?id={booking_id}`

**Authentication:** Required (Vendor API Token)

**Query Parameters:**

- `id` (integer, required): Booking ID to accept

**Response (200):**

```json
{
    "message": ["Request accepted successfully"]
}
```

**Error Responses:**

- `403`: "Please pay your due amount" (vendor has outstanding payments)
- `404`: Booking not found

---

### Reject Booking Request

**Endpoint:** `GET /vendor/booking/reject?id={booking_id}`

**Authentication:** Required (Vendor API Token)

**Query Parameters:**

- `id` (integer, required): Booking ID to reject

**Response (200):**

```json
{
    "message": ["Request rejected successfully"]
}
```

**Note:** Rejecting a booking will:

1. Set booking status to `4` (Rejected)
2. Process refund if payment was made online
3. Send notification to user

---

### Complete Booking

**Endpoint:** `GET /vendor/booking/complete?id={booking_id}`

**Authentication:** Required (Vendor API Token)

**Query Parameters:**

- `id` (integer, required): Booking ID to complete

**Response (200):**

```json
{
    "message": ["Booking completed successfully"]
}
```

**Note:** Completing a booking will:

1. Set booking status to `3` (Completed)
2. Process vendor payment
3. Record admin profit
4. Send completion notification and email to user

---

## Implementation Notes for Frontend

### 1. Displaying User Information

```javascript
// Example: Display user info in booking card
const booking = response.data.bookings[0];

console.log(`Customer: ${booking.user_info.name}`);
console.log(`Email: ${booking.user_info.email}`);
console.log(`Phone: ${booking.user_info.phone}`);
console.log(`License: ${booking.user_info.driving_license || "Not provided"}`);
console.log(`KYC Status: ${booking.user_info.kyc_status_string}`);
```

### 2. KYC Status Badge Colors

```javascript
function getKycBadgeColor(status) {
    switch (status) {
        case 0:
            return "gray"; // Unverified
        case 1:
            return "green"; // Verified
        case 2:
            return "yellow"; // Pending
        case 3:
            return "red"; // Rejected
        default:
            return "gray";
    }
}
```

### 3. Guest Booking Detection

```javascript
const isGuest = booking.user_id === null;

if (isGuest) {
    console.log("Guest booking - limited information available");
}
```

### 4. Image URL Construction

```javascript
const imageBasePath = response.data["image-path"];
const carImageUrl = `${imageBasePath.base_url}/${imageBasePath.image_path}/${booking.cars.image}`;
```

### 5. Status Filtering

The API automatically filters out completed (`status: 3`) and rejected (`status: 4`) bookings. You'll only receive:

- **Pending** (`status: 0`) - New requests
- **Booked** (`status: 1`) - Accepted but not started

---

## Migration Notes

### Database Changes (2026-01-23)

**New Field Added:**

- `users.driving_license` - VARCHAR(255) NULLABLE

**Updated Models:**

- `User` model now includes `driving_license` in casts
- `CarBooking` eager loads `user` relationship

**API Response Enhancement:**

- All booking responses now include `user_info` object with 9 fields
- Backwards compatible (existing fields unchanged)
- Guest bookings handled gracefully with default values

---

## Testing Endpoints

### Using Postman

1. **Get Vendor API Token** (login first)
2. **Set Authorization Header:**
    ```
    Authorization: Bearer {your_token}
    ```
3. **Test Booking List:**
    ```
    GET http://192.168.1.211:8001/api/v1/vendor/booking/requests
    ```

### Using cURL

```bash
curl -X GET "http://192.168.1.211:8001/api/v1/vendor/booking/requests" \
  -H "Authorization: Bearer YOUR_VENDOR_TOKEN" \
  -H "Accept: application/json"
```

---

## Support & Questions

For API support or questions about implementation, contact the backend development team.

**Last Updated:** January 23, 2026  
**API Version:** v1  
**Laravel Version:** 10.x  
**PHP Version:** 8.2+

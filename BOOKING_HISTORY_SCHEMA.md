# Booking History Response Schema

## Response Structure

```json
{
  "message": {
    "success": ["string"]
  },
  "data": {
    "history": [BookingHistoryItem]
  },
  "type": "string"
}
```

## BookingHistoryItem Schema

| Field               | Type                | Nullable | Description                                                           |
| ------------------- | ------------------- | -------- | --------------------------------------------------------------------- |
| `id`                | integer             | No       | Unique booking identifier                                             |
| `car_id`            | integer             | No       | ID of the booked car                                                  |
| `branch_id`         | integer             | Yes      | Branch ID (if pickup from branch)                                     |
| `user_id`           | integer             | No       | ID of the user who made the booking                                   |
| `slug`              | string              | No       | URL-friendly identifier                                               |
| `phone`             | string              | No       | Contact phone number                                                  |
| `email`             | string              | No       | Contact email address                                                 |
| `trip_id`           | integer             | No       | Unique trip identifier                                                |
| `location`          | string              | No       | Pickup location                                                       |
| `destination`       | string              | No       | Drop-off destination                                                  |
| `payment_type`      | string              | No       | Payment method (e.g., "cash", "card", "wallet")                       |
| `trx_id`            | string              | No       | Transaction reference ID                                              |
| `amount`            | integer/float       | No       | Base rental amount                                                    |
| `charges`           | integer/float       | No       | Additional charges                                                    |
| `delivery_fee`      | integer/float       | No       | Delivery fee (0 if no delivery)                                       |
| `tax_amount`        | integer/float       | No       | Calculated tax amount                                                 |
| `tax_percentage`    | integer/float       | No       | Tax rate percentage                                                   |
| `subtotal`          | integer/float       | No       | Subtotal before tax (amount + charges + delivery_fee)                 |
| `total_amount`      | integer/float       | No       | Final total including tax                                             |
| `is_delivery`       | boolean             | No       | Whether delivery service was used                                     |
| `paid_from_balance` | boolean             | No       | Whether paid from wallet balance                                      |
| `distance`          | integer/float       | No       | Distance in kilometers                                                |
| `pickup_time`       | string (HH:mm:ss)   | No       | Pickup time                                                           |
| `round_pickup_time` | string (HH:mm:ss)   | Yes      | Return pickup time (for round trips)                                  |
| `pickup_date`       | string (YYYY-MM-DD) | No       | Pickup date                                                           |
| `round_pickup_date` | string (YYYY-MM-DD) | Yes      | Return date (for round trips)                                         |
| `message`           | string              | Yes      | Additional notes or messages                                          |
| `status`            | integer             | No       | Booking status (0: pending, 1: confirmed, 2: completed, 3: cancelled) |
| `created_at`        | string (ISO 8601)   | No       | Creation timestamp                                                    |
| `updated_at`        | string (ISO 8601)   | No       | Last update timestamp                                                 |
| `cars`              | CarDetails          | No       | Nested car information object                                         |

## CarDetails Schema (Nested in BookingHistoryItem)

| Field                    | Type              | Nullable | Description                               |
| ------------------------ | ----------------- | -------- | ----------------------------------------- |
| `id`                     | integer           | No       | Unique car identifier                     |
| `vendor_id`              | integer           | No       | ID of the car vendor/owner                |
| `car_area_id`            | integer           | Yes      | Associated car area ID                    |
| `branch_id`              | integer           | Yes      | Associated branch ID                      |
| `car_type_id`            | integer           | No       | Car type/category ID                      |
| `car_model_id`           | integer           | No       | Car model ID                              |
| `slug`                   | string            | No       | URL-friendly car identifier (UUID)        |
| `car_title`              | object            | No       | Multilingual car title object             |
| `car_title.en`           | object            | No       | English title                             |
| `car_title.en.car_title` | string            | No       | Full car title in English                 |
| `car_title.fr`           | object            | No       | French title                              |
| `car_title.fr.car_title` | string            | No       | Full car title in French                  |
| `car_title.es`           | object            | No       | Spanish title                             |
| `car_title.es.car_title` | string            | No       | Full car title in Spanish                 |
| `car_title.ar`           | object            | No       | Arabic title                              |
| `car_title.ar.car_title` | string            | No       | Full car title in Arabic                  |
| `car_model`              | string            | Yes      | Car model name (can be null)              |
| `seat`                   | integer           | No       | Number of seats                           |
| `year`                   | integer           | No       | Manufacturing year                        |
| `experience`             | string            | Yes      | Experience level or description           |
| `car_number`             | string            | Yes      | License plate/registration number         |
| `fees`                   | string            | No       | Daily rental fee (decimal string)         |
| `image`                  | string            | No       | Image filename                            |
| `status`                 | integer           | No       | Car status (0: inactive, 1: active)       |
| `approval`               | integer           | No       | Approval status (0: pending, 1: approved) |
| `created_at`             | string (ISO 8601) | No       | Creation timestamp                        |
| `updated_at`             | string (ISO 8601) | No       | Last update timestamp                     |
| `image_url`              | string            | No       | Full URL to car image                     |

## Status Codes

### Booking Status

- `0` - Pending
- `1` - Confirmed
- `2` - Completed
- `3` - Cancelled

### Payment Types

- `cash` - Cash payment
- `card` - Card payment
- `wallet` - Wallet/balance payment
- `stripe` - Stripe payment gateway
- `paypal` - PayPal payment gateway

## Example Response

```json
{
    "message": {
        "success": ["History fetched successfully!"]
    },
    "data": {
        "history": [
            {
                "id": 6,
                "car_id": 12,
                "branch_id": null,
                "user_id": 1,
                "slug": "chevrolet",
                "phone": "01096613699",
                "email": "aymansaadhack@gmail.com",
                "trip_id": 34923834,
                "location": "makka",
                "destination": "makka",
                "payment_type": "cash",
                "trx_id": "ZuFmDKqa4gR9mpR6",
                "amount": 6500,
                "charges": 2300,
                "delivery_fee": 0,
                "tax_amount": 1320,
                "tax_percentage": 15,
                "subtotal": 8800,
                "total_amount": 10120,
                "is_delivery": false,
                "paid_from_balance": false,
                "distance": 10,
                "pickup_time": "21:03:00",
                "round_pickup_time": null,
                "pickup_date": "2026-01-17",
                "round_pickup_date": null,
                "message": "",
                "status": 1,
                "created_at": "2026-01-17T19:03:44.000000Z",
                "updated_at": "2026-01-17T19:03:44.000000Z",
                "cars": {
                    "id": 12,
                    "vendor_id": 7,
                    "car_area_id": null,
                    "branch_id": null,
                    "car_type_id": 17,
                    "car_model_id": 3,
                    "slug": "85ab30e3-522f-4371-911e-3f2c89527e95",
                    "car_title": {
                        "en": {
                            "car_title": "Chevrolet | Tahoe | 2023 Tahoe 2023"
                        },
                        "fr": {
                            "car_title": "Chevrolet | Tahoe | 2023 Tahoe 2023"
                        },
                        "es": {
                            "car_title": "Chevrolet | Tahoe | 2023 Tahoe 2023"
                        },
                        "ar": {
                            "car_title": "Chevrolet | Tahoe | 2023"
                        }
                    },
                    "car_model": null,
                    "seat": 8,
                    "year": 2023,
                    "experience": null,
                    "car_number": null,
                    "fees": "650.00000000",
                    "image": "0ef4c51f-7d4a-48d6-ae5c-24fa04a994e6.webp",
                    "status": 1,
                    "approval": 1,
                    "created_at": "2025-12-09T19:35:43.000000Z",
                    "updated_at": "2025-12-09T21:56:03.000000Z",
                    "image_url": "https://durraalaseel.nextoneplus.com/public/backend/images/car-models/0ef4c51f-7d4a-48d6-ae5c-24fa04a994e6.webp"
                }
            }
        ]
    },
    "type": "success"
}
```

## Pricing Calculation

The pricing structure follows this formula:

```
subtotal = amount + charges + delivery_fee
tax_amount = subtotal × (tax_percentage / 100)
total_amount = subtotal + tax_amount
```

### Example from response:

- Amount: 6,500
- Charges: 2,300
- Delivery Fee: 0
- **Subtotal: 8,800**
- Tax (15%): 1,320
- **Total Amount: 10,120**

## Notes

- All monetary values are in the base currency (SAR for Saudi Arabia)
- Dates are in ISO 8601 format
- Times are in 24-hour format (HH:mm:ss)
- The `fees` field in CarDetails is stored as a decimal string for precision
- The `car_title` object supports multiple languages for internationalization
- Round trip fields (`round_pickup_time`, `round_pickup_date`) are null for one-way bookings

# Car Booking API Implementation Changes

**Date:** January 24, 2026  
**Status:** ✅ Completed

---

## Summary

Updated the car booking system to:

1. **Remove `car_area`** - No longer required for booking flow
2. **Make `allowance_km` vendor-only** - Users don't specify, vendors set it later
3. **Add `pickup_location`** - Map-based location with GPS coordinates

---

## Database Changes

### Migration: `2026_01_24_151417_add_pickup_location_fields_to_car_bookings_table`

Added three new columns to `car_bookings` table:

| Column             | Type           | Nullable | Description                            |
| ------------------ | -------------- | -------- | -------------------------------------- |
| `pickup_latitude`  | decimal(10, 8) | Yes      | GPS latitude coordinate (-90 to 90)    |
| `pickup_longitude` | decimal(11, 8) | Yes      | GPS longitude coordinate (-180 to 180) |
| `pickup_address`   | varchar(500)   | Yes      | Human-readable address from maps       |

**Changes:**

- `allowance_km` is now **nullable** (vendor sets this, not users)

**Migration Status:** ✅ Successfully migrated

---

## Code Changes

### 1. CarBooking Model (`app/Models/CarBooking.php`)

**Added casts:**

```php
'pickup_latitude'   => 'double',
'pickup_longitude'  => 'double',
'pickup_address'    => 'string',
```

### 2. CarBookingController Preview Method

**File:** `app/Http/Controllers/Api/V1/User/CarBookingController.php`

#### Changes:

- ❌ Removed `car_area` validation
- ✅ Added `pickup_location` validation (array with latitude, longitude, address)

#### New Validation Rules:

```php
'pickup_location' => 'nullable|array',
'pickup_location.latitude' => 'required_with:pickup_location|numeric|between:-90,90',
'pickup_location.longitude' => 'required_with:pickup_location|numeric|between:-180,180',
'pickup_location.address' => 'required_with:pickup_location|string|max:500',
```

### 3. CarBookingController Confirm Method

**File:** `app/Http/Controllers/Api/V1/User/CarBookingController.php`

#### Changes:

- ❌ Removed `allowance_km` from validation (users don't set this)
- ✅ Added `pickup_location` validation
- ✅ Updated booking creation to extract and store pickup_location data
- ✅ Set `allowance_km` to `null` (vendor will set it later)

#### Booking Creation:

```php
'pickup_latitude' => $pickupLatitude,
'pickup_longitude' => $pickupLongitude,
'pickup_address' => $pickupAddress,
'allowance_km' => null, // Vendor-only field
```

### 4. SearchCar Method

**File:** `app/Http/Controllers/Api/V1/User/CarBookingController.php`

#### Changes:

- ❌ Removed `car_area` query filter
- ❌ Removed `car_area_id` from Car query
- ✅ Added `pickup_location` validation
- ✅ Now searches cars by `car_type_id` only

---

## API Endpoints Updated

### 1. `GET /api/v1/user/car-booking/preview`

**Request Changes:**

```json
{
    "car_id": 15,
    "car_type": 1, // car_area removed
    "pickup_date": "2026-02-15",
    "pickup_time": "10:30",
    "rental_days": 3,
    "pickup_location": {
        // NEW
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh"
    }
}
```

### 2. `POST /api/v1/user/car-booking/confirm`

**Request Changes:**

```json
{
    "car_id": 15,
    "car_slug": "toyota-camry-2024",
    "location": "Downtown Branch",
    "rental_days": 3,
    "pickup_location": {
        // NEW
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh"
    },
    "credentials": "ahmed@example.com",
    // allowance_km REMOVED - vendor sets this
    "payment": "balance"
}
```

### 3. `POST /api/v1/user/car-booking/search/car`

**Request Changes:**

```json
{
    "car_type": 1, // car_area removed
    "pickup_date": "2026-02-15",
    "pickup_time": "10:30",
    "pickup_location": {
        // NEW
        "latitude": 24.7136,
        "longitude": 46.6753,
        "address": "King Fahd Road, Riyadh"
    }
}
```

---

## Testing Checklist

- [x] Migration executed successfully
- [x] Model casts updated
- [x] Preview endpoint validation updated
- [x] Confirm endpoint validation updated
- [x] SearchCar endpoint validation updated
- [x] Booking creation with pickup_location
- [x] allowance_km set to null for users
- [x] Temporary data payload updated
- [x] All methods handle pickup_location correctly

---

## Breaking Changes

⚠️ **Frontend Integration Required:**

1. **Remove `car_area`** from all booking requests
2. **Remove `allowance_km`** input from user booking forms
3. **Add Map Integration** to capture pickup location:
    - Use Google Maps or similar
    - Capture latitude, longitude, and formatted address
    - Send as nested object in request

---

## Next Steps for Flutter Team

1. ✅ **Review Updated Documentation:** [FLUTTER_CAR_BOOKING_API_DOCS.md](FLUTTER_CAR_BOOKING_API_DOCS.md)

2. **Integrate Maps SDK:**

    ```dart
    // Example: Google Maps integration
    GoogleMap(
      onTap: (LatLng position) {
        // Get address from coordinates
        // Store for booking
      }
    )
    ```

3. **Update Request Bodies:**
    - Add `pickup_location` object
    - Remove `car_area` parameter
    - Remove `allowance_km` input

4. **Test Endpoints:**
    - Preview booking with map location
    - Confirm booking with all payment methods
    - Search cars without car_area

---

## Files Modified

1. ✅ `database/migrations/2026_01_24_151417_add_pickup_location_fields_to_car_bookings_table.php`
2. ✅ `app/Models/CarBooking.php`
3. ✅ `app/Http/Controllers/Api/V1/User/CarBookingController.php`
4. ✅ `FLUTTER_CAR_BOOKING_API_DOCS.md`

---

## Rollback Instructions

If needed, rollback using:

```bash
php artisan migrate:rollback --step=1
```

This will remove the pickup_location fields and revert allowance_km to NOT NULL.

---

## Support

For questions or issues, contact the backend development team.

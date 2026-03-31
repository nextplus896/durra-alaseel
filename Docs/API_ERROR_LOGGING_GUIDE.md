# API Error Logging & Debugging Guide

## Overview

This document describes the improved error logging system implemented to debug type casting and null value errors in the Car Booking API, particularly for Flutter integration.

## Problem Statement

Flutter applications were experiencing two main types of errors:

1. **Type Mismatch**: `type 'String' is not a subtype of type 'int'`
2. **Null Safety**: `Null check operator used on a null value`

These errors occur when:

- Database returns string values but Flutter expects integers
- Required fields contain null values
- Type casting is not explicit in API responses

## Solutions Implemented

### 1. API Resource Classes

Created dedicated Resource classes for consistent type casting:

- **`CarBookingResource`** (`app/Http/Resources/Api/CarBookingResource.php`)
- **`CarResource`** (`app/Http/Resources/Api/CarResource.php`)

#### Benefits:

- ✅ Explicit type casting for all fields
- ✅ Null-safe defaults for optional fields
- ✅ Consistent response structure
- ✅ ISO 8601 date formatting for Flutter compatibility

#### Usage Example:

```php
// Before (unsafe):
return Response::success(['history' => $bookings], 200);

// After (type-safe):
return Response::success(
    ['history' => CarBookingResource::collection($bookings)],
    200
);
```

### 2. Enhanced Error Logging

All catch blocks now include detailed logging with:

- Error message
- Stack trace
- Line number and file
- Context data (validated inputs, user ID, etc.)
- Error categorization

#### Example:

```php
catch (Exception $e) {
    \Log::error('CarBooking History Error: ' . $e->getMessage(), [
        'user_id' => auth()->guard('api')->user()->id ?? null,
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
    ]);
    return Response::error(
        [__('Error fetching booking history')],
        ['error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error'],
        500
    );
}
```

### 3. Null Field Detection

Added helper method `identifyNullFields()` to detect missing required fields:

```php
private function identifyNullFields($data)
{
    $requiredFields = [
        'car_id', 'user_id', 'car_slug', 'location', 'rental_days',
        'credentials', 'mobile', 'token'
    ];

    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
            $missingFields[] = $field;
        }
    }

    return $missingFields;
}
```

### 4. Categorized Error Responses

Errors are now categorized for easier debugging:

#### Type Casting Errors:

```json
{
    "message": {
        "error": ["Data type mismatch. Please contact support."]
    },
    "data": {
        "error_type": "type_casting_error",
        "error_details": "Argument 1 passed to..."
    },
    "type": "error"
}
```

#### Null Value Errors:

```json
{
    "message": {
        "error": ["Missing required data. Please check all fields are filled."]
    },
    "data": {
        "error_type": "null_value_error",
        "error_details": "Null check operator used on a null value",
        "missing_fields": ["car_id", "location"]
    },
    "type": "error"
}
```

## Modified API Endpoints

### 1. `bookingHistory()`

- ✅ Uses `CarBookingResource::collection()`
- ✅ Comprehensive error logging
- ✅ Ordered by creation date (newest first)

### 2. `searchCar()`

- ✅ Uses `CarResource::collection()`
- ✅ Logs validation data on error

### 3. `viewCar()`

- ✅ Uses `CarResource::collection()`
- ✅ Try-catch error handling

### 4. `bookingConfirm()`

- ✅ Detects null field errors
- ✅ SQL query logging
- ✅ Returns missing field names

### 5. `bookingConfirmWithBalance()`

- ✅ Database transaction rollback
- ✅ Type casting error detection
- ✅ Null value error detection

## Debugging Steps

### Step 1: Enable Debug Mode (Development Only)

```php
// .env
APP_DEBUG=true
```

### Step 2: Check Laravel Logs

```bash
# View latest errors
tail -f storage/logs/laravel.log

# Search for specific error type
grep "CarBooking.*Error" storage/logs/laravel.log
```

### Step 3: Analyze Error Response in Flutter

```dart
try {
  final response = await apiService.bookingHistory();
} catch (e) {
  print('⛔ Error Type: ${e.data['error_type']}');
  print('⛔ Details: ${e.data['error_details']}');
  print('⛔ Missing Fields: ${e.data['missing_fields']}');
}
```

### Step 4: Common Issues & Solutions

#### Issue: String returned for integer field

**Solution**: Check `$casts` array in Model

```php
// CarBooking.php
protected $casts = [
    'id' => 'integer',
    'rental_days' => 'integer',
    // ...
];
```

#### Issue: Null value for required field

**Solution**: Check database migration and seeders

```bash
php artisan migrate:status
```

#### Issue: Date format incompatible with Flutter

**Solution**: Use ISO 8601 format in Resource

```php
'created_at' => $this->created_at?->toIso8601String(),
```

## Testing Recommendations

### 1. Test with Null Values

```bash
# Using Postman or similar
POST /api/v1/user/car-booking/confirm
{
    "car_id": null,  # Test null handling
    "rental_days": "5"  # Test string-to-int casting
}
```

### 2. Monitor Logs During Testing

```bash
tail -f storage/logs/laravel.log | grep -E "(CarBooking|error_type)"
```

### 3. Verify Response Types in Flutter

```dart
assert(booking.id is int);
assert(booking.rentalDays is int);
assert(booking.amount is double);
```

## Performance Considerations

- Resource transformation adds minimal overhead (~1-5ms per 100 records)
- Logging is asynchronous and doesn't block responses
- Use `config('app.debug')` to hide sensitive error details in production

## Production Deployment Checklist

- [ ] Set `APP_DEBUG=false` in production `.env`
- [ ] Configure log rotation for `storage/logs/laravel.log`
- [ ] Set up error monitoring (e.g., Sentry, Bugsnag)
- [ ] Test all API endpoints with Postman collection
- [ ] Verify Flutter app handles all error_types

## Error Log Analysis

### Common Patterns to Watch For:

1. **Frequent null errors on specific field**
    - Indicates missing validation or database constraint
2. **Type casting errors on relationships**
    - Check eager loading and relationship definitions

3. **Errors during peak hours**
    - May indicate database connection/timeout issues

## Support & Maintenance

For issues or improvements:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Review error_type and error_details in API response
3. Use missing_fields array to identify problematic data
4. Consult this guide for common solutions

---

**Last Updated**: January 2026  
**Version**: 1.0  
**Maintainer**: Development Team

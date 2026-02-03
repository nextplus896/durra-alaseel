# Testing the Improved Error Logging

## Quick Test Guide

### 1. Test Booking History with Proper Type Casting

**Endpoint**: `GET /api/v1/user/car-booking/history`

**Expected Response Structure**:

```json
{
    "message": {
        "success": ["History fetched successfully!"]
    },
    "data": {
        "history": [
            {
                "id": 1, // ✅ int (not "1" string)
                "trip_id": 12345, // ✅ int or null
                "car_id": 5, // ✅ int
                "rental_days": 3, // ✅ int (not "3" string)
                "amount": 150.5, // ✅ float (not "150.50" string)
                "status": 1, // ✅ int
                "is_delivery": true, // ✅ bool (not 1 or "1")
                "created_at": "2026-01-25T10:30:45+00:00" // ✅ ISO 8601
            }
        ]
    },
    "type": "success"
}
```

### 2. Test Error Logging with Missing Fields

**Test Case**: Submit booking with missing fields

**Endpoint**: `POST /api/v1/user/car-booking/confirm`

**Payload** (intentionally incomplete):

```json
{
    "car_id": null,
    "rental_days": "5",
    "payment": "cash"
}
```

**Expected Error Response**:

```json
{
    "message": {
        "error": ["Missing required data. Please check all fields are filled."]
    },
    "data": {
        "error_type": "null_value_error",
        "error_details": "...",
        "missing_fields": ["car_id", "location", "car_slug", "credentials"]
    },
    "type": "error"
}
```

**Expected in Laravel Log**:

```
[2026-01-25 10:45:23] local.ERROR: CarBooking Confirm Error: ...
{
    "data": {
        "car_id": null,
        "location": null,
        ...
    },
    "missing_fields": ["car_id", "location", "car_slug"],
    "trace": "...",
    "line": 559,
    "file": "CarBookingController.php"
}
```

### 3. Test Type Casting Error Detection

**Test Case**: Submit string where integer expected

**Payload**:

```json
{
    "rental_days": "not_a_number"
}
```

**Expected**: Validation error before reaching controller

**If bypasses validation**, you'll see:

```json
{
    "error_type": "type_casting_error",
    "error_details": "Expected int, got string"
}
```

## Postman Collection Test

### Import Test Collection

1. Import `Dorra_Alaseel_Complete_API.postman_collection.json`
2. Test these endpoints:
    - Car Booking History
    - Search Car
    - View Car
    - Confirm Booking

### What to Check:

#### ✅ All numeric IDs are integers

```javascript
// Postman Test Script
pm.test("ID is integer", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.history[0].id).to.be.a("number");
    pm.expect(jsonData.data.history[0].rental_days).to.be.a("number");
});
```

#### ✅ Amounts are floats

```javascript
pm.test("Amount is float", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.history[0].amount).to.be.a("number");
});
```

#### ✅ Booleans are boolean type

```javascript
pm.test("is_delivery is boolean", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.history[0].is_delivery).to.be.a("boolean");
});
```

## Flutter Integration Test

### Before (Error):

```dart
// This would fail with: type 'String' is not a subtype of type 'int'
class Booking {
  final int id;
  final int rentalDays;

  Booking.fromJson(Map<String, dynamic> json)
      : id = json['id'],           // ❌ Crashes if "1" instead of 1
        rentalDays = json['rental_days'];  // ❌ Crashes if "5" instead of 5
}
```

### After (Fixed):

```dart
// Now works because API returns proper types
class Booking {
  final int id;
  final int rentalDays;
  final double amount;
  final bool isDelivery;

  Booking.fromJson(Map<String, dynamic> json)
      : id = json['id'],                    // ✅ Works! Gets int
        rentalDays = json['rental_days'],   // ✅ Works! Gets int
        amount = json['amount'].toDouble(), // ✅ Works! Gets double
        isDelivery = json['is_delivery'];   // ✅ Works! Gets bool
}
```

## Monitor Real-time Logs

### Terminal 1: Start Server

```bash
php artisan serve --host=192.168.1.211 --port=8000
```

### Terminal 2: Watch Logs

```bash
tail -f storage/logs/laravel.log | grep -E "(CarBooking|error_type|missing_fields)"
```

### Terminal 3: Make API Calls

```bash
curl -X GET http://192.168.1.211:8000/api/v1/user/car-booking/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Success Indicators

✅ **No more "String is not a subtype of int" errors in Flutter**  
✅ **Clear error messages with field names**  
✅ **Detailed logs in Laravel showing exact issue**  
✅ **Consistent response types across all endpoints**  
✅ **ISO 8601 dates compatible with Flutter DateTime**

## If Issues Persist

1. **Check Database Schema**

    ```bash
    php artisan db:show
    ```

2. **Verify Model Casts**

    ```php
    // CarBooking.php
    protected $casts = [
        'id' => 'integer',
        'rental_days' => 'integer',
        // ...
    ];
    ```

3. **Clear Cache**

    ```bash
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    ```

4. **Check Logs**
    ```bash
    cat storage/logs/laravel.log | grep "CarBooking.*Error" | tail -20
    ```

## Example Log Output

**Good Request**:

```
[2026-01-25 10:30:45] local.INFO: CarBooking History fetched for user 42
```

**Error with Details**:

```
[2026-01-25 10:35:12] local.ERROR: CarBooking Confirm Error: Null check operator used on a null value
{
    "data": {
        "car_id": 5,
        "location": null,  // ← Problem identified
        "rental_days": 3
    },
    "missing_fields": ["location"],
    "trace": "#0 CarBookingController.php(520)...",
    "line": 520
}
```

---

**Ready to Test!** 🚀

Start your server and test the API endpoints. Check both the HTTP responses and the Laravel logs.

# API Testing Examples - Car Booking with Pickup Location

## Test 1: Preview Booking with Map Location

### Request

```bash
curl -X GET "http://192.168.1.211:8000/api/v1/user/car-booking/preview" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "car_id": 1,
    "car_type": 1,
    "pickup_date": "2026-02-15",
    "pickup_time": "10:30",
    "rental_days": 3,
    "pickup_location": {
      "latitude": 24.7136,
      "longitude": 46.6753,
      "address": "King Fahd Road, Riyadh, Saudi Arabia"
    }
  }'
```

### Expected Response

```json
{
    "message": ["Booking data stored in the temporary table"],
    "data": {
        "token": "abc123xyz456",
        "booking_details": {
            "car_type": 1,
            "pickup_date": "2026-02-15",
            "pickup_time": "10:30",
            "rental_days": 3,
            "pickup_location": {
                "latitude": 24.7136,
                "longitude": 46.6753,
                "address": "King Fahd Road, Riyadh, Saudi Arabia"
            }
        },
        "pricing_breakdown": {
            "rental_days": 3,
            "rental_fees": "750.00",
            "delivery_price": "0.00",
            "tax_amount": "112.50",
            "total": "862.50"
        }
    }
}
```

---

## Test 2: Confirm Booking with Balance Payment

### Request

```bash
curl -X POST "http://192.168.1.211:8000/api/v1/user/car-booking/confirm" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "car_id": 1,
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
    "token": "abc123xyz456",
    "payment": "balance",
    "include_delivery": false
  }'
```

### Expected Response (Success)

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

### Expected Response (Insufficient Balance)

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

## Test 3: Search Cars (No car_area required)

### Request

```bash
curl -X POST "http://192.168.1.211:8000/api/v1/user/car-booking/search/car" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "car_type": 1,
    "pickup_date": "2026-02-15",
    "pickup_time": "10:30",
    "pickup_location": {
      "latitude": 24.7136,
      "longitude": 46.6753,
      "address": "King Fahd Road, Riyadh, Saudi Arabia"
    }
  }'
```

### Expected Response

```json
{
    "message": ["Car search successful"],
    "data": {
        "token": "search_token_xyz789",
        "cars": [
            {
                "id": 1,
                "car_title": "Toyota Camry 2024",
                "price": "250.00",
                "seat": 5
            }
        ]
    }
}
```

---

## Test 4: Database Verification

### Check Booking Record

```sql
SELECT
  id,
  car_id,
  user_id,
  pickup_latitude,
  pickup_longitude,
  pickup_address,
  allowance_km,
  rental_days,
  total_amount,
  payment_type,
  created_at
FROM car_bookings
WHERE id = 125;
```

### Expected Result

```
| id  | pickup_latitude | pickup_longitude | pickup_address                      | allowance_km |
|-----|-----------------|------------------|-------------------------------------|--------------|
| 125 | 24.71360000     | 46.67530000      | King Fahd Road, Riyadh, Saudi Arabia| NULL         |
```

---

## Validation Tests

### Test 5: Invalid Latitude (Out of Range)

**Request:**

```json
{
    "pickup_location": {
        "latitude": 95.0, // Invalid: exceeds 90
        "longitude": 46.6753,
        "address": "Test Address"
    }
}
```

**Expected Error:**

```json
{
    "message": [
        "The pickup location.latitude field must be between -90 and 90."
    ]
}
```

### Test 6: Missing pickup_location Fields

**Request:**

```json
{
    "pickup_location": {
        "latitude": 24.7136
        // Missing longitude and address
    }
}
```

**Expected Error:**

```json
{
    "message": [
        "The pickup location.longitude field is required when pickup location is present.",
        "The pickup location.address field is required when pickup location is present."
    ]
}
```

---

## Flutter Implementation Example

```dart
// Model for pickup location
class PickupLocation {
  final double latitude;
  final double longitude;
  final String address;

  PickupLocation({
    required this.latitude,
    required this.longitude,
    required this.address,
  });

  Map<String, dynamic> toJson() => {
    'latitude': latitude,
    'longitude': longitude,
    'address': address,
  };
}

// API Service method
Future<BookingPreviewResponse> previewBooking({
  required int carId,
  required int carType,
  required String pickupDate,
  required String pickupTime,
  required int rentalDays,
  PickupLocation? pickupLocation,
}) async {
  final response = await http.get(
    Uri.parse('$baseUrl/user/car-booking/preview'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'car_id': carId,
      'car_type': carType,
      'pickup_date': pickupDate,
      'pickup_time': pickupTime,
      'rental_days': rentalDays,
      if (pickupLocation != null) 'pickup_location': pickupLocation.toJson(),
    }),
  );

  if (response.statusCode == 200) {
    return BookingPreviewResponse.fromJson(jsonDecode(response.body));
  } else {
    throw Exception('Failed to preview booking');
  }
}

// Widget to capture location
class LocationPickerWidget extends StatefulWidget {
  final Function(PickupLocation) onLocationSelected;

  const LocationPickerWidget({required this.onLocationSelected});

  @override
  _LocationPickerWidgetState createState() => _LocationPickerWidgetState();
}

class _LocationPickerWidgetState extends State<LocationPickerWidget> {
  GoogleMapController? mapController;
  LatLng? selectedPosition;

  void _onMapTap(LatLng position) async {
    setState(() {
      selectedPosition = position;
    });

    // Get address from coordinates using geocoding
    final placemarks = await placemarkFromCoordinates(
      position.latitude,
      position.longitude,
    );

    if (placemarks.isNotEmpty) {
      final place = placemarks.first;
      final address = '${place.street}, ${place.locality}, ${place.country}';

      widget.onLocationSelected(
        PickupLocation(
          latitude: position.latitude,
          longitude: position.longitude,
          address: address,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return GoogleMap(
      onTap: _onMapTap,
      markers: selectedPosition != null
          ? {
              Marker(
                markerId: MarkerId('pickup'),
                position: selectedPosition!,
              ),
            }
          : {},
      initialCameraPosition: CameraPosition(
        target: LatLng(24.7136, 46.6753), // Riyadh default
        zoom: 12,
      ),
    );
  }
}
```

---

## Notes

1. **car_area is removed** - No longer needed in requests
2. **allowance_km is vendor-only** - Users don't specify this
3. **pickup_location is optional** - Can be sent or omitted
4. **Latitude range:** -90 to 90
5. **Longitude range:** -180 to 180
6. **Address max length:** 500 characters

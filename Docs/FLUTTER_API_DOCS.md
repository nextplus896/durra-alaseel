# Flutter API Documentation

This document describes the API endpoints for the Flutter mobile app.

## Base URL

```
https://your-domain.com/api/v1
```

---

## Authentication

### Register User

**POST** `/register`

Registers a new user account.

**Request Body:**

```json
{
    "firstname": "John",
    "lastname": "Doe",
    "email": "john@example.com",
    "password": "password123",
    "phone": "+966501234567",
    "agree": "on"
}
```

**Response (201):**

```json
{
    "message": ["User successfully registered"],
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "user_info": {
            "id": 1,
            "firstname": "John",
            "lastname": "Doe",
            "fullname": "John Doe",
            "username": "john-doe",
            "email": "john@example.com",
            "mobile_code": null,
            "mobile": "+966501234567",
            "full_mobile": null,
            "email_verified": false,
            "kyc_verified": false,
            "two_factor_verified": false,
            "two_factor_status": 0,
            "two_factor_secret": null
        },
        "authorization": {
            "status": true
        }
    }
}
```

**Note:** The `country` field has been removed. Use `phone` instead.

---

## Push Notifications (Mobile)

This project sends mobile push notifications using **Pusher Beams** (provider: `pusher`).

### How it works

- The backend publishes a push message to a **Pusher Beams User ID**.
- The mobile app logs in (gets a Passport token), then calls a **Beams auth** endpoint to obtain a Beams token.
- The Pusher Beams SDK uses that token to associate the device with the same User ID.

### Beams Auth (User)

**GET** `/user/pusher/beams-auth`

- **Auth:** `Authorization: Bearer <USER_TOKEN>`
- **Response:** Beams token JSON (must be used as-is by the Pusher Beams SDK)

### Beams Auth (Vendor)

**GET** `/vendor/pusher/beams-auth`

- **Auth:** `Authorization: Bearer <VENDOR_TOKEN>`
- **Response:** Beams token JSON

### Notes

- Push credentials are configured from the Admin panel (Push Notification setup): `instance_id` + `primary_key`.
- Server-side push sends use `App\Http\Helpers\PushNotificationHelper`.

---

## Cars API (Public - No Auth Required)

### List All Cars

**GET** `/cars`

Get all available cars with sorting and filtering.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `sort` | string | No | `price_asc` or `price_desc` (default: `price_desc`) |
| `car_type_id` | integer | No | Filter by car type ID |
| `car_model_id` | integer | No | Filter by car model ID |
| `vendor_id` | integer | No | Filter by vendor ID |
| `per_page` | integer | No | Items per page (default: 15, max: 100) |
| `page` | integer | No | Page number |

**Example Request:**

```
GET /api/v1/cars?sort=price_asc&car_type_id=1&per_page=10&page=1
```

**Response (200):**

```json
{
    "message": ["Cars fetched successfully!"],
    "data": {
        "available_filters": {
            "car_types": [
                { "id": 1, "name": "Sedan", "slug": "sedan" },
                { "id": 2, "name": "SUV", "slug": "suv" }
            ],
            "car_models": [
                { "id": 3, "name": "Camry", "car_type_id": 1 },
                { "id": 4, "name": "Corolla", "car_type_id": 1 }
            ]
        },
        "cars": [
            {
                "id": 1,
                "vendor_id": 5,
                "car_title": { "en": "Toyota Camry 2024" },
                "car_model": "Camry",
                "car_number": "ABC-1234",
                "seat": 5,
                "year": 2024,
                "fees": "150.00",
                "price": "150.00",
                "image": "car-image.jpg",
                "image_url": "https://domain.com/storage/car-models/car-image.jpg",
                "status": 1,
                "car_type": {
                    "id": 1,
                    "name": "Sedan",
                    "slug": "sedan"
                },
                "car_model_info": {
                    "id": 3,
                    "name": "Camry",
                    "image_url": "https://domain.com/storage/car-models/camry.jpg"
                },
                "area": {
                    "id": 2,
                    "name": "Riyadh"
                },
                "vendor": {
                    "id": 5,
                    "name": "Ahmed Ali",
                    "username": "ahmed-ali"
                },
                "created_at": "2025-01-15T10:30:00+00:00"
            }
        ],
        "pagination": {
            "total": 50,
            "per_page": 15,
            "current_page": 1,
            "last_page": 4,
            "from": 1,
            "to": 15
        },
        "data_path": {
            "base_url": "https://domain.com",
            "image_path": "storage/site-section"
        }
    }
}
```

---

### Get Car Types (for filter dropdown)

**GET** `/cars/types`

Get all available car types for filter dropdown.

**Response (200):**

```json
  "message": ["Car types fetched successfully!"],
  "data": {
    "car_types": [
      {"id": 1, "name": "Sedan", "slug": "sedan"},
      {"id": 2, "name": "SUV", "slug": "suv"},

Note: The `/cars` list endpoint returns an `available_filters` object containing only the car types and car models that are present in the current filtered result. Use `/cars/types` and `/cars/models` when you need the complete lists.
      {"id": 3, "name": "Van", "slug": "van"}
    ]
  }
}
```

---

### Get Car Models (for filter dropdown)

**GET** `/cars/models`

Get all available car models, optionally filtered by car type.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `car_type_id` | integer | No | Filter by car type ID |

**Example Request:**

```
GET /api/v1/cars/models?car_type_id=1
```

**Response (200):**

```json
{
    "message": ["Car models fetched successfully!"],
    "data": {
        "car_models": [
            {
                "id": 1,
                "name": "Camry",
                "car_type_id": 1,
                "image_url": "https://..."
            },
            {
                "id": 2,
                "name": "Corolla",
                "car_type_id": 1,
                "image_url": "https://..."
            }
        ]
    }
}
```

---

### Get Cars by Vendor

**GET** `/cars/vendor/{vendorId}`

Get all cars from a specific vendor.

**Query Parameters:** Same as "List All Cars"

**Example Request:**

```
GET /api/v1/cars/vendor/5?sort=price_desc
```

---

### Get Single Car Details

**GET** `/cars/{id}`

Get detailed information about a specific car.

**Response (200):**

```json
{
  "message": ["Car fetched successfully!"],
  "data": {
    "car": {
      "id": 1,
      "vendor_id": 5,
      "car_title": {"en": "Toyota Camry 2024"},
      "car_model": "Camry",
      "car_number": "ABC-1234",
      "seat": 5,
      "year": 2024,
      "fees": "150.00",
      "price": "150.00",
      "image": "car-image.jpg",
      "image_url": "https://domain.com/storage/car-models/car-image.jpg",
      "status": 1,
      "car_type": {...},
      "car_model_info": {...},
      "area": {...},
      "vendor": {...},
      "created_at": "2025-01-15T10:30:00+00:00"
    },
    "data_path": {...}
  }
}
```

---

## Vendor Cars API (Auth Required)

### List Vendor's Own Cars

**GET** `/vendor/car/list`

Get the authenticated vendor's own cars with sorting and filtering.

**Headers:**

```
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `sort` | string | No | `price_asc` or `price_desc` (default: `price_desc`) |
| `car_type_id` | integer | No | Filter by car type ID |
| `car_model_id` | integer | No | Filter by car model ID |
| `per_page` | integer | No | Items per page (default: 15, max: 100) |

**Response:** Same structure as public cars list, includes `approval` status.

---

## Flutter Implementation Examples

### Registration Screen (Remove Country, Add Phone)

```dart
// registration_request.dart
class RegistrationRequest {
  final String firstname;
  final String lastname;
  final String email;
  final String password;
  final String phone;  // NEW: Added phone
  // REMOVED: country field

  Map<String, dynamic> toJson() => {
    'firstname': firstname,
    'lastname': lastname,
    'email': email,
    'password': password,
    'phone': phone,
    'agree': 'on',
  };
}
```

### Car List with Sort & Filter

```dart
// car_service.dart
class CarService {
  final Dio _dio;

  Future<CarListResponse> getCars({
    String? sort,         // 'price_asc' or 'price_desc'
    int? carTypeId,
    int? carModelId,
    int? vendorId,
    int perPage = 15,
    int page = 1,
  }) async {
    final queryParams = <String, dynamic>{
      'per_page': perPage,
      'page': page,
    };

    if (sort != null) queryParams['sort'] = sort;
    if (carTypeId != null) queryParams['car_type_id'] = carTypeId;
    if (carModelId != null) queryParams['car_model_id'] = carModelId;
    if (vendorId != null) queryParams['vendor_id'] = vendorId;

    final response = await _dio.get('/cars', queryParameters: queryParams);
    return CarListResponse.fromJson(response.data);
  }

  Future<List<CarType>> getCarTypes() async {
    final response = await _dio.get('/cars/types');
    return (response.data['data']['car_types'] as List)
        .map((e) => CarType.fromJson(e))
        .toList();
  }

  Future<List<CarModel>> getCarModels({int? carTypeId}) async {
    final queryParams = carTypeId != null ? {'car_type_id': carTypeId} : null;
    final response = await _dio.get('/cars/models', queryParameters: queryParams);
    return (response.data['data']['car_models'] as List)
        .map((e) => CarModel.fromJson(e))
        .toList();
  }
}
```

### Sort & Filter UI Example

```dart
// car_list_screen.dart
class CarListScreen extends StatefulWidget {
  @override
  _CarListScreenState createState() => _CarListScreenState();
}

class _CarListScreenState extends State<CarListScreen> {
  String _sortOrder = 'price_desc';  // Default: high to low
  int? _selectedCarTypeId;
  int? _selectedCarModelId;

  void _toggleSort() {
    setState(() {
      _sortOrder = _sortOrder == 'price_desc' ? 'price_asc' : 'price_desc';
    });
    _loadCars();
  }

  Widget _buildSortButton() {
    return IconButton(
      icon: Icon(_sortOrder == 'price_desc'
          ? Icons.arrow_downward
          : Icons.arrow_upward),
      onPressed: _toggleSort,
      tooltip: _sortOrder == 'price_desc'
          ? 'Price: High to Low'
          : 'Price: Low to High',
    );
  }

  Widget _buildFilters() {
    return Row(
      children: [
        // Car Type Dropdown
        DropdownButton<int>(
          value: _selectedCarTypeId,
          hint: Text('Car Type'),
          items: _carTypes.map((type) => DropdownMenuItem(
            value: type.id,
            child: Text(type.name),
          )).toList(),
          onChanged: (value) {
            setState(() => _selectedCarTypeId = value);
            _loadCars();
          },
        ),

        // Car Model Dropdown
        DropdownButton<int>(
          value: _selectedCarModelId,
          hint: Text('Car Model'),
          items: _carModels.map((model) => DropdownMenuItem(
            value: model.id,
            child: Text(model.name),
          )).toList(),
          onChanged: (value) {
            setState(() => _selectedCarModelId = value);
            _loadCars();
          },
        ),
      ],
    );
  }
}
```

---

## Error Responses

All endpoints return errors in this format:

```json
{
    "message": ["Error message here"],
    "data": [],
    "type": "error"
}
```

Common HTTP Status Codes:

- `200` - Success
- `400` - Bad Request (validation errors)
- `401` - Unauthorized
- `404` - Not Found
- `422` - Unprocessable Entity
- `500` - Server Error

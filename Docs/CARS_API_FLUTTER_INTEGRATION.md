# Cars API - Flutter Integration Guide

This document provides comprehensive documentation for integrating the Cars API endpoints into your Flutter application.

## Base URL

```
http://192.168.1.211:8001/api/v1
```

---

## Table of Contents

1. [Get All Cars (with Filters)](#1-get-all-cars-with-filters)
2. [Get Car Types](#2-get-car-types)
3. [Get Car Models](#3-get-car-models)
4. [Get Branches](#4-get-branches)
5. [Get Single Car Details](#5-get-single-car-details)
6. [Get Cars by Vendor](#6-get-cars-by-vendor)
7. [Flutter Implementation Examples](#7-flutter-implementation-examples)

---

## 1. Get All Cars (with Filters)

### Endpoint

```
GET /api/v1/cars
```

### Description

Fetch a paginated list of all available cars with optional filtering and sorting capabilities.

### Query Parameters

| Parameter      | Type    | Required | Description                                        | Example      |
| -------------- | ------- | -------- | -------------------------------------------------- | ------------ |
| `sort`         | string  | No       | Sort by price: `price_asc` or `price_desc`         | `price_desc` |
| `car_type_id`  | integer | No       | Filter by car type ID                              | `1`          |
| `car_model_id` | integer | No       | Filter by car model ID                             | `2`          |
| `vendor_id`    | integer | No       | Filter by vendor ID                                | `5`          |
| `branch_id`    | integer | No       | Filter by branch ID                                | `3`          |
| `per_page`     | integer | No       | Items per page (1-100, default: 15)                | `15`         |
| `page`         | integer | No       | Page number                                        | `1`          |
| `token`        | string  | No       | Booking session token (if continuing booking flow) | -            |
| `pickup_date`  | string  | No       | Pickup date for booking (YYYY-MM-DD)               | `2026-02-15` |
| `pickup_time`  | string  | No       | Pickup time for booking (HH:mm)                    | `10:30`      |

### Example Request

```
GET http://192.168.1.211:8001/api/v1/cars?sort=price_desc&per_page=15&page=1&branch_id=3
```

### Success Response (200 OK)

```json
{
    "message": {
        "success": ["Cars fetched successfully!"]
    },
    "data": {
        "available_filters": {
            "car_types": [
                {
                    "id": 1,
                    "name": "Sedan",
                    "slug": "sedan"
                },
                {
                    "id": 2,
                    "name": "SUV",
                    "slug": "suv"
                }
            ],
            "car_models": [
                {
                    "id": 1,
                    "name": "Toyota Camry",
                    "car_type_id": 1
                },
                {
                    "id": 2,
                    "name": "Honda CR-V",
                    "car_type_id": 2
                }
            ],
            "branches": [
                {
                    "id": 1,
                    "name": "Downtown Branch",
                    "slug": "downtown-branch",
                    "address": "123 Main Street, City Center"
                },
                {
                    "id": 2,
                    "name": "Airport Branch",
                    "slug": "airport-branch",
                    "address": "Airport Road, Terminal 2"
                }
            ]
        },
        "cars": [
            {
                "id": 15,
                "vendor_id": 3,
                "car_title": "Luxury Sedan - Premium Experience",
                "car_model": "Camry 2024",
                "car_number": "ABC-1234",
                "seat": 5,
                "year": 2024,
                "fees": "250.00",
                "price": "250.00",
                "image": "car-image-1234.jpg",
                "image_url": "http://192.168.1.211:8001/backend/car-models/car-image-1234.jpg",
                "status": 1,
                "car_type": {
                    "id": 1,
                    "name": "Sedan",
                    "slug": "sedan"
                },
                "car_model_info": {
                    "id": 1,
                    "name": "Toyota Camry",
                    "image_url": "http://192.168.1.211:8001/backend/car-models/camry.jpg"
                },
                "area": {
                    "id": 2,
                    "name": "City Center"
                },
                "branch": {
                    "id": 1,
                    "name": "Downtown Branch",
                    "slug": "downtown-branch",
                    "address": "123 Main Street, City Center"
                },
                "vendor": {
                    "id": 3,
                    "name": "John Doe",
                    "username": "johndoe"
                },
                "created_at": "2026-01-20T10:30:00+00:00"
            }
        ],
        "pagination": {
            "total": 45,
            "per_page": 15,
            "current_page": 1,
            "last_page": 3,
            "from": 1,
            "to": 15
        },
        "data_path": {
            "base_url": "http://192.168.1.211:8001",
            "image_path": "backend/site-section"
        },
        "token": null
    }
}
```

### Error Response (422 Unprocessable Entity)

```json
{
    "message": {
        "error": [
            "The car type id field must be an integer.",
            "The per page field must be between 1 and 100."
        ]
    },
    "data": []
}
```

---

## 2. Get Car Types

### Endpoint

```
GET /api/v1/cars/types
```

### Description

Fetch all available car types for the filter dropdown.

### Example Request

```
GET http://192.168.1.211:8001/api/v1/cars/types
```

### Success Response (200 OK)

```json
{
    "message": {
        "success": ["Car types fetched successfully!"]
    },
    "data": {
        "car_types": [
            {
                "id": 1,
                "name": "Sedan",
                "slug": "sedan"
            },
            {
                "id": 2,
                "name": "SUV",
                "slug": "suv"
            },
            {
                "id": 3,
                "name": "Luxury",
                "slug": "luxury"
            }
        ]
    }
}
```

---

## 3. Get Car Models

### Endpoint

```
GET /api/v1/cars/models
```

### Description

Fetch all available car models. Optionally filter by car type.

### Query Parameters

| Parameter     | Type    | Required | Description                  | Example |
| ------------- | ------- | -------- | ---------------------------- | ------- |
| `car_type_id` | integer | No       | Filter models by car type ID | `1`     |

### Example Request

```
GET http://192.168.1.211:8001/api/v1/cars/models?car_type_id=1
```

### Success Response (200 OK)

```json
{
    "message": {
        "success": ["Car models fetched successfully!"]
    },
    "data": {
        "car_models": [
            {
                "id": 1,
                "name": "Toyota Camry",
                "car_type_id": 1,
                "image_url": "http://192.168.1.211:8001/backend/car-models/camry.jpg"
            },
            {
                "id": 2,
                "name": "Honda Accord",
                "car_type_id": 1,
                "image_url": "http://192.168.1.211:8001/backend/car-models/accord.jpg"
            }
        ]
    }
}
```

---

## 4. Get Branches

### Endpoint

```
GET /api/v1/cars/branches
```

### Description

Fetch all available branches for the filter dropdown.

### Example Request

```
GET http://192.168.1.211:8001/api/v1/cars/branches
```

### Success Response (200 OK)

```json
{
    "message": {
        "success": ["Branches fetched successfully!"]
    },
    "data": {
        "branches": [
            {
                "id": 1,
                "name": "Downtown Branch",
                "slug": "downtown-branch",
                "address": "123 Main Street, City Center"
            },
            {
                "id": 2,
                "name": "Airport Branch",
                "slug": "airport-branch",
                "address": "Airport Road, Terminal 2"
            },
            {
                "id": 3,
                "name": "North Branch",
                "slug": "north-branch",
                "address": "456 North Avenue"
            }
        ]
    }
}
```

---

## 5. Get Single Car Details

### Endpoint

```
GET /api/v1/cars/{id}
```

### Description

Fetch detailed information for a specific car by its ID.

### Path Parameters

| Parameter | Type    | Required | Description |
| --------- | ------- | -------- | ----------- |
| `id`      | integer | Yes      | Car ID      |

### Query Parameters

| Parameter | Type   | Required | Description           |
| --------- | ------ | -------- | --------------------- |
| `token`   | string | No       | Booking session token |

### Example Request

```
GET http://192.168.1.211:8001/api/v1/cars/15
```

### Success Response (200 OK)

```json
{
    "message": {
        "success": ["Car fetched successfully!"]
    },
    "data": {
        "car": {
            "id": 15,
            "vendor_id": 3,
            "car_title": "Luxury Sedan - Premium Experience",
            "car_model": "Camry 2024",
            "car_number": "ABC-1234",
            "seat": 5,
            "year": 2024,
            "fees": "250.00",
            "price": "250.00",
            "image": "car-image-1234.jpg",
            "image_url": "http://192.168.1.211:8001/backend/car-models/car-image-1234.jpg",
            "status": 1,
            "car_type": {
                "id": 1,
                "name": "Sedan",
                "slug": "sedan"
            },
            "car_model_info": {
                "id": 1,
                "name": "Toyota Camry",
                "image_url": "http://192.168.1.211:8001/backend/car-models/camry.jpg"
            },
            "area": {
                "id": 2,
                "name": "City Center"
            },
            "branch": {
                "id": 1,
                "name": "Downtown Branch",
                "slug": "downtown-branch",
                "address": "123 Main Street, City Center"
            },
            "vendor": {
                "id": 3,
                "name": "John Doe",
                "username": "johndoe"
            },
            "created_at": "2026-01-20T10:30:00+00:00"
        },
        "data_path": {
            "base_url": "http://192.168.1.211:8001",
            "image_path": "backend/site-section"
        },
        "token": null
    }
}
```

### Error Response (404 Not Found)

```json
{
    "message": {
        "error": ["Car not found"]
    },
    "data": []
}
```

---

## 6. Get Cars by Vendor

### Endpoint

```
GET /api/v1/cars/vendor/{vendorId}
```

### Description

Fetch all cars belonging to a specific vendor. Supports the same filters as the main cars endpoint.

### Path Parameters

| Parameter  | Type    | Required | Description |
| ---------- | ------- | -------- | ----------- |
| `vendorId` | integer | Yes      | Vendor ID   |

### Query Parameters

Same as [Get All Cars](#1-get-all-cars-with-filters)

### Example Request

```
GET http://192.168.1.211:8001/api/v1/cars/vendor/5?sort=price_desc
```

### Success Response

Same structure as [Get All Cars](#1-get-all-cars-with-filters)

---

## 7. Flutter Implementation Examples

### 7.1 Data Models

```dart
// lib/models/car_filter.dart
class CarFilter {
  final int? carTypeId;
  final int? carModelId;
  final int? vendorId;
  final int? branchId;
  final String? sort;
  final int perPage;
  final int page;

  CarFilter({
    this.carTypeId,
    this.carModelId,
    this.vendorId,
    this.branchId,
    this.sort = 'price_desc',
    this.perPage = 15,
    this.page = 1,
  });

  Map<String, dynamic> toQueryParams() {
    final params = <String, dynamic>{
      'per_page': perPage.toString(),
      'page': page.toString(),
    };

    if (sort != null) params['sort'] = sort;
    if (carTypeId != null) params['car_type_id'] = carTypeId.toString();
    if (carModelId != null) params['car_model_id'] = carModelId.toString();
    if (vendorId != null) params['vendor_id'] = vendorId.toString();
    if (branchId != null) params['branch_id'] = branchId.toString();

    return params;
  }
}

// lib/models/branch.dart
class Branch {
  final int id;
  final String name;
  final String slug;
  final String address;

  Branch({
    required this.id,
    required this.name,
    required this.slug,
    required this.address,
  });

  factory Branch.fromJson(Map<String, dynamic> json) {
    return Branch(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      address: json['address'],
    );
  }
}

// lib/models/car.dart
class Car {
  final int id;
  final int vendorId;
  final String carTitle;
  final String carModel;
  final String carNumber;
  final int seat;
  final int year;
  final String price;
  final String imageUrl;
  final CarType? carType;
  final CarModelInfo? carModelInfo;
  final Area? area;
  final Branch? branch;
  final Vendor? vendor;
  final DateTime? createdAt;

  Car({
    required this.id,
    required this.vendorId,
    required this.carTitle,
    required this.carModel,
    required this.carNumber,
    required this.seat,
    required this.year,
    required this.price,
    required this.imageUrl,
    this.carType,
    this.carModelInfo,
    this.area,
    this.branch,
    this.vendor,
    this.createdAt,
  });

  factory Car.fromJson(Map<String, dynamic> json) {
    return Car(
      id: json['id'],
      vendorId: json['vendor_id'],
      carTitle: json['car_title'],
      carModel: json['car_model'],
      carNumber: json['car_number'],
      seat: json['seat'],
      year: json['year'],
      price: json['price'],
      imageUrl: json['image_url'],
      carType: json['car_type'] != null
          ? CarType.fromJson(json['car_type'])
          : null,
      carModelInfo: json['car_model_info'] != null
          ? CarModelInfo.fromJson(json['car_model_info'])
          : null,
      area: json['area'] != null
          ? Area.fromJson(json['area'])
          : null,
      branch: json['branch'] != null
          ? Branch.fromJson(json['branch'])
          : null,
      vendor: json['vendor'] != null
          ? Vendor.fromJson(json['vendor'])
          : null,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
    );
  }
}

// lib/models/car_type.dart
class CarType {
  final int id;
  final String name;
  final String slug;

  CarType({
    required this.id,
    required this.name,
    required this.slug,
  });

  factory CarType.fromJson(Map<String, dynamic> json) {
    return CarType(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
    );
  }
}

// lib/models/car_response.dart
class CarResponse {
  final List<Car> cars;
  final Pagination pagination;
  final AvailableFilters availableFilters;
  final String? token;

  CarResponse({
    required this.cars,
    required this.pagination,
    required this.availableFilters,
    this.token,
  });

  factory CarResponse.fromJson(Map<String, dynamic> json) {
    return CarResponse(
      cars: (json['cars'] as List)
          .map((car) => Car.fromJson(car))
          .toList(),
      pagination: Pagination.fromJson(json['pagination']),
      availableFilters: AvailableFilters.fromJson(json['available_filters']),
      token: json['token'],
    );
  }
}

class AvailableFilters {
  final List<CarType> carTypes;
  final List<CarModelInfo> carModels;
  final List<Branch> branches;

  AvailableFilters({
    required this.carTypes,
    required this.carModels,
    required this.branches,
  });

  factory AvailableFilters.fromJson(Map<String, dynamic> json) {
    return AvailableFilters(
      carTypes: (json['car_types'] as List)
          .map((type) => CarType.fromJson(type))
          .toList(),
      carModels: (json['car_models'] as List)
          .map((model) => CarModelInfo.fromJson(model))
          .toList(),
      branches: (json['branches'] as List)
          .map((branch) => Branch.fromJson(branch))
          .toList(),
    );
  }
}

class Pagination {
  final int total;
  final int perPage;
  final int currentPage;
  final int lastPage;
  final int? from;
  final int? to;

  Pagination({
    required this.total,
    required this.perPage,
    required this.currentPage,
    required this.lastPage,
    this.from,
    this.to,
  });

  factory Pagination.fromJson(Map<String, dynamic> json) {
    return Pagination(
      total: json['total'],
      perPage: json['per_page'],
      currentPage: json['current_page'],
      lastPage: json['last_page'],
      from: json['from'],
      to: json['to'],
    );
  }

  bool get hasNextPage => currentPage < lastPage;
  bool get hasPreviousPage => currentPage > 1;
}
```

### 7.2 API Service

```dart
// lib/services/car_api_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class CarApiService {
  static const String baseUrl = 'http://192.168.1.211:8001/api/v1';

  /// Fetch cars with filters
  Future<CarResponse> getCars(CarFilter filter) async {
    try {
      final queryParams = filter.toQueryParams();
      final uri = Uri.parse('$baseUrl/cars').replace(queryParameters: queryParams);

      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return CarResponse.fromJson(jsonData['data']);
      } else if (response.statusCode == 422) {
        final jsonData = json.decode(response.body);
        throw ValidationException(jsonData['message']['error']);
      } else {
        throw ApiException('Failed to load cars: ${response.statusCode}');
      }
    } catch (e) {
      throw ApiException('Error fetching cars: $e');
    }
  }

  /// Fetch all car types
  Future<List<CarType>> getCarTypes() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/cars/types'));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return (jsonData['data']['car_types'] as List)
            .map((type) => CarType.fromJson(type))
            .toList();
      } else {
        throw ApiException('Failed to load car types');
      }
    } catch (e) {
      throw ApiException('Error fetching car types: $e');
    }
  }

  /// Fetch car models, optionally filtered by car type
  Future<List<CarModelInfo>> getCarModels({int? carTypeId}) async {
    try {
      var uri = Uri.parse('$baseUrl/cars/models');
      if (carTypeId != null) {
        uri = uri.replace(queryParameters: {'car_type_id': carTypeId.toString()});
      }

      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return (jsonData['data']['car_models'] as List)
            .map((model) => CarModelInfo.fromJson(model))
            .toList();
      } else {
        throw ApiException('Failed to load car models');
      }
    } catch (e) {
      throw ApiException('Error fetching car models: $e');
    }
  }

  /// Fetch all branches
  Future<List<Branch>> getBranches() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/cars/branches'));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return (jsonData['data']['branches'] as List)
            .map((branch) => Branch.fromJson(branch))
            .toList();
      } else {
        throw ApiException('Failed to load branches');
      }
    } catch (e) {
      throw ApiException('Error fetching branches: $e');
    }
  }

  /// Fetch single car details
  Future<Car> getCarById(int id, {String? token}) async {
    try {
      var uri = Uri.parse('$baseUrl/cars/$id');
      if (token != null) {
        uri = uri.replace(queryParameters: {'token': token});
      }

      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return Car.fromJson(jsonData['data']['car']);
      } else if (response.statusCode == 404) {
        throw NotFoundException('Car not found');
      } else {
        throw ApiException('Failed to load car details');
      }
    } catch (e) {
      throw ApiException('Error fetching car: $e');
    }
  }

  /// Fetch cars by vendor
  Future<CarResponse> getCarsByVendor(int vendorId, CarFilter filter) async {
    try {
      final queryParams = filter.toQueryParams();
      final uri = Uri.parse('$baseUrl/cars/vendor/$vendorId')
          .replace(queryParameters: queryParams);

      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return CarResponse.fromJson(jsonData['data']);
      } else {
        throw ApiException('Failed to load vendor cars');
      }
    } catch (e) {
      throw ApiException('Error fetching vendor cars: $e');
    }
  }
}

// Exceptions
class ApiException implements Exception {
  final String message;
  ApiException(this.message);

  @override
  String toString() => message;
}

class ValidationException implements Exception {
  final List<dynamic> errors;
  ValidationException(this.errors);

  @override
  String toString() => errors.join(', ');
}

class NotFoundException implements Exception {
  final String message;
  NotFoundException(this.message);

  @override
  String toString() => message;
}
```

### 7.3 State Management (Provider Example)

```dart
// lib/providers/car_provider.dart
import 'package:flutter/foundation.dart';

class CarProvider extends ChangeNotifier {
  final CarApiService _apiService = CarApiService();

  List<Car> _cars = [];
  List<CarType> _carTypes = [];
  List<CarModelInfo> _carModels = [];
  List<Branch> _branches = [];
  Pagination? _pagination;
  bool _isLoading = false;
  String? _error;

  CarFilter _currentFilter = CarFilter();

  // Getters
  List<Car> get cars => _cars;
  List<CarType> get carTypes => _carTypes;
  List<CarModelInfo> get carModels => _carModels;
  List<Branch> get branches => _branches;
  Pagination? get pagination => _pagination;
  bool get isLoading => _isLoading;
  String? get error => _error;
  CarFilter get currentFilter => _currentFilter;

  /// Load initial filter data (types, models, branches)
  Future<void> loadFilterData() async {
    try {
      _isLoading = true;
      _error = null;
      notifyListeners();

      final results = await Future.wait([
        _apiService.getCarTypes(),
        _apiService.getCarModels(),
        _apiService.getBranches(),
      ]);

      _carTypes = results[0] as List<CarType>;
      _carModels = results[1] as List<CarModelInfo>;
      _branches = results[2] as List<Branch>;

      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Fetch cars with current filter
  Future<void> fetchCars({bool loadMore = false}) async {
    try {
      if (!loadMore) {
        _isLoading = true;
        _currentFilter = _currentFilter.copyWith(page: 1);
      }

      _error = null;
      notifyListeners();

      final response = await _apiService.getCars(_currentFilter);

      if (loadMore) {
        _cars.addAll(response.cars);
      } else {
        _cars = response.cars;
      }

      _pagination = response.pagination;
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Load next page
  Future<void> loadNextPage() async {
    if (_pagination?.hasNextPage == true && !_isLoading) {
      _currentFilter = _currentFilter.copyWith(
        page: _currentFilter.page + 1,
      );
      await fetchCars(loadMore: true);
    }
  }

  /// Update filter and refresh
  Future<void> updateFilter({
    int? carTypeId,
    int? carModelId,
    int? branchId,
    String? sort,
    bool? clearCarType,
    bool? clearCarModel,
    bool? clearBranch,
  }) async {
    _currentFilter = CarFilter(
      carTypeId: clearCarType == true ? null : (carTypeId ?? _currentFilter.carTypeId),
      carModelId: clearCarModel == true ? null : (carModelId ?? _currentFilter.carModelId),
      branchId: clearBranch == true ? null : (branchId ?? _currentFilter.branchId),
      sort: sort ?? _currentFilter.sort,
      perPage: _currentFilter.perPage,
      page: 1, // Reset to first page when filter changes
    );

    await fetchCars();
  }

  /// Clear all filters
  Future<void> clearFilters() async {
    _currentFilter = CarFilter();
    await fetchCars();
  }
}

// Extension for CarFilter copyWith
extension CarFilterCopyWith on CarFilter {
  CarFilter copyWith({
    int? carTypeId,
    int? carModelId,
    int? vendorId,
    int? branchId,
    String? sort,
    int? perPage,
    int? page,
  }) {
    return CarFilter(
      carTypeId: carTypeId ?? this.carTypeId,
      carModelId: carModelId ?? this.carModelId,
      vendorId: vendorId ?? this.vendorId,
      branchId: branchId ?? this.branchId,
      sort: sort ?? this.sort,
      perPage: perPage ?? this.perPage,
      page: page ?? this.page,
    );
  }
}
```

### 7.4 UI Example (Cars List Screen)

```dart
// lib/screens/cars_list_screen.dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class CarsListScreen extends StatefulWidget {
  @override
  _CarsListScreenState createState() => _CarsListScreenState();
}

class _CarsListScreenState extends State<CarsListScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();

    // Load initial data
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<CarProvider>();
      provider.loadFilterData();
      provider.fetchCars();
    });

    // Setup infinite scroll
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
          _scrollController.position.maxScrollExtent - 200) {
        context.read<CarProvider>().loadNextPage();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Available Cars'),
        actions: [
          IconButton(
            icon: Icon(Icons.filter_list),
            onPressed: () => _showFilterSheet(context),
          ),
        ],
      ),
      body: Consumer<CarProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.cars.isEmpty) {
            return Center(child: CircularProgressIndicator());
          }

          if (provider.error != null && provider.cars.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('Error: ${provider.error}'),
                  SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => provider.fetchCars(),
                    child: Text('Retry'),
                  ),
                ],
              ),
            );
          }

          if (provider.cars.isEmpty) {
            return Center(child: Text('No cars available'));
          }

          return Column(
            children: [
              // Active filters display
              if (provider.currentFilter.branchId != null ||
                  provider.currentFilter.carTypeId != null ||
                  provider.currentFilter.carModelId != null)
                _buildActiveFilters(context, provider),

              // Cars list
              Expanded(
                child: ListView.builder(
                  controller: _scrollController,
                  itemCount: provider.cars.length + 1,
                  itemBuilder: (context, index) {
                    if (index == provider.cars.length) {
                      // Loading indicator at the end
                      return provider.pagination?.hasNextPage == true
                          ? Padding(
                              padding: EdgeInsets.all(16),
                              child: Center(child: CircularProgressIndicator()),
                            )
                          : SizedBox.shrink();
                    }

                    return CarListItem(car: provider.cars[index]);
                  },
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildActiveFilters(BuildContext context, CarProvider provider) {
    return Container(
      padding: EdgeInsets.all(8),
      color: Colors.grey[200],
      child: Wrap(
        spacing: 8,
        children: [
          if (provider.currentFilter.branchId != null)
            Chip(
              label: Text('Branch Filter'),
              onDeleted: () => provider.updateFilter(clearBranch: true),
            ),
          if (provider.currentFilter.carTypeId != null)
            Chip(
              label: Text('Type Filter'),
              onDeleted: () => provider.updateFilter(clearCarType: true),
            ),
          if (provider.currentFilter.carModelId != null)
            Chip(
              label: Text('Model Filter'),
              onDeleted: () => provider.updateFilter(clearCarModel: true),
            ),
          TextButton(
            onPressed: () => provider.clearFilters(),
            child: Text('Clear All'),
          ),
        ],
      ),
    );
  }

  void _showFilterSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => FilterBottomSheet(),
    );
  }
}

// Car List Item Widget
class CarListItem extends StatelessWidget {
  final Car car;

  const CarListItem({required this.car});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: ListTile(
        leading: ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Image.network(
            car.imageUrl,
            width: 80,
            height: 80,
            fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => Icon(Icons.car_rental, size: 80),
          ),
        ),
        title: Text(car.carTitle),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('${car.carModel} • ${car.year}'),
            Text('${car.seat} seats'),
            if (car.branch != null)
              Text('Branch: ${car.branch!.name}',
                   style: TextStyle(fontSize: 12, color: Colors.grey)),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              '\$${car.price}',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).primaryColor,
              ),
            ),
            Text('per day', style: TextStyle(fontSize: 10)),
          ],
        ),
        onTap: () {
          // Navigate to car details
          Navigator.pushNamed(context, '/car-details', arguments: car.id);
        },
      ),
    );
  }
}

// Filter Bottom Sheet
class FilterBottomSheet extends StatefulWidget {
  @override
  _FilterBottomSheetState createState() => _FilterBottomSheetState();
}

class _FilterBottomSheetState extends State<FilterBottomSheet> {
  int? _selectedBranchId;
  int? _selectedCarTypeId;
  int? _selectedCarModelId;
  String? _selectedSort;

  @override
  void initState() {
    super.initState();
    final provider = context.read<CarProvider>();
    _selectedBranchId = provider.currentFilter.branchId;
    _selectedCarTypeId = provider.currentFilter.carTypeId;
    _selectedCarModelId = provider.currentFilter.carModelId;
    _selectedSort = provider.currentFilter.sort;
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<CarProvider>(
      builder: (context, provider, child) {
        return Container(
          padding: EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Filter Cars',
                   style: Theme.of(context).textTheme.headlineSmall),
              SizedBox(height: 16),

              // Branch Filter
              Text('Branch'),
              DropdownButton<int>(
                isExpanded: true,
                value: _selectedBranchId,
                hint: Text('Select Branch'),
                items: [
                  DropdownMenuItem(value: null, child: Text('All Branches')),
                  ...provider.branches.map((branch) => DropdownMenuItem(
                    value: branch.id,
                    child: Text(branch.name),
                  )),
                ],
                onChanged: (value) {
                  setState(() => _selectedBranchId = value);
                },
              ),
              SizedBox(height: 16),

              // Car Type Filter
              Text('Car Type'),
              DropdownButton<int>(
                isExpanded: true,
                value: _selectedCarTypeId,
                hint: Text('Select Car Type'),
                items: [
                  DropdownMenuItem(value: null, child: Text('All Types')),
                  ...provider.carTypes.map((type) => DropdownMenuItem(
                    value: type.id,
                    child: Text(type.name),
                  )),
                ],
                onChanged: (value) {
                  setState(() {
                    _selectedCarTypeId = value;
                    _selectedCarModelId = null; // Reset model when type changes
                  });
                },
              ),
              SizedBox(height: 16),

              // Sort
              Text('Sort By'),
              DropdownButton<String>(
                isExpanded: true,
                value: _selectedSort,
                items: [
                  DropdownMenuItem(value: 'price_desc', child: Text('Price: High to Low')),
                  DropdownMenuItem(value: 'price_asc', child: Text('Price: Low to High')),
                ],
                onChanged: (value) {
                  setState(() => _selectedSort = value);
                },
              ),
              SizedBox(height: 24),

              // Apply Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    provider.updateFilter(
                      branchId: _selectedBranchId,
                      carTypeId: _selectedCarTypeId,
                      carModelId: _selectedCarModelId,
                      sort: _selectedSort,
                    );
                    Navigator.pop(context);
                  },
                  child: Text('Apply Filters'),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
```

---

## Testing the API

### Using cURL

```bash
# Get all cars with branch filter
curl "http://192.168.1.211:8001/api/v1/cars?branch_id=1&sort=price_desc&per_page=15&page=1"

# Get all branches
curl "http://192.168.1.211:8001/api/v1/cars/branches"

# Get car types
curl "http://192.168.1.211:8001/api/v1/cars/types"

# Get car models
curl "http://192.168.1.211:8001/api/v1/cars/models"

# Get single car
curl "http://192.168.1.211:8001/api/v1/cars/15"

# Get cars with multiple filters
curl "http://192.168.1.211:8001/api/v1/cars?car_type_id=1&branch_id=2&sort=price_asc"
```

---

## Notes

1. **Pagination**: The API uses cursor-based pagination. Monitor the `pagination.has_next_page` field to determine if more data is available.

2. **Image URLs**: All image URLs are absolute and ready to use directly in `Image.network()` widgets.

3. **Null Safety**: Many fields can be `null` (branch, vendor, area). Always check for null before accessing nested properties.

4. **Branch Filter**: The new `branch_id` filter allows users to search for cars available at specific branch locations.

5. **Error Handling**: Always implement proper error handling for network requests and validation errors (422 status code).

6. **Token Management**: If implementing a booking flow, preserve the `token` value across API calls.

7. **Performance**: Use infinite scroll/pagination for better performance rather than loading all cars at once.

---

## Support

For API issues or questions, contact the backend team or refer to the main API documentation.

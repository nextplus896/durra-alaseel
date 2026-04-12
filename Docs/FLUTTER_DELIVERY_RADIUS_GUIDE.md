# Flutter Delivery Radius System — Integration Guide

Complete implementation guide for integrating the Branch Delivery Radius System into the Flutter mobile app.

---

## API Contract

### Check Delivery Area

| Field        | Value                                 |
| ------------ | ------------------------------------- |
| Method       | `POST`                                |
| URL          | `/api/v1/user/check-delivery-area`    |
| Auth         | `Bearer {token}` (Sanctum `auth:api`) |
| Content-Type | `application/json`                    |

**Request Body:**

```json
{
    "branch_id": 3,
    "lat": 24.7136,
    "lng": 46.6753
}
```

**Success Response (`200`):**

```json
{
    "status": true,
    "data": {
        "allowed": true,
        "distance_km": 4.2,
        "max_radius": 10.0
    }
}
```

**Outside Radius (`200`):**

```json
{
    "status": true,
    "data": {
        "allowed": false,
        "distance_km": 14.7,
        "max_radius": 10.0
    }
}
```

**Delivery Disabled Response (`200`):**

```json
{
    "status": true,
    "data": {
        "allowed": false,
        "distance_km": null,
        "max_radius": null
    }
}
```

**Validation Error (`422`):**

```json
{
    "status": false,
    "message": "The given data was invalid.",
    "errors": ["The branch id field is required."]
}
```

---

### Car List — Delivery Fields

Each car object in the car list API now includes flat delivery fields:

```json
{
  "id": 12,
  "name": "Toyota Camry",
  "branch_name": "Riyadh Central Branch",
  "branch_latitude": 24.7136,
  "branch_longitude": 46.6753,
  "delivery_enabled": true,
  "delivery_radius_km": 10.0,
  ...
}
```

> **Note:** `delivery_enabled: false` means pickup-only. `delivery_radius_km` may be `null` even when `delivery_enabled` is `true` if admin hasn't configured a radius yet — treat as disabled.

---

## Data Models

### `lib/models/branch_info.dart`

```dart
class BranchInfo {
  final int id;
  final String name;
  final double latitude;
  final double longitude;
  final bool deliveryEnabled;
  final double? deliveryRadiusKm;

  const BranchInfo({
    required this.id,
    required this.name,
    required this.latitude,
    required this.longitude,
    required this.deliveryEnabled,
    this.deliveryRadiusKm,
  });

  factory BranchInfo.fromCarJson(Map<String, dynamic> json) {
    return BranchInfo(
      id: json['branch_id'] as int,
      name: json['branch_name'] as String? ?? '',
      latitude: (json['branch_latitude'] as num?)?.toDouble() ?? 0.0,
      longitude: (json['branch_longitude'] as num?)?.toDouble() ?? 0.0,
      deliveryEnabled: json['delivery_enabled'] as bool? ?? false,
      deliveryRadiusKm: (json['delivery_radius_km'] as num?)?.toDouble(),
    );
  }

  /// True only when delivery is enabled AND a radius has been configured.
  bool get isDeliveryAvailable =>
      deliveryEnabled && deliveryRadiusKm != null && deliveryRadiusKm! > 0;
}
```

### `lib/models/delivery_check_result.dart`

```dart
class DeliveryCheckResult {
  final bool allowed;
  final double? distanceKm;
  final double? maxRadius;

  const DeliveryCheckResult({
    required this.allowed,
    this.distanceKm,
    this.maxRadius,
  });

  factory DeliveryCheckResult.fromJson(Map<String, dynamic> json) {
    return DeliveryCheckResult(
      allowed: json['allowed'] as bool,
      distanceKm: (json['distance_km'] as num?)?.toDouble(),
      maxRadius: (json['max_radius'] as num?)?.toDouble(),
    );
  }

  String get statusMessage {
    if (allowed) return 'Delivery available to this location';
    if (distanceKm == null) return 'Delivery is not available for this branch';
    return 'Location is ${distanceKm!.toStringAsFixed(1)} km away '
        '(max ${maxRadius!.toStringAsFixed(1)} km)';
  }
}
```

---

## API Service

### `lib/services/delivery_api_service.dart`

```dart
import 'package:dio/dio.dart';
import '../models/delivery_check_result.dart';

class DeliveryApiService {
  final Dio _dio;

  DeliveryApiService(this._dio);

  /// Returns null on network/server error — callers must handle gracefully.
  Future<DeliveryCheckResult?> checkDeliveryArea({
    required int branchId,
    required double lat,
    required double lng,
  }) async {
    try {
      final response = await _dio.post(
        '/api/v1/user/check-delivery-area',
        data: {
          'branch_id': branchId,
          'lat': lat,
          'lng': lng,
        },
      );

      if (response.data['status'] == true) {
        return DeliveryCheckResult.fromJson(
          response.data['data'] as Map<String, dynamic>,
        );
      }
      return null;
    } on DioException {
      return null;
    }
  }
}
```

---

## GetX Controller

### `lib/controllers/booking_controller.dart`

```dart
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import '../models/branch_info.dart';
import '../models/delivery_check_result.dart';
import '../services/delivery_api_service.dart';

enum BookingOption { pickup, delivery }

class BookingController extends GetxController {
  final DeliveryApiService _deliveryApiService;

  BookingController(this._deliveryApiService);

  // ─── Observables ──────────────────────────────────────────────────────────

  final selectedOption = BookingOption.pickup.obs;
  final branch = Rxn<BranchInfo>();
  final deliveryCheckResult = Rxn<DeliveryCheckResult>();
  final selectedDeliveryLocation = Rxn<LatLng>();
  final isCheckingDelivery = false.obs;

  // ─── Computed ─────────────────────────────────────────────────────────────

  bool get canConfirmBooking {
    if (selectedOption.value == BookingOption.pickup) return true;
    return deliveryCheckResult.value?.allowed == true;
  }

  Set<Marker> get mapMarkers {
    final markers = <Marker>{};

    if (branch.value != null) {
      markers.add(Marker(
        markerId: const MarkerId('branch'),
        position: LatLng(branch.value!.latitude, branch.value!.longitude),
        infoWindow: InfoWindow(title: branch.value!.name),
      ));
    }

    if (selectedDeliveryLocation.value != null) {
      markers.add(Marker(
        markerId: const MarkerId('delivery'),
        position: selectedDeliveryLocation.value!,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueOrange),
        infoWindow: const InfoWindow(title: 'Delivery Location'),
      ));
    }

    return markers;
  }

  Set<Circle> get mapCircles {
    final b = branch.value;
    if (b == null || !b.isDeliveryAvailable) return {};

    return {
      Circle(
        circleId: const CircleId('delivery_radius'),
        center: LatLng(b.latitude, b.longitude),
        radius: b.deliveryRadiusKm! * 1000, // convert km → metres
        strokeColor: const Color(0xFFFF8C00),
        strokeWidth: 2,
        fillColor: const Color(0x26FF8C00), // 15% opacity
      ),
    };
  }

  // ─── Actions ──────────────────────────────────────────────────────────────

  void loadBranch(BranchInfo branchInfo) {
    branch.value = branchInfo;
    // Reset delivery state when switching cars/branches
    deliveryCheckResult.value = null;
    selectedDeliveryLocation.value = null;
    selectedOption.value = BookingOption.pickup;
  }

  void selectOption(BookingOption option) {
    if (!branch.value!.isDeliveryAvailable && option == BookingOption.delivery) {
      Get.snackbar('Not Available', 'Delivery is not available for this branch.');
      return;
    }
    selectedOption.value = option;
    if (option == BookingOption.pickup) {
      deliveryCheckResult.value = null;
      selectedDeliveryLocation.value = null;
    }
  }

  Future<void> onDeliveryLocationSelected(LatLng location) async {
    selectedDeliveryLocation.value = location;
    await _validateDeliveryLocation(location);
  }

  Future<void> _validateDeliveryLocation(LatLng location) async {
    final b = branch.value;
    if (b == null) return;

    isCheckingDelivery.value = true;
    deliveryCheckResult.value = null;

    final result = await _deliveryApiService.checkDeliveryArea(
      branchId: b.id,
      lat: location.latitude,
      lng: location.longitude,
    );

    deliveryCheckResult.value = result;
    isCheckingDelivery.value = false;

    if (result == null) {
      Get.snackbar('Error', 'Could not validate delivery location. Please try again.');
    }
  }
}
```

---

## DI Binding

### `lib/bindings/booking_binding.dart`

```dart
import 'package:get/get.dart';
import '../controllers/booking_controller.dart';
import '../services/delivery_api_service.dart';

class BookingBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<DeliveryApiService>(
      () => DeliveryApiService(Get.find()),
    );
    Get.lazyPut<BookingController>(
      () => BookingController(Get.find<DeliveryApiService>()),
    );
  }
}
```

---

## Screens

### `lib/screens/map_location_picker_screen.dart`

Full-screen Google Maps picker. User taps to select delivery location; circle shows the allowed radius.

```dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import '../controllers/booking_controller.dart';
import '../models/branch_info.dart';

class MapLocationPickerScreen extends StatefulWidget {
  const MapLocationPickerScreen({super.key});

  @override
  State<MapLocationPickerScreen> createState() => _MapLocationPickerScreenState();
}

class _MapLocationPickerScreenState extends State<MapLocationPickerScreen> {
  final controller = Get.find<BookingController>();
  LatLng? _pendingLocation;

  @override
  Widget build(BuildContext context) {
    final BranchInfo branch = controller.branch.value!;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Select Delivery Location'),
        actions: [
          Obx(() => TextButton(
            onPressed: controller.isCheckingDelivery.value
                ? null
                : () => _confirmAndPop(),
            child: const Text('Confirm', style: TextStyle(color: Colors.white)),
          )),
        ],
      ),
      body: Stack(
        children: [
          Obx(() => GoogleMap(
            initialCameraPosition: CameraPosition(
              target: LatLng(branch.latitude, branch.longitude),
              zoom: 12,
            ),
            markers: controller.mapMarkers,
            circles: controller.mapCircles,
            onTap: _onMapTap,
            myLocationButtonEnabled: true,
            myLocationEnabled: true,
          )),
          // Status banner
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: _DeliveryStatusBanner(),
          ),
        ],
      ),
    );
  }

  void _onMapTap(LatLng location) {
    setState(() => _pendingLocation = location);
    controller.onDeliveryLocationSelected(location);
  }

  void _confirmAndPop() {
    final result = controller.deliveryCheckResult.value;
    if (result?.allowed == true) {
      Get.back();
    } else {
      Get.snackbar(
        'Invalid Location',
        result?.statusMessage ?? 'Please select a valid delivery location.',
        backgroundColor: Colors.red,
        colorText: Colors.white,
      );
    }
  }
}

class _DeliveryStatusBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final controller = Get.find<BookingController>();

    return Obx(() {
      if (controller.isCheckingDelivery.value) {
        return const _Banner(
          color: Colors.grey,
          icon: Icons.hourglass_top,
          message: 'Checking delivery availability...',
        );
      }

      final result = controller.deliveryCheckResult.value;
      if (result == null) {
        return const _Banner(
          color: Colors.blue,
          icon: Icons.touch_app,
          message: 'Tap the map to select your delivery location',
        );
      }

      return _Banner(
        color: result.allowed ? Colors.green : Colors.red,
        icon: result.allowed ? Icons.check_circle : Icons.cancel,
        message: result.statusMessage,
      );
    });
  }
}

class _Banner extends StatelessWidget {
  final Color color;
  final IconData icon;
  final String message;

  const _Banner({
    required this.color,
    required this.icon,
    required this.message,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      color: color.withOpacity(0.9),
      child: Row(
        children: [
          Icon(icon, color: Colors.white, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(color: Colors.white, fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }
}
```

---

### `lib/screens/booking_screen.dart`

Booking screen with pickup/delivery toggle, mini map preview, and validation status.

```dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import '../controllers/booking_controller.dart';
import 'map_location_picker_screen.dart';

class BookingScreen extends StatelessWidget {
  const BookingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = Get.find<BookingController>();

    return Scaffold(
      appBar: AppBar(title: const Text('Book Car')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Pickup / Delivery Toggle ────────────────────────────────
            Obx(() {
              final branch = controller.branch.value;
              return SegmentedButton<BookingOption>(
                segments: [
                  const ButtonSegment(
                    value: BookingOption.pickup,
                    label: Text('Pickup'),
                    icon: Icon(Icons.store),
                  ),
                  ButtonSegment(
                    value: BookingOption.delivery,
                    label: const Text('Delivery'),
                    icon: const Icon(Icons.delivery_dining),
                    enabled: branch?.isDeliveryAvailable ?? false,
                  ),
                ],
                selected: {controller.selectedOption.value},
                onSelectionChanged: (Set<BookingOption> newSelection) {
                  controller.selectOption(newSelection.first);
                },
              );
            }),

            const SizedBox(height: 16),

            // ── Delivery Location Picker ────────────────────────────────
            Obx(() {
              if (controller.selectedOption.value != BookingOption.delivery) {
                return const SizedBox.shrink();
              }

              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Select Delivery Location',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 8),

                  // Mini map preview
                  if (controller.selectedDeliveryLocation.value != null)
                    SizedBox(
                      height: 180,
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: GoogleMap(
                          initialCameraPosition: CameraPosition(
                            target: controller.selectedDeliveryLocation.value!,
                            zoom: 13,
                          ),
                          markers: controller.mapMarkers,
                          circles: controller.mapCircles,
                          zoomControlsEnabled: false,
                          scrollGesturesEnabled: false,
                          tiltGesturesEnabled: false,
                          rotateGesturesEnabled: false,
                        ),
                      ),
                    ),

                  const SizedBox(height: 8),

                  // Validation status row
                  Obx(() {
                    final result = controller.deliveryCheckResult.value;
                    if (controller.isCheckingDelivery.value) {
                      return const Row(
                        children: [
                          SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                          SizedBox(width: 8),
                          Text('Checking delivery area...'),
                        ],
                      );
                    }
                    if (result != null) {
                      return Row(
                        children: [
                          Icon(
                            result.allowed ? Icons.check_circle : Icons.cancel,
                            color: result.allowed ? Colors.green : Colors.red,
                            size: 18,
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              result.statusMessage,
                              style: TextStyle(
                                color: result.allowed ? Colors.green : Colors.red,
                                fontSize: 13,
                              ),
                            ),
                          ),
                        ],
                      );
                    }
                    return const SizedBox.shrink();
                  }),

                  const SizedBox(height: 8),

                  OutlinedButton.icon(
                    onPressed: () => Get.to(() => const MapLocationPickerScreen()),
                    icon: const Icon(Icons.map),
                    label: Text(
                      controller.selectedDeliveryLocation.value == null
                          ? 'Choose on Map'
                          : 'Change Location',
                    ),
                  ),
                ],
              );
            }),

            const SizedBox(height: 32),

            // ── Confirm Button ──────────────────────────────────────────
            Obx(() => SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: controller.canConfirmBooking
                    ? () => _confirmBooking(controller)
                    : null,
                child: const Text('Confirm Booking'),
              ),
            )),
          ],
        ),
      ),
    );
  }

  void _confirmBooking(BookingController controller) {
    // Pass is_delivery, lat, lng to the booking confirm API
    final isDelivery = controller.selectedOption.value == BookingOption.delivery;
    final location = controller.selectedDeliveryLocation.value;

    // TODO: call your existing booking confirm API with:
    // {
    //   "is_delivery": isDelivery,
    //   "pickup_latitude": isDelivery ? location?.latitude : null,
    //   "pickup_longitude": isDelivery ? location?.longitude : null,
    // }
  }
}
```

---

## Integration Checklist

### Car List Screen

- [ ] Parse `branch_id`, `branch_name`, `branch_latitude`, `branch_longitude`, `delivery_enabled`, `delivery_radius_km` from each car JSON
- [ ] Call `controller.loadBranch(BranchInfo.fromCarJson(carJson))` when user opens a car detail/booking screen

### Booking Screen

- [ ] Show pickup/delivery toggle only when `branch.isDeliveryAvailable == true`
- [ ] Disable delivery option and show tooltip if `delivery_enabled == false`
- [ ] Gate the confirm button on `controller.canConfirmBooking`

### Map Location Picker

- [ ] Draw orange circle on the map to visually show the delivery radius
- [ ] Update status banner on every tap (API call to `check-delivery-area`)
- [ ] Only allow confirming when `result.allowed == true`

### Booking Confirm API Call

- [ ] Include `is_delivery: true` and `pickup_latitude`/`pickup_longitude` when delivery is selected
- [ ] The backend **re-validates** the radius server-side — client circle is visual only

---

## Key Architecture Decisions

| Decision                                                  | Rationale                                                         |
| --------------------------------------------------------- | ----------------------------------------------------------------- |
| Backend re-validates on every booking confirm             | Client can be tampered with; server is the single source of truth |
| `check-delivery-area` called on every map tap             | Immediate feedback; no stale state                                |
| `delivery_radius_km` exposed as flat field in CarResource | Avoids nested branch object parsing; prevents missing-key crashes |
| Circle drawn client-side from `delivery_radius_km`        | Visual guide only — never used for access control                 |
| Orange circle for delivery radius                         | Distinct from service radius circle (blue) shown in admin         |

# Flutter Mobile App - Push Notification Integration Guide
## Booking Rejection Push Notifications

---

## Overview

This guide explains how to integrate push notifications in the Flutter mobile app to receive booking rejection notifications from the backend using Pusher Beams.

---

## Backend Implementation Summary

### What Was Implemented:
✅ Rejection dialog with 7 predefined reasons + custom option  
✅ Rejection reason stored in database (`car_bookings.rejection_reason`)  
✅ Push notifications sent via Pusher Beams  
✅ User notifications stored in database  

### Push Notification Configuration:
- **Provider**: Pusher Beams
- **Instance ID**: `f80f6f50-1302-4c1f-b7ba-b64a57fcbd29`
- **Publishable ID Format**: `user-{user_id}`
  - Example: `user-4`, `user-123`
  - **IMPORTANT**: The format is simply `user-{user_id}` (no domain prefix)

### Notification Payload:
```json
{
  "title": "Booking Rejected",
  "body": "Your booking #{trx_id} was rejected. Reason: {rejection_reason}",
  "icon": "{fav_icon_url}"
}
```

### Rejection Reasons:
1. Vehicle Unavailable
2. Driver Documents Invalid
3. Payment Issue
4. Service Area Restriction
5. Rental Duration Issue
6. Policy Violation
7. Other (custom reason from vendor)

---

## Flutter Implementation Steps

### Step 1: Add Dependencies

Add to `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # Push Notifications
  pusher_beams: ^2.0.0
  firebase_messaging: ^14.7.0  # For FCM (required by Pusher Beams)
  flutter_local_notifications: ^16.3.0
  
  # Permissions
  permission_handler: ^11.0.1
```

Run:
```bash
flutter pub get
```

---

### Step 2: Configure Pusher Beams Instance

Create `lib/services/push_notification_service.dart`:

```dart
import 'package:pusher_beams/pusher_beams.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class PushNotificationService {
  static final PushNotificationService _instance = PushNotificationService._internal();
  factory PushNotificationService() => _instance;
  PushNotificationService._internal();

  // Pusher Beams Configuration
  static const String INSTANCE_ID = 'f80f6f50-1302-4c1f-b7ba-b64a57fcbd29';
  
  final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin =
      FlutterLocalNotificationsPlugin();

  // Initialize Push Notifications
  Future<void> initialize() async {
    // Initialize local notifications
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    
    const DarwinInitializationSettings initializationSettingsIOS =
        DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );

    const InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
      iOS: initializationSettingsIOS,
    );

    await flutterLocalNotificationsPlugin.initialize(
      initializationSettings,
      onDidReceiveNotificationResponse: _onNotificationTapped,
    );

    // Initialize Pusher Beams
    await PusherBeams.instance.start(INSTANCE_ID);
    
    // Set up notification handler
    await PusherBeams.instance.onMessageReceivedInTheForeground(_onMessageReceived);
  }

  // Subscribe user to notifications
  Future<void> subscribeUser(int userId) async {
    try {
      // Create publishable ID: user-{userId}
      // IMPORTANT: Backend uses format "user-{userId}" (no domain prefix)
      String publishableId = 'user-$userId';
      
      // Subscribe to user-specific notifications
      await PusherBeams.instance.setUserId(
        publishableId,
        BeamsAuthProvider()..authUrl = 'YOUR_API_URL/pusher/beams-auth',
      );
      
      print('✅ Subscribed to push notifications: $publishableId');
    } catch (e) {
      print('❌ Failed to subscribe to push notifications: $e');
    }
  }

  // Unsubscribe user (on logout)
  Future<void> unsubscribeUser() async {
    try {
      await PusherBeams.instance.clearAllState();
      print('✅ Unsubscribed from push notifications');
    } catch (e) {
      print('❌ Failed to unsubscribe: $e');
    }
  }

  // Handle message received in foreground
  void _onMessageReceived(Map<Object?, Object?> data) {
    print('📩 Notification received: $data');
    
    // Extract notification data
    final notification = data['notification'] as Map?;
    if (notification != null) {
      final title = notification['title'] as String?;
      final body = notification['body'] as String?;
      
      // Show local notification
      _showLocalNotification(title ?? 'Notification', body ?? '');
      
      // Handle booking rejection notification
      if (title?.contains('Rejected') ?? false) {
        _handleBookingRejection(data);
      }
    }
  }

  // Show local notification
  Future<void> _showLocalNotification(String title, String body) async {
    const AndroidNotificationDetails androidPlatformChannelSpecifics =
        AndroidNotificationDetails(
      'booking_channel',
      'Booking Notifications',
      channelDescription: 'Notifications for booking updates',
      importance: Importance.high,
      priority: Priority.high,
      showWhen: true,
    );

    const DarwinNotificationDetails iOSPlatformChannelSpecifics =
        DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const NotificationDetails platformChannelSpecifics = NotificationDetails(
      android: androidPlatformChannelSpecifics,
      iOS: iOSPlatformChannelSpecifics,
    );

    await flutterLocalNotificationsPlugin.show(
      DateTime.now().millisecond,
      title,
      body,
      platformChannelSpecifics,
    );
  }

  // Handle booking rejection
  void _handleBookingRejection(Map<Object?, Object?> data) {
    // Parse booking rejection data
    // Navigate to booking details or show dialog
    // You can emit an event or use a state management solution
    print('🚫 Booking rejected notification received');
    
    // Example: Extract booking ID from message
    final notification = data['notification'] as Map?;
    final message = notification?['body'] as String?;
    
    if (message != null) {
      // Extract booking ID and reason from message
      // Message format: "Your booking #{trx_id} was rejected. Reason: {reason}"
      final bookingIdMatch = RegExp(r'#(\w+)').firstMatch(message);
      final reasonMatch = RegExp(r'Reason: (.+)$').firstMatch(message);
      
      final bookingId = bookingIdMatch?.group(1);
      final reason = reasonMatch?.group(1);
      
      print('Booking ID: $bookingId');
      print('Rejection Reason: $reason');
      
      // TODO: Navigate to booking details or update UI
      // TODO: Refresh booking list
      // TODO: Show dialog with rejection reason
    }
  }

  // Handle notification tap
  void _onNotificationTapped(NotificationResponse response) {
    print('📲 Notification tapped: ${response.payload}');
    
    // Navigate to appropriate screen
    // TODO: Implement navigation based on notification type
  }

  // Request notification permissions (iOS)
  Future<bool> requestPermissions() async {
    if (Platform.isIOS) {
      final bool? result = await flutterLocalNotificationsPlugin
          .resolvePlatformSpecificImplementation<
              IOSFlutterLocalNotificationsPlugin>()
          ?.requestPermissions(
            alert: true,
            badge: true,
            sound: true,
          );
      return result ?? false;
    }
    return true; // Android doesn't need runtime permission request
  }
}
```

---

### Step 3: Initialize on App Startup

Update `lib/main.dart`:

```dart
import 'package:flutter/material.dart';
import 'services/push_notification_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize push notifications
  final pushService = PushNotificationService();
  await pushService.initialize();
  await pushService.requestPermissions();
  
  runApp(MyApp());
}
```

---

### Step 4: Subscribe User After Login

Update your login/authentication flow:

```dart
class AuthService {
  Future<void> login(String email, String password) async {
    // Your existing login logic
    final response = await api.login(email, password);
    
    if (response.success) {
      final userId = response.data['user']['id'];
      
      // Subscribe to push notifications
      await PushNotificationService().subscribeUser(userId);
      
      // Save user data
      await saveUserData(response.data);
    }
  }

  Future<void> logout() async {
    // Unsubscribe from push notifications
    await PushNotificationService().unsubscribeUser();
    
    // Your existing logout logic
    await clearUserData();
  }
}
```

---

### Step 5: Handle Booking Rejection UI

Create a widget to show rejection reason:

```dart
class BookingRejectionDialog extends StatelessWidget {
  final String bookingId;
  final String reason;

  const BookingRejectionDialog({
    Key? key,
    required this.bookingId,
    required this.reason,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Row(
        children: [
          Icon(Icons.cancel, color: Colors.red),
          SizedBox(width: 8),
          Text('Booking Rejected'),
        ],
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Booking #$bookingId',
            style: TextStyle(fontWeight: FontWeight.bold),
          ),
          SizedBox(height: 16),
          Text('Rejection Reason:', style: TextStyle(color: Colors.grey)),
          SizedBox(height: 8),
          Container(
            padding: EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.red.shade50,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.red.shade200),
            ),
            child: Text(
              reason,
              style: TextStyle(fontSize: 16),
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text('OK'),
        ),
        ElevatedButton(
          onPressed: () {
            Navigator.pop(context);
            // Navigate to booking details
            // Navigator.push(...);
          },
          child: Text('View Details'),
        ),
      ],
    );
  }
}
```

---

### Step 6: Update Booking List to Show Rejection Reason

Add rejection reason to booking model:

```dart
class Booking {
  final String id;
  final String trxId;
  final int status;
  final String? rejectionReason; // NEW FIELD
  // ... other fields

  factory Booking.fromJson(Map<String, dynamic> json) {
    return Booking(
      id: json['id'].toString(),
      trxId: json['trx_id'],
      status: json['status'],
      rejectionReason: json['rejection_reason'], // PARSE NEW FIELD
      // ... other fields
    );
  }
}
```

Update booking card UI:

```dart
class BookingCard extends StatelessWidget {
  final Booking booking;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Column(
        children: [
          // ... existing booking info
          
          // Show rejection reason if status is rejected (4)
          if (booking.status == 4 && booking.rejectionReason != null)
            Container(
              padding: EdgeInsets.all(12),
              margin: EdgeInsets.only(top: 8),
              decoration: BoxDecoration(
                color: Colors.red.shade50,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Icon(Icons.info_outline, color: Colors.red, size: 20),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Reason: ${booking.rejectionReason}',
                      style: TextStyle(color: Colors.red.shade900),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
```

---

## Android Configuration

### Update `android/app/src/main/AndroidManifest.xml`:

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <!-- Add permissions -->
    <uses-permission android:name="android.permission.INTERNET"/>
    <uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
    <uses-permission android:name="android.permission.VIBRATE"/>
    
    <application
        android:name="${applicationName}"
        android:label="Your App Name"
        android:icon="@mipmap/ic_launcher">
        
        <!-- ... existing activity -->
        
        <!-- Add notification channel -->
        <meta-data
            android:name="com.google.firebase.messaging.default_notification_channel_id"
            android:value="booking_channel" />
    </application>
</manifest>
```

---

## iOS Configuration

### Update `ios/Runner/Info.plist`:

```xml
<key>UIBackgroundModes</key>
<array>
    <string>fetch</string>
    <string>remote-notification</string>
</array>
```

### Enable Push Notifications Capability:
1. Open `ios/Runner.xcworkspace` in Xcode
2. Select Runner target
3. Go to "Signing & Capabilities"
4. Click "+ Capability"
5. Add "Push Notifications"
6. Add "Background Modes" and check "Remote notifications"

---

## Testing Guide

### 1. Test Backend Notification Sending:

You can test from backend using Tinker:

```php
php artisan tinker

// Send test notification
$helper = new App\Http\Helpers\PushNotificationHelper();
$helper->prepare(
    [4], // user_id
    [
        'title' => 'Test Notification',
        'desc' => 'This is a test push notification',
        'user_type' => 'user',
    ]
)->send();
```

### 2. Test Rejection Flow:

1. Login to vendor panel
2. Go to booking details
3. Click "Reject" button
4. Select a rejection reason
5. Submit
6. Mobile app should receive notification immediately

### 3. Verify Subscription:

Add debug logging in your app:

```dart
// After login
final userId = 4;
await PushNotificationService().subscribeUser(userId);

// Check Pusher Beams dashboard
// Look for user: {domain}-user-4
```

---

## Troubleshooting

### Issue: Not receiving notifications

**Check:**
1. ✅ User is logged in
2. ✅ Push notifications are enabled in settings
3. ✅ App has notification permissions
4. ✅ User ID is correct
5. ✅ Pusher Instance ID matches backend
6. ✅ App is subscribed to correct interest: `{domain}-user-{userId}`

### Issue: Notifications only work when app is open

**Solution:**
- Ensure Background Modes are enabled (iOS)
- Ensure FCM is properly configured (Android)
- Check notification channel is created (Android)

### Issue: Wrong publishable ID

**Check domain extraction:**
```dart
// Should match backend format
// Backend: localhost-user-4
// App should subscribe to: localhost-user-4
```

---

## API Endpoints Reference

### Get User Notifications:
```
GET /api/v1/user/notifications
```

Response includes rejection notifications with reasons.

### Reject Booking (Vendor):
```
POST /api/v1/vendor/booking/reject
Body: {
  "id": 123,
  "reason": "Vehicle Unavailable"
}
```

---

## State Management Integration

### Using Provider:

```dart
class NotificationProvider extends ChangeNotifier {
  List<NotificationModel> _notifications = [];

  void addNotification(NotificationModel notification) {
    _notifications.insert(0, notification);
    notifyListeners();
  }

  void handleBookingRejection(String bookingId, String reason) {
    // Update booking status in local state
    // Show dialog or navigation
    notifyListeners();
  }
}
```

### Using GetX:

```dart
class NotificationController extends GetxController {
  final notifications = <NotificationModel>[].obs;

  void onBookingRejected(String bookingId, String reason) {
    // Update booking
    Get.snackbar(
      'Booking Rejected',
      'Your booking was rejected. Reason: $reason',
      backgroundColor: Colors.red,
      colorText: Colors.white,
    );
  }
}
```

---

## Summary Checklist

- [ ] Add `pusher_beams` dependency
- [ ] Create `PushNotificationService`
- [ ] Initialize service in `main.dart`
- [ ] Subscribe user after login
- [ ] Unsubscribe on logout
- [ ] Add rejection reason to Booking model
- [ ] Update API parsing to include `rejection_reason`
- [ ] Show rejection reason in booking list/details
- [ ] Configure Android manifest
- [ ] Configure iOS capabilities
- [ ] Test notification flow end-to-end
- [ ] Handle foreground notifications
- [ ] Handle background notifications
- [ ] Handle notification tap navigation

---

## Additional Resources

- Pusher Beams Documentation: https://pusher.com/docs/beams
- Flutter Local Notifications: https://pub.dev/packages/flutter_local_notifications
- Pusher Beams Flutter SDK: https://pub.dev/packages/pusher_beams

---

## Support

For backend issues, check Laravel logs:
```bash
tail -f storage/logs/laravel.log | grep "push notification"
```

For frontend issues, enable debug logging in `PushNotificationService`.

---

**Last Updated**: February 15, 2026  
**Backend Version**: Laravel 10.x  
**Flutter SDK**: >=3.0.0  
**Pusher Instance ID**: `f80f6f50-1302-4c1f-b7ba-b64a57fcbd29`

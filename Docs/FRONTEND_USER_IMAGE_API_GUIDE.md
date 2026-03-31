# Frontend User Image API Guide

## Overview

This guide explains how the frontend retrieves user profile images from the Dorra Alaseel API and constructs the complete image URLs for display.

---

## API Endpoints

### 1. User Profile Info Endpoint

**Request:** `GET /api/v1/user/profile/info`

- **Authentication:** Required (Bearer token)
- **Guard:** `auth:api`
- **File:** [routes/api/v1/user.php](routes/api/v1/user.php#L59-L61)
- **Controller:** [app/Http/Controllers/Api/V1/User/ProfileController.php](app/Http/Controllers/Api/V1/User/ProfileController.php#L19-L57)

### 2. User Dashboard Endpoint (Alternative)

**Request:** `GET /api/v1/user/dashboard`

- **Authentication:** Required (Bearer token)
- **Returns:** User info + profile image paths
- **File:** [routes/api/v1/user.php](routes/api/v1/user.php#L72)
- **Controller:** [app/Http/Controllers/Api/V1/User/DashboardController.php](app/Http/Controllers/Api/V1/User/DashboardController.php#L1-L60)

---

## API Response Structure

### Success Response Format

```json
{
  "message": {
    "success": ["Profile info fetch successfully!"]
  },
  "data": {
    "instructions": {
      "kyc_verified": "0: Default, 1: Approved, 2: Pending, 3:Rejected"
    },
    "user_info": {
      "id": 1,
      "firstname": "John",
      "lastname": "Doe",
      "username": "john_doe",
      "email": "john@example.com",
      "mobile_code": "+966",
      "mobile": "501234567",
      "image": "profile_123abc.jpg",
      "kyc_verified": 1,
      "country": "Saudi Arabia",
      "city": "Riyadh",
      "state": "Riyadh",
      "postal_code": "12345",
      "address": "123 Main Street"
    },
    "image_paths": {
      "base_url": "http://192.168.1.211:8001",
      "path_location": "public/frontend/user",
      "default_image": "public/backend/images/default/profile-default.webp"
    },
    "countries": [...]
  },
  "type": "success"
}
```

---

## Image URL Construction

### Required Components

1. **base_url** - Server base URL from API response
2. **path_location** - Directory path for user images
3. **default_image** - Fallback image path if user has no image
4. **user_info.image** - User's uploaded image filename

### Construction Logic

```javascript
// If user has uploaded a profile image
if (userInfo.image) {
    const imageUrl = `${imagePaths.base_url}/${imagePaths.path_location}/${userInfo.image}`;
    // Example URL: http://192.168.1.211:8001/public/frontend/user/profile_123abc.jpg
}

// If user doesn't have an image, use default
else {
    const imageUrl = `${imagePaths.base_url}/${imagePaths.default_image}`;
    // Example URL: http://192.168.1.211:8001/public/backend/images/default/profile-default.webp
}
```

---

## Frontend Implementation Examples

### React Implementation

```javascript
import { useEffect, useState } from "react";

function UserProfile() {
    const [userInfo, setUserInfo] = useState(null);
    const [imagePaths, setImagePaths] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Fetch user profile info
        fetch("/api/v1/user/profile/info", {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                "Content-Type": "application/json",
            },
        })
            .then((response) => response.json())
            .then((data) => {
                const { user_info, image_paths } = data.data;
                setUserInfo(user_info);
                setImagePaths(image_paths);
                setLoading(false);
            })
            .catch((error) => {
                console.error("Error fetching profile:", error);
                setLoading(false);
            });
    }, []);

    if (loading) return <div>Loading...</div>;

    // Build image URL
    const profileImageUrl = userInfo?.image
        ? `${imagePaths.base_url}/${imagePaths.path_location}/${userInfo.image}`
        : `${imagePaths.base_url}/${imagePaths.default_image}`;

    return (
        <div className="profile">
            <img
                src={profileImageUrl}
                alt="Profile"
                className="profile-image"
            />
            <h2>
                {userInfo?.firstname} {userInfo?.lastname}
            </h2>
            <p>{userInfo?.email}</p>
        </div>
    );
}

export default UserProfile;
```

### Vue 3 Implementation

```vue
<template>
    <div class="profile">
        <img
            v-if="!loading"
            :src="profileImageUrl"
            alt="Profile"
            class="profile-image"
        />
        <h2>{{ userInfo?.firstname }} {{ userInfo?.lastname }}</h2>
        <p>{{ userInfo?.email }}</p>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";

const userInfo = ref(null);
const imagePaths = ref(null);
const loading = ref(true);

const profileImageUrl = computed(() => {
    if (!userInfo.value || !imagePaths.value) return "";

    if (userInfo.value.image) {
        return `${imagePaths.value.base_url}/${imagePaths.value.path_location}/${userInfo.value.image}`;
    }

    return `${imagePaths.value.base_url}/${imagePaths.value.default_image}`;
});

onMounted(async () => {
    try {
        const response = await fetch("/api/v1/user/profile/info", {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                "Content-Type": "application/json",
            },
        });

        const data = await response.json();
        userInfo.value = data.data.user_info;
        imagePaths.value = data.data.image_paths;
    } catch (error) {
        console.error("Error fetching profile:", error);
    } finally {
        loading.value = false;
    }
});
</script>
```

### Flutter Implementation

```dart
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class UserProfile extends StatefulWidget {
  final String authToken;

  const UserProfile({required this.authToken});

  @override
  State<UserProfile> createState() => _UserProfileState();
}

class _UserProfileState extends State<UserProfile> {
  late Future<UserProfileData> futureProfile;

  @override
  void initState() {
    super.initState();
    futureProfile = fetchUserProfile();
  }

  Future<UserProfileData> fetchUserProfile() async {
    final response = await http.get(
      Uri.parse('http://192.168.1.211:8001/api/v1/user/profile/info'),
      headers: {
        'Authorization': 'Bearer ${widget.authToken}',
        'Content-Type': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return UserProfileData.fromJson(data['data']);
    } else {
      throw Exception('Failed to load profile');
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<UserProfileData>(
      future: futureProfile,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        } else if (snapshot.hasError) {
          return Center(child: Text('Error: ${snapshot.error}'));
        } else {
          final profile = snapshot.data!;
          return SingleChildScrollView(
            child: Column(
              children: [
                CircleAvatar(
                  radius: 50,
                  backgroundImage: NetworkImage(profile.profileImageUrl),
                ),
                const SizedBox(height: 16),
                Text(
                  '${profile.userInfo['firstname']} ${profile.userInfo['lastname']}',
                  style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                ),
                Text(profile.userInfo['email']),
              ],
            ),
          );
        }
      },
    );
  }
}

class UserProfileData {
  final Map<String, dynamic> userInfo;
  final Map<String, String> imagePaths;

  UserProfileData({required this.userInfo, required this.imagePaths});

  String get profileImageUrl {
    if (userInfo['image'] != null && userInfo['image'].isNotEmpty) {
      return '${imagePaths['base_url']}/${imagePaths['path_location']}/${userInfo['image']}';
    }
    return '${imagePaths['base_url']}/${imagePaths['default_image']}';
  }

  factory UserProfileData.fromJson(Map<String, dynamic> json) {
    return UserProfileData(
      userInfo: json['user_info'] ?? {},
      imagePaths: {
        'base_url': json['image_paths']?['base_url'] ?? '',
        'path_location': json['image_paths']?['path_location'] ?? '',
        'default_image': json['image_paths']?['default_image'] ?? '',
      },
    );
  }
}
```

---

## Server-Side Storage

### Directory Structure

```
public/
├── frontend/
│   └── user/                          # User profile images
│       ├── profile_123abc.jpg
│       ├── profile_456def.jpg
│       └── ... (user profile images)
│
└── backend/
    └── images/
        └── default/
            ├── default.webp
            └── profile-default.webp   # Default fallback image
```

### Path Definitions

View in [app/Http/Helpers/helpers.php](app/Http/Helpers/helpers.php#L455-L456):

```php
'user-profile' => [
    'path' => 'frontend/user',
],
```

The `files_asset_path('user-profile')` helper function returns:

```
public/frontend/user
```

---

## User Image Model Accessor

The `User` model has a `userImage` accessor that automatically resolves image URLs:

**File:** [app/Models/User.php](app/Models/User.php#L160-L170)

```php
public function getUserImageAttribute()
{
    $image = $this->image;

    if ($image == null) {
        return files_asset_path('profile-default');
    } else if (filter_var($image, FILTER_VALIDATE_URL)) {
        return $image;
    } else {
        return files_asset_path("user-profile") . "/" . $image;
    }
}
```

**Usage:** `$user->userImage` returns the complete URL

---

## Upload Process

### User Profile Update

**POST** `/api/v1/user/profile/info/update`

**Process:**

1. Frontend sends image file in multipart/form-data
2. Backend validates and uploads to `junk-files` directory
3. Moves to final `user-profile` directory
4. Stores filename in `users.image` column

**Controller:** [app/Http/Controllers/Api/V1/User/ProfileController.php](app/Http/Controllers/Api/V1/User/ProfileController.php#L101-L102)

```php
$image = upload_file($validated['image'], 'junk-files', $user->image);
$upload_image = upload_files_from_path_dynamic([$image['dev_path']], 'user-profile');
```

---

## KYC Verification Status

The `kyc_verified` field indicates user verification status:

- **0** - Default (no verification)
- **1** - Approved
- **2** - Pending
- **3** - Rejected

---

## Error Handling

### Handling Missing Images

```javascript
// Safe implementation with fallback
function getProfileImageUrl(userInfo, imagePaths) {
    try {
        if (!userInfo || !imagePaths) {
            return imagePaths?.base_url + "/" + imagePaths?.default_image;
        }

        if (userInfo.image && typeof userInfo.image === "string") {
            return `${imagePaths.base_url}/${imagePaths.path_location}/${userInfo.image}`;
        }

        return `${imagePaths.base_url}/${imagePaths.default_image}`;
    } catch (error) {
        console.error("Error constructing image URL:", error);
        return null; // Return null and handle in UI
    }
}
```

### Image Loading with Error Handling

```javascript
<img
    src={profileImageUrl}
    alt="Profile"
    onError={(e) => {
        // Fallback to default if image fails to load
        e.target.src = defaultImageUrl;
    }}
/>
```

---

## Best Practices

1. **Cache Image Paths** - Store `image_paths` in state/context to avoid repeated API calls
2. **Error Handling** - Always provide fallback to default image
3. **Image Optimization** - Consider using image compression/CDN for production
4. **Validation** - Validate image filename before displaying
5. **Lazy Loading** - Lazy load profile images in lists to improve performance
6. **Responsive Images** - Use responsive image techniques for different screen sizes

---

## Related Files

- **API Route:** [routes/api/v1/user.php](routes/api/v1/user.php)
- **Profile Controller:** [app/Http/Controllers/Api/V1/User/ProfileController.php](app/Http/Controllers/Api/V1/User/ProfileController.php)
- **Dashboard Controller:** [app/Http/Controllers/Api/V1/User/DashboardController.php](app/Http/Controllers/Api/V1/User/DashboardController.php)
- **User Model:** [app/Models/User.php](app/Models/User.php)
- **Helper Functions:** [app/Http/Helpers/helpers.php](app/Http/Helpers/helpers.php)

---

## Testing

### Using Postman

1. **Get Auth Token**
    - POST `/api/v1/auth/login` with credentials
    - Copy `token` from response

2. **Fetch User Profile**
    - GET `/api/v1/user/profile/info`
    - Add Header: `Authorization: Bearer {token}`
    - Response includes `image_paths` and `user_info`

3. **Construct URL**
    - Use values from `image_paths` and `user_info.image`
    - Test URL in browser to verify image loads

---

## Troubleshooting

| Issue                     | Solution                                                           |
| ------------------------- | ------------------------------------------------------------------ |
| Image returns 404         | Check if file exists in `public/frontend/user/`                    |
| Image path is null        | User may not have uploaded an image; use default                   |
| Default image not showing | Verify `public/backend/images/default/profile-default.webp` exists |
| CORS errors               | Check CORS configuration in `config/cors.php`                      |
| Auth token invalid        | Ensure token is not expired; refresh if needed                     |

# National ID & Driving License Image Upload - Fixed Implementation

## Fixed Issues

### 1. **Helper Function Error**

- **Problem**: Used non-existent `files_asset_path_basename()` function
- **Solution**: Simplified to direct paths without using helper function
- **File**: `app/Http/Controllers/Api/V1/User/IdentificationController.php`

### 2. **File Paths Configuration**

- **Problem**: Helper function was throwing exception
- **Solution**: Use hardcoded public folder paths instead
- **Changes**:
    ```php
    $image_paths = [
        'base_url'              => url("/"),
        'national_id_path'      => "public/frontend/user-national-id",
        'driving_license_path'  => "public/frontend/user-driving-license",
    ];
    ```

### 3. **Error Logging**

- **Added**: `Log::error()` calls in exception handlers to track actual errors
- **Impact**: Better debugging when issues occur

---

## Current Implementation Status

### ✅ Database

- Migration created: `2026_02_14_000001_add_national_id_to_users_table.php`
- Columns added: `national_id`, `driving_license` (both nullable strings)

### ✅ Model

- User model updated with casts for both fields

### ✅ API Endpoints

All endpoints fully implemented in `IdentificationController`:

#### GET Endpoints

```
GET /api/v1/user/identification/info
GET /api/v1/user/identification/all
```

#### Upload Endpoints

```
POST /api/v1/user/identification/national-id/upload
POST /api/v1/user/identification/driving-license/upload
```

#### Delete Endpoints

```
DELETE /api/v1/user/identification/national-id/delete
DELETE /api/v1/user/identification/driving-license/delete
```

### ✅ Image Folders

```
public/frontend/user-national-id/
public/frontend/user-driving-license/
```

### ✅ Routes

All routes registered in `routes/api/v1/user.php`

---

## Testing the API

### 1. Get User Identification Info (GET)

```bash
curl -X GET "http://192.168.1.211:8001/api/v1/user/identification/all" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Expected Response (200 OK):**

```json
{
    "message": ["All identification images fetched successfully!"],
    "status": true,
    "data": {
        "user_id": 123,
        "national_id": null,
        "driving_license": null,
        "identification_complete": false,
        "image_paths": {
            "base_url": "http://192.168.1.211:8001",
            "national_id_path": "public/frontend/user-national-id",
            "driving_license_path": "public/frontend/user-driving-license"
        }
    },
    "code": 200
}
```

### 2. Upload National ID Image (POST with multipart/form-data)

```bash
curl -X POST "http://192.168.1.211:8001/api/v1/user/identification/national-id/upload" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "national_id=@/path/to/image.jpg"
```

**Expected Response (200 OK):**

```json
{
    "message": ["National ID image successfully uploaded!"],
    "status": true,
    "data": {
        "national_id": "frontend/user-national-id/national_id_user_123_abc123.jpg"
    },
    "code": 200
}
```

### 3. Delete National ID Image (DELETE)

```bash
curl -X DELETE "http://192.168.1.211:8001/api/v1/user/identification/national-id/delete" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response (200 OK):**

```json
{
    "message": ["National ID image successfully deleted!"],
    "status": true,
    "data": [],
    "code": 200
}
```

---

## Getting a Valid Bearer Token

### Option 1: Using Postman/Insomnia

1. Login to your app first
2. Copy the token from login response
3. Use token in Authorization header

### Option 2: Using PHP Artisan

```bash
php artisan tinker

# Inside tinker:
$user = App\Models\User::find(1);  // Replace 1 with desired user ID
$token = $user->createToken('api-token')->accessToken;
echo $token;
```

### Option 3: Login Via API

```bash
curl -X POST "http://192.168.1.211:8001/api/auth/login" \
  -H "Content-Type: application/json" \
  -d {
    "login": "your-username-or-email",
    "password": "your-password"
  }
```

---

## Common Errors & Solutions

### Error: 401 Unauthorized

- **Cause**: Invalid or missing bearer token
- **Solution**: Use a valid token from user login

### Error: 422 Validation Failed

- **Cause**: Invalid file format or missing file
- **Solution**:
    - Ensure file is image (jpg, png, jpeg, gif, webp)
    - File size must be < 10 MB
    - Use correct field name in form data

### Error: 500 Internal Server Error

- **New**: Should be rare after fixing helper function
- **Check**: Laravel logs in `storage/logs/laravel.log`
- **Common causes**:
    - File upload directory not writable
    - Insufficient disk space
    - Database connection issues

---

## File Changes Summary

### Modified Files:

1. **app/Http/Helpers/helpers.php**
    - Added entries for `user-national-id` and `user-driving-license`

2. **app/Http/Controllers/Api/V1/User/IdentificationController.php**
    - Fixed helper function calls
    - Added error logging
    - Implemented all 6 endpoints

3. **routes/api/v1/user.php**
    - Registered identification routes

### Created Files:

1. **database/migrations/2026_02_14_000001_add_national_id_to_users_table.php**
    - Migration for national_id column

2. **public/frontend/user-national-id/**
    - Image storage directory

3. **public/frontend/user-driving-license/**
    - Image storage directory

---

## Next Steps

1. **Test with valid token** - Use one of the token generation methods above
2. **Verify image upload** - Upload a test image and check `public/frontend/user-national-id/`
3. **Check logs** - Monitor `storage/logs/laravel.log` for any issues
4. **Flutter integration** - Reference `NATIONAL_ID_FLUTTER_INTEGRATION_GUIDE.md` for mobile implementation

---

## Important Notes

- Both `national_id` and `driving_license` columns accept image file paths (URLs)
- Images are stored in dedicated public folders for security
- Old images are automatically deleted when new ones are uploaded
- All endpoints require Bearer token authentication
- File size limit: 10 MB
- Allowed formats: JPG, PNG, JPEG, GIF, WebP

---

**Last Updated**: February 14, 2026 - 00:55 AM

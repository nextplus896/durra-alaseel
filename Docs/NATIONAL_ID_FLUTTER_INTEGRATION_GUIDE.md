# Flutter National ID & Driving License Image Upload Guide

## Overview

This guide explains how to integrate National ID and Driving License image uploads into your Flutter application. The API provides endpoints to upload, view, and delete images of national ID and driving license documents.

---

## API Base URL

```
https://your-domain/api/v1/user/identification
```

## Authentication

All endpoints require a Bearer token. Add the authentication header to all requests:

```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. Get User Identification Images

**Endpoint:** `GET /api/v1/user/identification/info`

**Description:** Retrieve the current user's national ID and driving license image URLs.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** Empty (No body required)

**Response Example (Success - 200):**

```json
{
    "message": ["Identification info fetched successfully!"],
    "status": true,
    "data": {
        "instructions": {
            "national_id": "Image URL path for national ID document",
            "driving_license": "Image URL path for driving license document"
        },
        "identification": {
            "national_id": "frontend/user-national-id/national_id_user_123_6584956.jpg",
            "driving_license": "frontend/user-driving-license/driving_lic_user_123_8945123.jpg"
        },
        "image_paths": {
            "base_url": "https://your-domain",
            "national_id_path_location": "frontend/user-national-id",
            "driving_license_path_location": "frontend/user-driving-license"
        }
    },
    "code": 200
}
```

**Response Example (No images uploaded - 200):**

```json
{
    "message": ["Identification info fetched successfully!"],
    "status": true,
    "data": {
        "instructions": {
            "national_id": "Image URL path for national ID document",
            "driving_license": "Image URL path for driving license document"
        },
        "identification": {
            "national_id": null,
            "driving_license": null
        },
        "image_paths": {
            "base_url": "https://your-domain",
            "national_id_path_location": "frontend/user-national-id",
            "driving_license_path_location": "frontend/user-driving-license"
        }
    },
    "code": 200
}
```

---

### 2. Get All Identification Images

**Endpoint:** `GET /api/v1/user/identification/all`

**Description:** Retrieve both national ID and driving license image URLs with completion status.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** Empty (No body required)

**Response Example (Success - 200):**

```json
{
    "message": ["All identification images fetched successfully!"],
    "status": true,
    "data": {
        "user_id": 123,
        "national_id": "frontend/user-national-id/national_id_user_123_6584956.jpg",
        "driving_license": "frontend/user-driving-license/driving_lic_user_123_8945123.jpg",
        "identification_complete": true,
        "image_paths": {
            "base_url": "https://your-domain",
            "national_id_path_location": "frontend/user-national-id",
            "driving_license_path_location": "frontend/user-driving-license"
        }
    },
    "code": 200
}
```

**Response Example (Incomplete - 200):**

```json
{
    "message": ["All identification images fetched successfully!"],
    "status": true,
    "data": {
        "user_id": 123,
        "national_id": "frontend/user-national-id/national_id_user_123_6584956.jpg",
        "driving_license": null,
        "identification_complete": false,
        "image_paths": {
            "base_url": "https://your-domain",
            "national_id_path_location": "frontend/user-national-id",
            "driving_license_path_location": "frontend/user-driving-license"
        }
    },
    "code": 200
}
```

---

### 3. Upload National ID Image

**Endpoint:** `POST /api/v1/user/identification/national-id/upload`

**Description:** Upload an image of the user's national ID document.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
| Field | Type | Required | Max Size | Allowed Formats |
|-------|------|----------|----------|-----------------|
| national_id | File/Image | Yes | 10 MB | jpg, png, jpeg, gif, webp |

**Example using cURL:**

```bash
curl -X POST \
  https://your-domain/api/v1/user/identification/national-id/upload \
  -H "Authorization: Bearer {token}" \
  -F "national_id=@/path/to/national_id.jpg"
```

**Valid Response (Success - 200):**

```json
{
    "message": ["National ID image successfully uploaded!"],
    "status": true,
    "data": {
        "national_id": "frontend/user-national-id/national_id_user_123_6584956.jpg"
    },
    "code": 200
}
```

**Error Response - Invalid File Format (422):**

```json
{
    "message": [
        "The national_id field must be a file of type: jpg, png, jpeg, gif, webp."
    ],
    "status": false,
    "data": [],
    "code": 422
}
```

**Error Response - File Too Large (422):**

```json
{
    "message": [
        "The national_id field must not be greater than 10240 kilobytes."
    ],
    "status": false,
    "data": [],
    "code": 422
}
```

**Error Response - No File Provided (422):**

```json
{
    "message": ["The national_id field is required."],
    "status": false,
    "data": [],
    "code": 422
}
```

---

### 4. Delete National ID Image

**Endpoint:** `DELETE /api/v1/user/identification/national-id/delete`

**Description:** Remove the user's national ID image.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** Empty (No body required)

**Valid Response (Success - 200):**

```json
{
    "message": ["National ID image successfully deleted!"],
    "status": true,
    "data": [],
    "code": 200
}
```

**Error Response - No Image Found (400):**

```json
{
    "message": ["No national ID image found to delete"],
    "status": false,
    "data": [],
    "code": 400
}
```

**Error Response - Server Error (500):**

```json
{
    "message": ["Something went wrong! Please try again"],
    "status": false,
    "data": [],
    "code": 500
}
```

---

### 5. Upload Driving License Image

**Endpoint:** `POST /api/v1/user/identification/driving-license/upload`

**Description:** Upload an image of the user's driving license document.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
| Field | Type | Required | Max Size | Allowed Formats |
|-------|------|----------|----------|-----------------|
| driving_license | File/Image | Yes | 10 MB | jpg, png, jpeg, gif, webp |

**Example using cURL:**

```bash
curl -X POST \
  https://your-domain/api/v1/user/identification/driving-license/upload \
  -H "Authorization: Bearer {token}" \
  -F "driving_license=@/path/to/driving_license.jpg"
```

**Valid Response (Success - 200):**

```json
{
    "message": ["Driving license image successfully uploaded!"],
    "status": true,
    "data": {
        "driving_license": "frontend/user-driving-license/driving_lic_user_123_8945123.jpg"
    },
    "code": 200
}
```

**Error Response - Invalid File Format (422):**

```json
{
    "message": [
        "The driving_license field must be a file of type: jpg, png, jpeg, gif, webp."
    ],
    "status": false,
    "data": [],
    "code": 422
}
```

**Error Response - File Too Large (422):**

```json
{
    "message": [
        "The driving_license field must not be greater than 10240 kilobytes."
    ],
    "status": false,
    "data": [],
    "code": 422
}
```

**Error Response - No File Provided (422):**

```json
{
    "message": ["The driving_license field is required."],
    "status": false,
    "data": [],
    "code": 422
}
```

---

### 6. Delete Driving License Image

**Endpoint:** `DELETE /api/v1/user/identification/driving-license/delete`

**Description:** Remove the user's driving license image.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** Empty (No body required)

**Valid Response (Success - 200):**

```json
{
    "message": ["Driving license image successfully deleted!"],
    "status": true,
    "data": [],
    "code": 200
}
```

**Error Response - No Image Found (400):**

```json
{
    "message": ["No driving license image found to delete"],
    "status": false,
    "data": [],
    "code": 400
}
```

**Error Response - Server Error (500):**

```json
{
    "message": ["Something went wrong! Please try again"],
    "status": false,
    "data": [],
    "code": 500
}
```

---

## Flutter Implementation Example

### 1. Create a Model Class

```dart
class IdentificationModel {
  final String? nationalId;
  final String? drivingLicense;
  final bool? identificationComplete;
  final String? baseUrl;
  final String? nationalIdPathLocation;
  final String? drivingLicensePathLocation;

  IdentificationModel({
    this.nationalId,
    this.drivingLicense,
    this.identificationComplete,
    this.baseUrl,
    this.nationalIdPathLocation,
    this.drivingLicensePathLocation,
  });

  factory IdentificationModel.fromJson(Map<String, dynamic> json) {
    return IdentificationModel(
      nationalId: json['national_id'],
      drivingLicense: json['driving_license'],
      identificationComplete: json['identification_complete'],
      baseUrl: json['image_paths']?['base_url'],
      nationalIdPathLocation: json['image_paths']?['national_id_path_location'],
      drivingLicensePathLocation: json['image_paths']?['driving_license_path_location'],
    );
  }

  // Helper method to get full image URLs
  String? getNationalIdFullUrl() {
    if (nationalId != null && baseUrl != null) {
      return '$baseUrl/$nationalId';
    }
    return null;
  }

  String? getDrivingLicenseFullUrl() {
    if (drivingLicense != null && baseUrl != null) {
      return '$baseUrl/$drivingLicense';
    }
    return null;
  }
}
```

### 2. Create a Service Class

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:io';

class IdentificationService {
  final String baseUrl = 'https://your-domain/api/v1/user/identification';
  final String token;

  IdentificationService({required this.token});

  Map<String, String> get headers => {
    'Authorization': 'Bearer $token',
  };

  // Get identification info
  Future<IdentificationModel> getIdentificationInfo() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/info'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return IdentificationModel.fromJson(data['data']);
      } else {
        throw Exception('Failed to fetch identification info');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Get all identification data
  Future<IdentificationModel> getAllIdentifications() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/all'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return IdentificationModel.fromJson(data['data']);
      } else {
        throw Exception('Failed to fetch all identifications');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Upload national ID image
  Future<String> uploadNationalIdImage(File imageFile) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/national-id/upload'),
      );

      request.headers.addAll(headers);
      request.files.add(
        await http.MultipartFile.fromPath(
          'national_id',
          imageFile.path,
        ),
      );

      var response = await request.send();
      var responseBody = await response.stream.bytesToString();

      if (response.statusCode == 200) {
        final data = jsonDecode(responseBody);
        return data['data']['national_id'];
      } else if (response.statusCode == 422) {
        final data = jsonDecode(responseBody);
        throw Exception(data['message'][0]);
      } else {
        throw Exception('Failed to upload national ID image');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Delete national ID image
  Future<bool> deleteNationalIdImage() async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/national-id/delete'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        return true;
      } else if (response.statusCode == 400) {
        final data = jsonDecode(response.body);
        throw Exception(data['message'][0]);
      } else {
        throw Exception('Failed to delete national ID image');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Upload driving license image
  Future<String> uploadDrivingLicenseImage(File imageFile) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/driving-license/upload'),
      );

      request.headers.addAll(headers);
      request.files.add(
        await http.MultipartFile.fromPath(
          'driving_license',
          imageFile.path,
        ),
      );

      var response = await request.send();
      var responseBody = await response.stream.bytesToString();

      if (response.statusCode == 200) {
        final data = jsonDecode(responseBody);
        return data['data']['driving_license'];
      } else if (response.statusCode == 422) {
        final data = jsonDecode(responseBody);
        throw Exception(data['message'][0]);
      } else {
        throw Exception('Failed to upload driving license image');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Delete driving license image
  Future<bool> deleteDrivingLicenseImage() async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/driving-license/delete'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        return true;
      } else if (response.statusCode == 400) {
        final data = jsonDecode(response.body);
        throw Exception(data['message'][0]);
      } else {
        throw Exception('Failed to delete driving license image');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }
}
```

### 3. Create a Provider/State Management

Using Provider package example:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'dart:io';

class IdentificationProvider with ChangeNotifier {
  final IdentificationService _service;

  IdentificationModel? _identification;
  bool _isLoading = false;
  String? _errorMessage;
  File? _selectedNationalIdImage;
  File? _selectedDrivingLicenseImage;

  IdentificationProvider({required IdentificationService service})
      : _service = service;

  // Getters
  IdentificationModel? get identification => _identification;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  File? get selectedNationalIdImage => _selectedNationalIdImage;
  File? get selectedDrivingLicenseImage => _selectedDrivingLicenseImage;

  // Setters for selected images
  void setNationalIdImage(File? file) {
    _selectedNationalIdImage = file;
    notifyListeners();
  }

  void setDrivingLicenseImage(File? file) {
    _selectedDrivingLicenseImage = file;
    notifyListeners();
  }

  // Fetch all identifications
  Future<void> fetchAllIdentifications() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _identification = await _service.getAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Upload national ID image
  Future<void> uploadNationalIdImage(File imageFile) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _service.uploadNationalIdImage(imageFile);
      _selectedNationalIdImage = null;
      await fetchAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      rethrow;
    }
  }

  // Delete national ID image
  Future<void> deleteNationalIdImage() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _service.deleteNationalIdImage();
      await fetchAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      rethrow;
    }
  }

  // Upload driving license image
  Future<void> uploadDrivingLicenseImage(File imageFile) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _service.uploadDrivingLicenseImage(imageFile);
      _selectedDrivingLicenseImage = null;
      await fetchAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      rethrow;
    }
  }

  // Delete driving license image
  Future<void> deleteDrivingLicenseImage() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _service.deleteDrivingLicenseImage();
      await fetchAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      rethrow;
    }
  }
}
```

### 4. Create Image Picker Helper

```dart
import 'package:image_picker/image_picker.dart';
import 'dart:io';

class ImagePickerHelper {
  static final ImagePicker _imagePicker = ImagePicker();

  static Future<File?> pickImageFromGallery() async {
    try {
      final XFile? pickedFile = await _imagePicker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 80,
      );

      if (pickedFile != null) {
        return File(pickedFile.path);
      }
      return null;
    } catch (e) {
      throw Exception('Error picking image: $e');
    }
  }

  static Future<File?> pickImageFromCamera() async {
    try {
      final XFile? pickedFile = await _imagePicker.pickImage(
        source: ImageSource.camera,
        imageQuality: 80,
      );

      if (pickedFile != null) {
        return File(pickedFile.path);
      }
      return null;
    } catch (e) {
      throw Exception('Error capturing image: $e');
    }
  }
}
```

### 5. Create UI Screens

**Identification Screen Example:**

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'dart:io';

class IdentificationScreen extends StatefulWidget {
  @override
  _IdentificationScreenState createState() => _IdentificationScreenState();
}

class _IdentificationScreenState extends State<IdentificationScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<IdentificationProvider>().fetchAllIdentifications();
    });
  }

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }

  Future<void> _pickAndUploadNationalId() async {
    try {
      final file = await ImagePickerHelper.pickImageFromGallery();
      if (file != null) {
        context.read<IdentificationProvider>().setNationalIdImage(file);
        await context.read<IdentificationProvider>().uploadNationalIdImage(file);
        _showSnackBar('National ID image uploaded successfully');
      }
    } catch (e) {
      _showSnackBar('Error: $e', isError: true);
    }
  }

  Future<void> _pickAndUploadDrivingLicense() async {
    try {
      final file = await ImagePickerHelper.pickImageFromGallery();
      if (file != null) {
        context.read<IdentificationProvider>().setDrivingLicenseImage(file);
        await context.read<IdentificationProvider>().uploadDrivingLicenseImage(file);
        _showSnackBar('Driving license image uploaded successfully');
      }
    } catch (e) {
      _showSnackBar('Error: $e', isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Identification Documents'),
      ),
      body: Consumer<IdentificationProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading) {
            return Center(child: CircularProgressIndicator());
          }

          return SingleChildScrollView(
            padding: EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // National ID Section
                _buildDocumentCard(
                  title: 'National ID',
                  imageUrl: provider.identification?.getNationalIdFullUrl(),
                  onUpload: _pickAndUploadNationalId,
                  onDelete: provider.identification?.nationalId != null
                      ? () async {
                          try {
                            await provider.deleteNationalIdImage();
                            _showSnackBar('National ID deleted successfully');
                          } catch (e) {
                            _showSnackBar('Error: $e', isError: true);
                          }
                        }
                      : null,
                ),
                SizedBox(height: 24),

                // Driving License Section
                _buildDocumentCard(
                  title: 'Driving License',
                  imageUrl: provider.identification?.getDrivingLicenseFullUrl(),
                  onUpload: _pickAndUploadDrivingLicense,
                  onDelete: provider.identification?.drivingLicense != null
                      ? () async {
                          try {
                            await provider.deleteDrivingLicenseImage();
                            _showSnackBar('Driving license deleted successfully');
                          } catch (e) {
                            _showSnackBar('Error: $e', isError: true);
                          }
                        }
                      : null,
                ),
                SizedBox(height: 24),

                // Completion Status
                if (provider.identification != null)
                  _buildStatusCard(
                    isComplete: provider.identification!.identificationComplete ?? false,
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildDocumentCard({
    required String title,
    required String? imageUrl,
    required VoidCallback onUpload,
    required VoidCallback? onDelete,
  }) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            SizedBox(height: 16),

            // Image Preview
            if (imageUrl != null)
              Container(
                width: double.infinity,
                height: 200,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(8),
                  color: Colors.grey[200],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    imageUrl,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) {
                      return Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.image_not_supported, size: 48),
                            SizedBox(height: 8),
                            Text('Failed to load image'),
                          ],
                        ),
                      );
                    },
                  ),
                ),
              )
            else
              Container(
                width: double.infinity,
                height: 200,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(
                    color: Colors.grey[300]!,
                    width: 2,
                  ),
                  color: Colors.grey[50],
                ),
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.cloud_upload, size: 48, color: Colors.grey),
                      SizedBox(height: 8),
                      Text('No image uploaded'),
                    ],
                  ),
                ),
              ),
            SizedBox(height: 16),

            // Action Buttons
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: onUpload,
                    icon: Icon(Icons.upload),
                    label: Text('Upload Image'),
                  ),
                ),
                if (onDelete != null) ...[
                  SizedBox(width: 8),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: onDelete,
                      icon: Icon(Icons.delete),
                      label: Text('Delete'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.red,
                      ),
                    ),
                  ),
                ]
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusCard({required bool isComplete}) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
      ),
      color: isComplete ? Colors.green[50] : Colors.orange[50],
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(
              isComplete ? Icons.check_circle : Icons.info,
              color: isComplete ? Colors.green : Colors.orange,
              size: 32,
            ),
            SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    isComplete ? 'All Documents Complete' : 'Incomplete Documents',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: isComplete ? Colors.green : Colors.orange,
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    isComplete
                        ? 'You have uploaded all required documents'
                        : 'Please upload all documents to complete verification',
                    style: TextStyle(
                      color: Colors.grey[700],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## Image Upload Requirements

| Property            | Value                              |
| ------------------- | ---------------------------------- |
| **Max File Size**   | 10 MB                              |
| **Allowed Formats** | JPG, PNG, JPEG, GIF, WebP          |
| **Content Type**    | multipart/form-data                |
| **Field Name**      | `national_id` or `driving_license` |

---

## HTTP Status Codes

| Code | Name                  | Meaning                                        |
| ---- | --------------------- | ---------------------------------------------- |
| 200  | OK                    | Request was successful                         |
| 400  | Bad Request           | Image doesn't exist or other validation errors |
| 422  | Unprocessable Entity  | Validation failed (invalid file type/size)     |
| 500  | Internal Server Error | Server-side error                              |

---

## Error Handling

Always check the `status` field in the response:

- **status: true** = Successful request
- **status: false** = Failed request

Check the `message` array for error details. The first element usually contains the main error message.

**Example Error Handling in Flutter:**

```dart
Future<void> uploadImage(File imageFile) async {
  try {
    final response = await _service.uploadNationalIdImage(imageFile);
    // Handle success
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Image uploaded successfully')),
    );
  } catch (e) {
    // Handle error
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Error: $e'),
        backgroundColor: Colors.red,
      ),
    );
  }
}
```

---

## Folder Structure

Images are stored in the following server directories:

```
public/
└── frontend/
    ├── user-national-id/         (National ID images)
    └── user-driving-license/     (Driving license images)
```

Full URL format:

```
https://your-domain/frontend/user-national-id/{filename}
https://your-domain/frontend/user-driving-license/{filename}
```

---

## Important Notes

1. **Image Validation**: Only image files are accepted. PDF or document formats are not allowed.
2. **File Size Limit**: Maximum 10 MB per image.
3. **Replace on Upload**: Uploading a new image will replace the old one automatically.
4. **Required Authentication**: All endpoints require a valid Bearer token.
5. **Unique Storage**: Each user's images are stored separately in their respective folders.
6. **Nullable Fields**: Both images can be null if not yet uploaded.

---

## Testing with Postman

### Setup:

1. Set environment variable: `token` = your_auth_token
2. Set environment variable: `base_url` = your_api_base_url

### Test Get Identification Info:

```
GET {{base_url}}/api/v1/user/identification/info
Authorization: Bearer {{token}}
```

### Test Upload National ID:

```
POST {{base_url}}/api/v1/user/identification/national-id/upload
Authorization: Bearer {{token}}

Form Data:
Key: national_id
Type: File
Value: <select image file>
```

### Test Delete National ID:

```
DELETE {{base_url}}/api/v1/user/identification/national-id/delete
Authorization: Bearer {{token}}
```

---

## Troubleshooting

### Issue: "The national_id field must be a file of type: jpg, png, jpeg, gif, webp"

**Solution**: Ensure the uploaded file is an image in one of the supported formats.

### Issue: "The national_id field must not be greater than 10240 kilobytes"

**Solution**: Compress your image or select a smaller file. Maximum size is 10 MB.

### Issue: "The national_id field is required"

**Solution**: Ensure the file is selected and being sent in the request.

### Issue: "No national ID image found to delete"

**Solution**: The user hasn't uploaded a national ID image yet. Call the upload endpoint first.

### Issue: 401 Unauthorized

**Solution**: Check that the Bearer token is valid and hasn't expired.

### Issue: 500 Internal Server Error

**Solution**: This is a server error. Try again later or contact support if the problem persists.

---

## Dependencies for Flutter

Add these to your `pubspec.yaml`:

```yaml
dependencies:
    flutter:
        sdk: flutter
    http: ^1.1.0
    image_picker: ^1.0.0
    provider: ^6.0.0
```

---

## Support

For issues or questions related to this API, contact:

- **Email**: support@your-domain.com
- **Documentation**: https://your-domain.com/api-docs

---

**Last Updated**: February 14, 2026
**API Version**: v1

---

### 2. Get All Identification Information

**Endpoint:** `GET /api/v1/user/identification/all`

**Description:** Retrieve both national ID and driving license, with completion status.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** Empty (No body required)

**Response Example (Success - 200):**

```json
{
    "message": ["All identification info fetched successfully!"],
    "status": true,
    "data": {
        "user_id": 123,
        "national_id": "1234567890123",
        "driving_license": "DL123456789ABC",
        "identification_complete": true
    },
    "code": 200
}
```

**Response Example (Incomplete - 200):**

```json
{
    "message": ["All identification info fetched successfully!"],
    "status": true,
    "data": {
        "user_id": 123,
        "national_id": "1234567890123",
        "driving_license": null,
        "identification_complete": false
    },
    "code": 200
}
```

---

### 3. Update National ID

**Endpoint:** `POST /api/v1/user/identification/national-id/update`

**Description:** Add or update the user's national ID.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "national_id": "1234567890123"
}
```

**Request Body Field Specifications:**
| Field | Type | Required | Max Length | Description |
|-------|------|----------|-----------|-------------|
| national_id | String | Yes | 255 | User's national ID number |

**Valid Response (Success - 200):**

```json
{
    "message": ["National ID successfully updated!"],
    "status": true,
    "data": {
        "national_id": "1234567890123"
    },
    "code": 200
}
```

**Error Response - Duplicate National ID (400):**

```json
{
    "message": ["National ID already exists for another user"],
    "status": false,
    "data": [],
    "code": 400
}
```

**Error Response - Validation Failed (422):**

```json
{
    "message": ["The national_id field is required."],
    "status": false,
    "data": [],
    "code": 422
}
```

**Error Response - Server Error (500):**

```json
{
    "message": ["Something went wrong! Please try again"],
    "status": false,
    "data": [],
    "code": 500
}
```

---

### 4. Delete National ID

**Endpoint:** `DELETE /api/v1/user/identification/national-id/delete`

**Description:** Remove the user's national ID.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** Empty (No body required)

**Valid Response (Success - 200):**

```json
{
    "message": ["National ID successfully deleted!"],
    "status": true,
    "data": [],
    "code": 200
}
```

**Error Response - No ID Found (400):**

```json
{
    "message": ["No national ID found to delete"],
    "status": false,
    "data": [],
    "code": 400
}
```

**Error Response - Server Error (500):**

```json
{
    "message": ["Something went wrong! Please try again"],
    "status": false,
    "data": [],
    "code": 500
}
```

---

### 5. Update Driving License

**Endpoint:** `POST /api/v1/user/identification/driving-license/update`

**Description:** Add or update the user's driving license.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "driving_license": "DL123456789ABC"
}
```

**Request Body Field Specifications:**
| Field | Type | Required | Max Length | Description |
|-------|------|----------|-----------|-------------|
| driving_license | String | Yes | 255 | User's driving license number |

**Valid Response (Success - 200):**

```json
{
    "message": ["Driving license successfully updated!"],
    "status": true,
    "data": {
        "driving_license": "DL123456789ABC"
    },
    "code": 200
}
```

**Error Response - Duplicate License (400):**

```json
{
    "message": ["Driving license already exists for another user"],
    "status": false,
    "data": [],
    "code": 400
}
```

**Error Response - Validation Failed (422):**

```json
{
    "message": ["The driving_license field is required."],
    "status": false,
    "data": [],
    "code": 422
}
```

**Error Response - Server Error (500):**

```json
{
    "message": ["Something went wrong! Please try again"],
    "status": false,
    "data": [],
    "code": 500
}
```

---

### 6. Delete Driving License

**Endpoint:** `DELETE /api/v1/user/identification/driving-license/delete`

**Description:** Remove the user's driving license.

**Request Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** Empty (No body required)

**Valid Response (Success - 200):**

```json
{
    "message": ["Driving license successfully deleted!"],
    "status": true,
    "data": [],
    "code": 200
}
```

**Error Response - No License Found (400):**

```json
{
    "message": ["No driving license found to delete"],
    "status": false,
    "data": [],
    "code": 400
}
```

**Error Response - Server Error (500):**

```json
{
    "message": ["Something went wrong! Please try again"],
    "status": false,
    "data": [],
    "code": 500
}
```

---

## Flutter Implementation Example

### 1. Create a Model Class

```dart
class IdentificationModel {
  final String? nationalId;
  final String? drivingLicense;
  final bool? identificationComplete;

  IdentificationModel({
    this.nationalId,
    this.drivingLicense,
    this.identificationComplete,
  });

  factory IdentificationModel.fromJson(Map<String, dynamic> json) {
    return IdentificationModel(
      nationalId: json['national_id'],
      drivingLicense: json['driving_license'],
      identificationComplete: json['identification_complete'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'national_id': nationalId,
      'driving_license': drivingLicense,
      'identification_complete': identificationComplete,
    };
  }
}
```

### 2. Create a Service Class

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class IdentificationService {
  final String baseUrl = 'https://your-domain/api/v1/user/identification';
  final String token;

  IdentificationService({required this.token});

  Map<String, String> get headers => {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
  };

  // Get identification info
  Future<Map<String, dynamic>> getIdentificationInfo() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/info'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data']['identification'];
      } else {
        throw Exception('Failed to fetch identification info');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Get all identification data
  Future<IdentificationModel> getAllIdentifications() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/all'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return IdentificationModel.fromJson(data['data']);
      } else {
        throw Exception('Failed to fetch all identifications');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Update national ID
  Future<Map<String, dynamic>> updateNationalId(String nationalId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/national-id/update'),
        headers: headers,
        body: jsonEncode({
          'national_id': nationalId,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data'];
      } else if (response.statusCode == 400) {
        final data = jsonDecode(response.body);
        throw Exception(data['message'][0]);
      } else if (response.statusCode == 422) {
        final data = jsonDecode(response.body);
        throw Exception(data['message'][0]);
      } else {
        throw Exception('Failed to update national ID');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Delete national ID
  Future<bool> deleteNationalId() async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/national-id/delete'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        return true;
      } else if (response.statusCode == 400) {
        final data = jsonDecode(response.body);
        throw Exception(data['message'][0]);
      } else {
        throw Exception('Failed to delete national ID');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Update driving license
  Future<Map<String, dynamic>> updateDrivingLicense(String drivingLicense) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/driving-license/update'),
        headers: headers,
        body: jsonEncode({
          'driving_license': drivingLicense,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data'];
      } else if (response.statusCode == 400) {
        final data = jsonDecode(response.body);
        throw Exception(data['message'][0]);
      } else if (response.statusCode == 422) {
        final data = jsonDecode(response.body);
        throw Exception(data['message'][0]);
      } else {
        throw Exception('Failed to update driving license');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  // Delete driving license
  Future<bool> deleteDrivingLicense() async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/driving-license/delete'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        return true;
      } else if (response.statusCode == 400) {
        final data = jsonDecode(response.body);
        throw Exception(data['message'][0]);
      } else {
        throw Exception('Failed to delete driving license');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }
}
```

### 3. Create a Provider/State Management

Using Provider package example:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class IdentificationProvider with ChangeNotifier {
  final IdentificationService _service;

  IdentificationModel? _identification;
  bool _isLoading = false;
  String? _errorMessage;

  IdentificationProvider({required IdentificationService service})
      : _service = service;

  // Getters
  IdentificationModel? get identification => _identification;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  // Fetch all identifications
  Future<void> fetchAllIdentifications() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _identification = await _service.getAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Update national ID
  Future<void> updateNationalId(String nationalId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _service.updateNationalId(nationalId);
      await fetchAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      rethrow;
    }
  }

  // Delete national ID
  Future<void> deleteNationalId() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _service.deleteNationalId();
      await fetchAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      rethrow;
    }
  }

  // Update driving license
  Future<void> updateDrivingLicense(String drivingLicense) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _service.updateDrivingLicense(drivingLicense);
      await fetchAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      rethrow;
    }
  }

  // Delete driving license
  Future<void> deleteDrivingLicense() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _service.deleteDrivingLicense();
      await fetchAllIdentifications();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      rethrow;
    }
  }
}
```

### 4. Create UI Screens

**Identification Screen Example:**

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class IdentificationScreen extends StatefulWidget {
  @override
  _IdentificationScreenState createState() => _IdentificationScreenState();
}

class _IdentificationScreenState extends State<IdentificationScreen> {
  final _nationalIdController = TextEditingController();
  final _drivingLicenseController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<IdentificationProvider>().fetchAllIdentifications();
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final identification = context.watch<IdentificationProvider>().identification;
    if (identification != null) {
      _nationalIdController.text = identification.nationalId ?? '';
      _drivingLicenseController.text = identification.drivingLicense ?? '';
    }
  }

  void _showSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Identification'),
      ),
      body: Consumer<IdentificationProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading) {
            return Center(child: CircularProgressIndicator());
          }

          return SingleChildScrollView(
            padding: EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // National ID Section
                Text(
                  'National ID',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                SizedBox(height: 8),
                TextField(
                  controller: _nationalIdController,
                  decoration: InputDecoration(
                    hintText: 'Enter your national ID',
                    border: OutlineInputBorder(),
                  ),
                ),
                SizedBox(height: 8),
                Row(
                  children: [
                    ElevatedButton(
                      onPressed: () async {
                        if (_nationalIdController.text.isNotEmpty) {
                          try {
                            await provider.updateNationalId(
                              _nationalIdController.text,
                            );
                            _showSnackBar('National ID updated successfully');
                          } catch (e) {
                            _showSnackBar('Error: $e');
                          }
                        }
                      },
                      child: Text('Update'),
                    ),
                    SizedBox(width: 8),
                    ElevatedButton(
                      onPressed: provider.identification?.nationalId != null
                          ? () async {
                              try {
                                await provider.deleteNationalId();
                                _showSnackBar('National ID deleted successfully');
                                _nationalIdController.clear();
                              } catch (e) {
                                _showSnackBar('Error: $e');
                              }
                            }
                          : null,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.red,
                      ),
                      child: Text('Delete'),
                    ),
                  ],
                ),
                SizedBox(height: 24),

                // Driving License Section
                Text(
                  'Driving License',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                SizedBox(height: 8),
                TextField(
                  controller: _drivingLicenseController,
                  decoration: InputDecoration(
                    hintText: 'Enter your driving license number',
                    border: OutlineInputBorder(),
                  ),
                ),
                SizedBox(height: 8),
                Row(
                  children: [
                    ElevatedButton(
                      onPressed: () async {
                        if (_drivingLicenseController.text.isNotEmpty) {
                          try {
                            await provider.updateDrivingLicense(
                              _drivingLicenseController.text,
                            );
                            _showSnackBar('Driving license updated successfully');
                          } catch (e) {
                            _showSnackBar('Error: $e');
                          }
                        }
                      },
                      child: Text('Update'),
                    ),
                    SizedBox(width: 8),
                    ElevatedButton(
                      onPressed: provider.identification?.drivingLicense != null
                          ? () async {
                              try {
                                await provider.deleteDrivingLicense();
                                _showSnackBar('Driving license deleted successfully');
                                _drivingLicenseController.clear();
                              } catch (e) {
                                _showSnackBar('Error: $e');
                              }
                            }
                          : null,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.red,
                      ),
                      child: Text('Delete'),
                    ),
                  ],
                ),
                SizedBox(height: 24),

                // Completion Status
                if (provider.identification != null)
                  Container(
                    padding: EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          provider.identification!.identificationComplete == true
                              ? Icons.check_circle
                              : Icons.info,
                          color: provider.identification!.identificationComplete == true
                              ? Colors.green
                              : Colors.orange,
                        ),
                        SizedBox(width: 8),
                        Text(
                          provider.identification!.identificationComplete == true
                              ? 'All identifications complete'
                              : 'Please complete all identifications',
                          style: TextStyle(
                            color: provider.identification!.identificationComplete == true
                                ? Colors.green
                                : Colors.orange,
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  @override
  void dispose() {
    _nationalIdController.dispose();
    _drivingLicenseController.dispose();
    super.dispose();
  }
}
```

---

## HTTP Status Codes

| Code | Name                  | Meaning                                                       |
| ---- | --------------------- | ------------------------------------------------------------- |
| 200  | OK                    | Request was successful                                        |
| 400  | Bad Request           | National ID/License already exists or other validation errors |
| 422  | Unprocessable Entity  | Validation failed on request fields                           |
| 500  | Internal Server Error | Server-side error                                             |

---

## Error Handling

Always check the `status` field in the response:

- **status: true** = Successful request
- **status: false** = Failed request

Check the `message` array for error details. The first element usually contains the main error message.

**Example Error Handling in Flutter:**

```dart
Future<void> updateNationalId(String id) async {
  try {
    final response = await _service.updateNationalId(id);
    // Handle success
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('National ID updated successfully')),
    );
  } catch (e) {
    // Handle error
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Error: $e'),
        backgroundColor: Colors.red,
      ),
    );
  }
}
```

---

## Important Notes

1. **Unique Constraint**: National ID and Driving License must be unique across all users in the system.
2. **Required Authentication**: All endpoints require a valid Bearer token.
3. **Validation**: All fields have maximum length of 255 characters.
4. **Error Handling**: Always check for error messages in the response `message` array.
5. **Data Persistence**: After successful update/delete operations, the database is updated immediately.
6. **Nullable Fields**: Both national_id and driving_license can be null (empty).

---

## Testing with Postman

### Setup:

1. Import the collection from your API documentation
2. Set the environment variable: `token` = your_auth_token
3. Set the environment variable: `base_url` = your_api_base_url

### Test Get Identification Info:

```
GET {{base_url}}/api/v1/user/identification/info
Authorization: Bearer {{token}}
```

### Test Update National ID:

```
POST {{base_url}}/api/v1/user/identification/national-id/update
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "national_id": "1234567890123"
}
```

### Test Delete National ID:

```
DELETE {{base_url}}/api/v1/user/identification/national-id/delete
Authorization: Bearer {{token}}
```

---

## Troubleshooting

### Issue: "National ID already exists for another user"

**Solution**: Use a unique national ID that hasn't been registered by another user.

### Issue: "The national_id field is required"

**Solution**: Ensure the request body includes the `national_id` field with a non-empty string value.

### Issue: "No national ID found to delete"

**Solution**: The user hasn't set a national ID yet. Call the update endpoint first.

### Issue: 401 Unauthorized

**Solution**: Check that the Bearer token is valid and hasn't expired.

### Issue: 500 Internal Server Error

**Solution**: This is a server error. Try again later or contact support if the problem persists.

---

## Support

For issues or questions related to this API, contact:

- **Email**: support@your-domain.com
- **Documentation**: https://your-domain.com/api-docs

---

**Last Updated**: February 14, 2026
**API Version**: v1

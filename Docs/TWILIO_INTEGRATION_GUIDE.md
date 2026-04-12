# Twilio OTP Integration Guide

## Overview

This integration adds Twilio Verify API support for sending OTP (One-Time Password) verification codes via SMS and WhatsApp to your Laravel application. It includes API endpoints for Flutter mobile integration and an admin dashboard for monitoring usage.

## Features

-   ✅ Send OTP via SMS using Twilio Verify API
-   ✅ Send OTP via WhatsApp using Twilio Verify API
-   ✅ Verify OTP codes with automatic expiration
-   ✅ RESTful API endpoints for Flutter mobile apps
-   ✅ Rate limiting (10 requests per minute)
-   ✅ Webhook support for delivery status tracking
-   ✅ Admin dashboard with usage statistics and cost tracking
-   ✅ Database logging for audit trails
-   ✅ Role-based access control

## Installation Steps

### 1. Install Twilio SDK

The Twilio SDK has already been added to `composer.json`. If you need to reinstall:

```bash
composer require twilio/sdk --ignore-platform-reqs
```

### 2. Configure Environment Variables

Add the following to your `.env` file:

```env
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_VERIFY_SID=your_verify_service_sid_here
TWILIO_FROM_NUMBER=+1234567890
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

**Getting Twilio Credentials:**

1. Sign up at [https://www.twilio.com/](https://www.twilio.com/)
2. Create a Verify Service in Twilio Console → Verify → Services
3. Copy your Account SID, Auth Token, and Verify Service SID
4. For SMS: Purchase a phone number with SMS capabilities
5. For WhatsApp: Set up WhatsApp Business API or use Twilio Sandbox for testing

### 3. Run Migrations

Database tables have been created. If you need to run them again:

```bash
php artisan migrate
```

This creates two tables:

-   `phone_verifications` - Stores OTP verification attempts
-   `twilio_messages` - Tracks message delivery and costs

## API Endpoints for Flutter

### Base URL

```
https://your-domain.com/api/v1/otp
```

### 1. Request OTP

**Endpoint:** `POST /api/v1/otp/request`

**Headers:**

```json
{
    "Content-Type": "application/json",
    "Accept": "application/json"
}
```

**Request Body:**

```json
{
    "phone": "+966555123456",
    "channel": "sms",
    "locale": "ar"
}
```

**Parameters:**

-   `phone` (required): Phone number in E.164 format (e.g., +966555123456)
-   `channel` (required): Either `sms` or `whatsapp`
-   `locale` (optional): Language code (`en`, `ar`, `es`, `fr`). Default: `en`

**Success Response (200):**

```json
{
    "success": true,
    "message": "OTP sent successfully",
    "data": {
        "verification_id": 123,
        "channel": "sms",
        "to": "+966555123456",
        "status": "pending"
    }
}
```

**Error Response (400):**

```json
{
    "success": false,
    "message": "Failed to send OTP",
    "error_code": 21211
}
```

**Validation Error (422):**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "phone": ["The phone field is required."],
        "channel": ["The selected channel is invalid."]
    }
}
```

### 2. Verify OTP

**Endpoint:** `POST /api/v1/otp/verify`

**Request Body:**

```json
{
    "phone": "+966555123456",
    "code": "123456"
}
```

**Parameters:**

-   `phone` (required): Same phone number used to request OTP
-   `code` (required): 6-digit verification code

**Success Response (200):**

```json
{
    "success": true,
    "message": "Phone verified successfully",
    "data": {
        "verification_id": 123,
        "status": "approved"
    }
}
```

**Error Response (400):**

```json
{
    "success": false,
    "message": "Invalid verification code"
}
```

### Rate Limiting

-   Limited to **10 requests per minute** per IP address
-   Returns HTTP 429 if exceeded

### Flutter Example

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class TwilioOtpService {
  final String baseUrl = 'https://your-domain.com/api/v1/otp';

  // Request OTP
  Future<Map<String, dynamic>> requestOtp(String phone, String channel) async {
    final response = await http.post(
      Uri.parse('$baseUrl/request'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'phone': phone,
        'channel': channel, // 'sms' or 'whatsapp'
        'locale': 'ar',
      }),
    );

    return jsonDecode(response.body);
  }

  // Verify OTP
  Future<Map<String, dynamic>> verifyOtp(String phone, String code) async {
    final response = await http.post(
      Uri.parse('$baseUrl/verify'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'phone': phone,
        'code': code,
      }),
    );

    return jsonDecode(response.body);
  }
}
```

## Twilio Webhooks (Optional but Recommended)

To track message delivery status and costs, configure webhooks in your Twilio Console:

### Status Callback URL

```
https://your-domain.com/api/webhooks/twilio/status
```

### Pricing Callback URL (Optional)

```
https://your-domain.com/api/webhooks/twilio/pricing
```

**How to Configure:**

1. Go to Twilio Console → Messaging → Settings → Geo Permissions
2. Under "Configure your Webhooks", add the Status Callback URL
3. These webhooks will automatically update message delivery status in your database

## Admin Dashboard

### Accessing Twilio Usage

1. Log in to your admin panel
2. Navigate to **Bonus → Twilio Usage** in the sidebar
3. View SMS and WhatsApp statistics including:
    - Total messages sent
    - Delivery rates
    - Failed messages
    - Total costs (USD)
    - Recent message logs

### Features

-   **Date Filtering**: Filter statistics by date range
-   **Channel Breakdown**: Separate stats for SMS and WhatsApp
-   **Real-time Tracking**: Last 50 messages with status and error details
-   **Cost Monitoring**: Track spending per channel

### Permissions

The admin route is protected by role-based permissions. Ensure admin users have the `admin.twilio.usage.index` permission in the role management system.

## Phone Number Format

All phone numbers must be in **E.164 format**:

-   Starts with `+` followed by country code
-   No spaces, dashes, or parentheses
-   Examples:
    -   Saudi Arabia: `+966555123456`
    -   UAE: `+971501234567`
    -   USA: `+14155552671`

The service automatically formats numbers if the `+` is missing.

## WhatsApp Setup

### Sandbox (Testing)

Twilio provides a WhatsApp Sandbox for testing:

1. Go to Twilio Console → Messaging → Try it out → Send a WhatsApp message
2. Follow instructions to join the sandbox
3. Use `whatsapp:+14155238886` as the sender (default)

### Production

For production WhatsApp messaging:

1. Apply for WhatsApp Business API approval via Twilio
2. Create message templates in Twilio Console
3. Update `TWILIO_WHATSAPP_FROM` with your approved sender ID
4. Note: Message templates must be pre-approved by WhatsApp

## Database Schema

### phone_verifications

```sql
- id (bigint, primary key)
- phone (string, indexed)
- channel (string: sms|whatsapp)
- verification_sid (string, Twilio SID)
- status (string: pending|approved|canceled)
- attempts (tinyint)
- verified_at (timestamp, nullable)
- expires_at (timestamp, nullable)
- metadata (json)
- created_at, updated_at
```

### twilio_messages

```sql
- id (bigint, primary key)
- message_sid (string, unique, Twilio Message SID)
- account_sid (string)
- to (string)
- from (string)
- channel (string: sms|whatsapp)
- status (string: queued|sent|delivered|failed|undelivered)
- direction (string)
- body (text)
- error_code (string, nullable)
- error_message (text, nullable)
- price (decimal, nullable)
- price_unit (string, nullable)
- verification_id (foreign key to phone_verifications)
- metadata (json)
- created_at, updated_at
```

## Security Considerations

1. **Rate Limiting**: API endpoints are throttled to 10 requests/minute
2. **OTP Expiration**: Codes expire after 10 minutes
3. **Attempt Tracking**: Failed verification attempts are logged
4. **Webhook Validation**: Consider adding Twilio signature validation for webhooks in production
5. **SSL/TLS**: Ensure your webhook URLs use HTTPS

## Testing

### Test SMS

```bash
curl -X POST https://your-domain.com/api/v1/otp/request \
  -H "Content-Type: application/json" \
  -d '{"phone":"+966555123456","channel":"sms","locale":"ar"}'
```

### Test WhatsApp

```bash
curl -X POST https://your-domain.com/api/v1/otp/request \
  -H "Content-Type: application/json" \
  -d '{"phone":"+966555123456","channel":"whatsapp","locale":"ar"}'
```

### Test Verification

```bash
curl -X POST https://your-domain.com/api/v1/otp/verify \
  -H "Content-Type: application/json" \
  -d '{"phone":"+966555123456","code":"123456"}'
```

## Troubleshooting

### Common Errors

**Error 21211: Invalid 'To' Phone Number**

-   Ensure phone is in E.164 format (+countrycode + number)
-   Check the number is valid and not blocked

**Error 20003: Authentication Error**

-   Verify TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN in .env
-   Ensure no extra spaces in credentials

**Error 20404: Resource Not Found**

-   Check TWILIO_VERIFY_SID is correct
-   Ensure Verify Service is active in Twilio Console

**WhatsApp messages not sending**

-   Confirm you've joined the sandbox (for testing)
-   Check WhatsApp templates are approved (for production)
-   Verify TWILIO_WHATSAPP_FROM is correct

### Logs

Check Laravel logs for detailed error messages:

```bash
tail -f storage/logs/laravel.log
```

## Cost Estimation

Twilio pricing (approximate, verify on Twilio website):

-   **SMS**: ~$0.0075 - $0.10 per message (varies by country)
-   **WhatsApp**: ~$0.005 - $0.09 per message (template + session fees)
-   **Verify API**: Included in message cost

The admin dashboard tracks actual costs from Twilio's pricing webhooks.

## Support

For Twilio-specific issues:

-   [Twilio Documentation](https://www.twilio.com/docs)
-   [Twilio Support](https://support.twilio.com/)

For integration issues:

-   Check `storage/logs/laravel.log`
-   Review database entries in `phone_verifications` and `twilio_messages`
-   Verify all environment variables are set correctly

## Files Created/Modified

### New Files

-   `app/Services/TwilioService.php` - Twilio integration service
-   `app/Models/PhoneVerification.php` - OTP verification model
-   `app/Models/TwilioMessage.php` - Message tracking model
-   `app/Http/Controllers/Api/V1/OtpController.php` - API controller
-   `app/Http/Controllers/Webhooks/TwilioWebhookController.php` - Webhook handler
-   `app/Http/Controllers/Admin/TwilioUsageController.php` - Admin dashboard controller
-   `resources/views/admin/sections/twilio-usage/index.blade.php` - Admin view
-   `database/migrations/2025_11_17_000001_create_phone_verifications_table.php`
-   `database/migrations/2025_11_17_000002_create_twilio_messages_table.php`

### Modified Files

-   `composer.json` - Added twilio/sdk dependency
-   `config/services.php` - Added Twilio configuration
-   `.env.example` - Added Twilio environment variables
-   `routes/api.php` - Added OTP and webhook routes
-   `routes/admin.php` - Added admin usage route
-   `resources/views/admin/partials/side-nav.blade.php` - Added menu item
-   `config/system-role-permissions.php` - Added permissions

---

**Version:** 1.0  
**Last Updated:** November 17, 2025

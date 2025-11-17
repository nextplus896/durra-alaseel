# Twilio OTP Integration - Postman Testing Guide

## 🚀 Quick Setup

### Step 1: Import Postman Collection

1. Open Postman
2. Click **Import** button (top-left)
3. Select **File** tab
4. Choose `Twilio_OTP_Postman_Collection.json` from project root
5. Click **Import**

### Step 2: Configure Environment Variables

The collection comes with pre-configured variables:

-   `base_url`: `http://192.168.1.211:8000`
-   `verification_id`: (auto-populated from test responses)
-   `phone_tested`: (auto-populated from test responses)

## 📝 Testing Scenarios

### Scenario 1: Send & Verify OTP via SMS

#### Step 1A: Request OTP via SMS

**Endpoint:** `1. Request OTP via SMS`

```json
{
    "phone": "+966555123456",
    "channel": "sms",
    "locale": "ar"
}
```

**Expected Response (200):**

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

✅ **What happens:**

-   SMS with OTP code is sent to the phone number
-   `verification_id` is automatically saved to Postman environment
-   Message is logged in `twilio_messages` table

---

#### Step 1B: Verify OTP Code

**Endpoint:** `3. Verify OTP Code`

```json
{
    "phone": "+966555123456",
    "code": "123456"
}
```

⚠️ **IMPORTANT:** Replace `"123456"` with the actual code you received

**Expected Response (200):**

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

---

### Scenario 2: Send OTP via WhatsApp

#### Step 2: Request OTP via WhatsApp

**Endpoint:** `2. Request OTP via WhatsApp`

```json
{
    "phone": "+966555123456",
    "channel": "whatsapp",
    "locale": "ar"
}
```

**Expected Response (200):**

```json
{
    "success": true,
    "message": "OTP sent successfully",
    "data": {
        "verification_id": 124,
        "channel": "whatsapp",
        "to": "+966555123456",
        "status": "pending"
    }
}
```

⚠️ **Note:** WhatsApp requires:

-   Twilio Sandbox enrollment (for testing)
-   Production WhatsApp approval (for live use)

---

### Scenario 3: Test Error Handling

#### Invalid Phone Number

**Request:**

```json
{
    "phone": "123",
    "channel": "sms"
}
```

**Expected Response (422):**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "phone": ["The phone field must be a valid phone number."]
    }
}
```

---

#### Invalid Channel

**Request:**

```json
{
    "phone": "+966555123456",
    "channel": "telegram"
}
```

**Expected Response (422):**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "channel": ["The selected channel is invalid."]
    }
}
```

---

#### Wrong Verification Code

**Request:**

```json
{
    "phone": "+966555123456",
    "code": "000000"
}
```

**Expected Response (400):**

```json
{
    "success": false,
    "message": "Invalid verification code"
}
```

---

### Scenario 4: Test Rate Limiting

Send the request **more than 10 times in 1 minute**

**Expected Response (429):**

```
HTTP 429 Too Many Requests
```

The API is limited to 10 requests per minute per IP address.

---

## 🔍 Webhook Testing

### Test Status Callback

**Endpoint:** `Simulate Status Callback`

This simulates Twilio sending delivery status updates.

**Request body (pre-filled):**

-   `MessageSid`: Unique message identifier
-   `MessageStatus`: delivered, sent, failed, undelivered
-   `To`: Recipient phone number
-   `From`: Sender phone number

**Expected Response (200):**

```json
{
    "status": "success"
}
```

**What happens:**

-   Message status is updated in `twilio_messages` table
-   Dashboard automatically shows delivery status

---

### Test Pricing Callback

**Endpoint:** `Simulate Pricing Callback`

This simulates Twilio sending cost information.

**Request body (pre-filled):**

-   `MessageSid`: Message identifier
-   `Price`: Cost (negative value from Twilio)
-   `PriceUnit`: USD

**Expected Response (200):**

```json
{
    "status": "success"
}
```

**What happens:**

-   Cost information is saved in `twilio_messages` table
-   Dashboard shows updated costs

---

## 📊 Check Results

### Via Postman

After testing, check responses in Postman:

1. Click on any request
2. Send it
3. Check **Response** tab for results
4. View **Tests** output showing auto-stored variables

### Via Database

```bash
# SSH into your server or use database client:

# View phone verifications
SELECT * FROM phone_verifications
WHERE phone = '+966555123456'
ORDER BY created_at DESC;

# View messages sent
SELECT * FROM twilio_messages
ORDER BY created_at DESC
LIMIT 10;
```

### Via Admin Dashboard

1. Navigate to `/admin/twilio-usage`
2. View:
    - Current account balance
    - SMS statistics
    - WhatsApp statistics
    - Recent messages table
    - Delivery rates

---

## 🧪 Complete Testing Flow

### Recommended Test Order:

1. **✓ Request OTP via SMS**

    - Record the verification_id
    - Confirm SMS received on your phone

2. **✓ Verify OTP Code**

    - Use the code from SMS
    - Confirm successful verification

3. **✓ Request OTP via WhatsApp**

    - Record the verification_id
    - Confirm WhatsApp message received

4. **✓ Simulate Webhook Callbacks**

    - Simulate status update (delivered)
    - Simulate pricing update
    - Verify in database

5. **✓ Check Admin Dashboard**

    - Verify balance displays
    - Check statistics updated
    - View message logs

6. **✓ Test Rate Limiting**
    - Send 11+ requests quickly
    - Confirm 429 response

---

## 🔧 Troubleshooting

### "Invalid 'To' Phone Number" Error

-   Phone must be in E.164 format: `+countrycode + number`
-   Example: `+966555123456` (not `0555123456`)

### "Authentication Error"

-   Verify Twilio credentials in `.env`:
    ```
    TWILIO_ACCOUNT_SID
    TWILIO_AUTH_TOKEN
    TWILIO_VERIFY_SID
    ```
-   Check credentials at https://console.twilio.com/

### "Verify Service Not Found"

-   Ensure `TWILIO_VERIFY_SID` is correct
-   Create Verify Service in Twilio Console if missing

### WhatsApp Message Not Received

-   Confirm Twilio Sandbox enrollment
-   Use registered test phone number
-   Check Twilio Console for sandbox status

### No SMS Received

-   Confirm Twilio phone number is valid
-   Check Twilio balance (must be positive)
-   Verify country isn't blocked by carrier

---

## 📱 Real-World Testing

### Test with Your Phone

1. Get your phone number in E.164 format
2. Request OTP to your number
3. Receive SMS/WhatsApp with code
4. Verify the code via Postman
5. Check admin dashboard for logs

### Test with Multiple Channels

1. Request via SMS
2. Wait for SMS
3. Verify SMS code
4. Request via WhatsApp
5. Wait for WhatsApp message
6. Verify WhatsApp code

---

## 📊 Example Successful Response

```json
{
    "success": true,
    "message": "OTP sent successfully",
    "data": {
        "verification_id": 1,
        "channel": "sms",
        "to": "+966555123456",
        "status": "pending"
    }
}
```

---

## 🔐 Security Notes

-   API limited to 10 requests/minute (prevents brute force)
-   OTP expires after 10 minutes
-   Codes are one-use only
-   All requests logged in database
-   Phone numbers stored in E.164 format
-   Delivery status tracked for audit

---

## 💡 Tips

-   Save frequently used phone numbers as Postman variables
-   Use Postman's pre-request scripts to auto-generate test data
-   Export test results for documentation
-   Monitor admin dashboard for real-time usage
-   Set up SMS forwarding to test multiple numbers

---

**Status:** Ready for Testing ✅  
**Last Updated:** November 18, 2025

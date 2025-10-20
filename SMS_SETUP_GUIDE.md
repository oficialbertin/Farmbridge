# SMS Integration Setup Guide - FarmBridgeAI

## Overview
This guide will help you integrate HDEV SMS Gateway into your FarmBridgeAI platform for sending notifications to users.

## Files Created
- `sms_parse.php` - Core SMS gateway class
- `sms_config.php` - Configuration file for API credentials
- `sms_config.example.php` - Example configuration file
- `sms_helpers.php` - Helper functions for common SMS operations
- `test_sms.php` - Test page to verify SMS functionality
- `SMS_SETUP_GUIDE.md` - This guide

## Setup Instructions

### Step 1: Get API Credentials
1. Visit [https://sms-api.hdev.rw](https://sms-api.hdev.rw)
2. Create an account or login
3. Navigate to your profile/dashboard
4. Copy your **API ID** and **API Key**
5. Register a **Sender ID** (e.g., "FarmBridge" or "FarmBridgeAI")

### Step 2: Configure SMS Settings
1. Open `sms_config.php` in your editor
2. Replace the placeholder values:
   ```php
   define('SMS_API_ID', 'your_actual_api_id_here');
   define('SMS_API_KEY', 'your_actual_api_key_here');
   define('SMS_SENDER_ID', 'FarmBridge'); // Your registered sender ID
   define('SMS_ENABLED', true); // Enable SMS sending
   ```
3. Save the file

### Step 3: Test SMS Integration
1. Open your browser and navigate to: `http://localhost/FarmBridgeAI/test_sms.php`
2. Fill in the test form with:
   - **Phone Number**: A valid Rwanda phone number (format: 250788123456)
   - **Message**: A test message
3. Click "Send SMS"
4. Check the response to verify the SMS was sent successfully

### Step 4: Top-up Your Account
Before sending SMS, you need to have credits in your HDEV SMS account:
1. Use the "Account Top-up" section in `test_sms.php`
2. Enter your Mobile Money number and amount
3. Complete the payment on your phone
4. Credits will be added to your account

## Usage in Your Application

### Basic SMS Sending
```php
// Include the helper file
require_once 'sms_helpers.php';

// Send a simple SMS
$result = send_sms('250788123456', 'Hello from FarmBridgeAI!');

if ($result['success']) {
    echo "SMS sent successfully!";
} else {
    echo "Failed: " . $result['message'];
}
```

### Pre-built Notification Functions

#### 1. Registration Welcome SMS
```php
send_registration_sms('250788123456', 'John Doe', 'farmer');
```

#### 2. Order Notification to Farmer
```php
send_order_notification_farmer(
    '250788123456',  // Farmer's phone
    'John Doe',      // Farmer's name
    'Tomatoes',      // Crop name
    50,              // Quantity
    'ORD-12345'      // Order ID
);
```

#### 3. Order Confirmation to Buyer
```php
send_order_confirmation_buyer(
    '250788123456',  // Buyer's phone
    'Jane Smith',    // Buyer's name
    'Tomatoes',      // Crop name
    50,              // Quantity
    'ORD-12345'      // Order ID
);
```

#### 4. Payment Confirmation
```php
send_payment_confirmation(
    '250788123456',  // Phone
    'John Doe',      // Name
    25000,           // Amount
    'ORD-12345'      // Order ID
);
```

#### 5. Order Status Update
```php
send_order_status_update(
    '250788123456',  // Phone
    'Jane Smith',    // Name
    'shipped',       // Status: confirmed, processing, shipped, delivered, cancelled
    'ORD-12345'      // Order ID
);
```

#### 6. Dispute Notification
```php
send_dispute_notification(
    '250788123456',  // Phone
    'John Doe',      // Name
    'ORD-12345',     // Order ID
    'Quality Issue'  // Dispute type
);
```

#### 7. Payment Reminder
```php
send_payment_reminder(
    '250788123456',  // Phone
    'Jane Smith',    // Name
    25000,           // Amount
    'ORD-12345',     // Order ID
    '2024-12-31'     // Due date
);
```

#### 8. Price Alert
```php
send_price_alert(
    '250788123456',  // Phone
    'John Doe',      // Name
    'Tomatoes',      // Crop name
    1500             // Price per kg
);
```

#### 9. Verification Code
```php
send_verification_code('250788123456', '123456');
```

### Phone Number Utilities
```php
// Format phone number
$formatted = format_phone_number('0788123456'); // Returns: 250788123456

// Validate phone number
if (validate_phone_number('250788123456')) {
    echo "Valid phone number";
}
```

### Bulk SMS
```php
$recipients = ['250788123456', '250788654321', '250788111222'];
$message = "Important announcement from FarmBridgeAI!";

$results = send_bulk_sms($recipients, $message);

foreach ($results as $phone => $result) {
    echo "$phone: " . ($result['success'] ? 'Sent' : 'Failed') . "\n";
}
```

## Integration Points in FarmBridgeAI

### 1. User Registration (`register.php`)
Add after successful registration:
```php
require_once 'sms_helpers.php';

// After user is created
if ($registration_success) {
    $phone = format_phone_number($_POST['phone']);
    send_registration_sms($phone, $user_name, $user_role);
}
```

### 2. New Order (`process_order.php`)
Add after order creation:
```php
require_once 'sms_helpers.php';

// Notify farmer
send_order_notification_farmer(
    $farmer_phone,
    $farmer_name,
    $crop_name,
    $quantity,
    $order_id
);

// Notify buyer
send_order_confirmation_buyer(
    $buyer_phone,
    $buyer_name,
    $crop_name,
    $quantity,
    $order_id
);
```

### 3. Payment Confirmation (`confirm_payment.php`)
Add after payment verification:
```php
require_once 'sms_helpers.php';

if ($payment_confirmed) {
    // Notify buyer
    send_payment_confirmation(
        $buyer_phone,
        $buyer_name,
        $amount,
        $order_id
    );
    
    // Notify farmer
    send_payment_confirmation(
        $farmer_phone,
        $farmer_name,
        $amount,
        $order_id
    );
}
```

### 4. Order Status Update (`update_order_status.php`)
Add when status changes:
```php
require_once 'sms_helpers.php';

send_order_status_update(
    $buyer_phone,
    $buyer_name,
    $new_status,
    $order_id
);
```

### 5. Dispute Creation (`raise_dispute.php`)
Add after dispute is created:
```php
require_once 'sms_helpers.php';

// Notify the other party
send_dispute_notification(
    $other_party_phone,
    $other_party_name,
    $order_id,
    $dispute_type
);
```

## Best Practices

### 1. Message Length
- Keep messages under 160 characters to avoid multiple SMS charges
- Use abbreviations where appropriate
- Include only essential information

### 2. Phone Number Format
- Always use `format_phone_number()` before sending
- Validate with `validate_phone_number()` first
- Store phone numbers in international format (250...)

### 3. Error Handling
```php
$result = send_sms($phone, $message);

if (!$result['success']) {
    // Log the error
    error_log("SMS failed: " . $result['message']);
    
    // Don't stop the process, just log
    // The user can still use the platform without SMS
}
```

### 4. Testing
- Always test with `SMS_ENABLED = false` first
- Use `test_sms.php` to verify configuration
- Test with real phone numbers before going live

### 5. Cost Management
- Monitor SMS usage regularly
- Set up low balance alerts
- Use SMS only for critical notifications
- Consider email as backup for non-urgent messages

## Troubleshooting

### SMS Not Sending
1. Check `sms_config.php` has correct credentials
2. Verify `SMS_ENABLED` is set to `true`
3. Check account balance on HDEV SMS dashboard
4. Verify phone number format (250...)
5. Check `sms_log.txt` for error messages

### Invalid API Credentials
- Double-check API ID and API Key
- Ensure no extra spaces in configuration
- Verify account is active on HDEV SMS

### Phone Number Issues
- Use format: 250788123456 (no spaces, dashes, or +)
- Validate with `validate_phone_number()`
- Test with your own number first

### Low Balance
- Top-up via `test_sms.php` or HDEV SMS dashboard
- Set up auto top-up if available
- Monitor balance regularly

## API Response Codes

### Success Response
```json
{
    "status": "success",
    "message": "SMS sent successfully",
    "sms_id": "12345",
    "balance": "1000"
}
```

### Error Response
```json
{
    "status": "error",
    "message": "Insufficient balance",
    "code": "LOW_BALANCE"
}
```

## Security Considerations

1. **Never commit `sms_config.php` to version control**
   - Add to `.gitignore`
   - Use `sms_config.example.php` for repository

2. **Protect API credentials**
   - Store securely
   - Don't expose in client-side code
   - Use environment variables in production

3. **Rate limiting**
   - Implement delays for bulk SMS
   - Monitor for abuse
   - Set daily limits per user

4. **Validation**
   - Always validate phone numbers
   - Sanitize message content
   - Verify user permissions

## Support

- **HDEV SMS Support**: info@hdevtech.cloud
- **API Documentation**: https://sms-api.hdev.rw
- **FarmBridgeAI Issues**: Check your project repository

## Next Steps

1. ✅ Test SMS sending with `test_sms.php`
2. ✅ Top-up your account
3. ⏳ Integrate into registration flow
4. ⏳ Add order notifications
5. ⏳ Implement payment confirmations
6. ⏳ Set up dispute alerts
7. ⏳ Monitor usage and costs

---

**Note**: Remember to keep your API credentials secure and never share them publicly!

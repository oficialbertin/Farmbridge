# SMS Integration Quick Reference

## 🚀 Quick Start

### 1. Setup (One-time)
```php
// Edit sms_config.php with your credentials
define('SMS_API_ID', 'your_api_id');
define('SMS_API_KEY', 'your_api_key');
define('SMS_ENABLED', true);
```

### 2. Test
Visit: `http://localhost/FarmBridgeAI/test_sms.php`

---

## 📱 Common Use Cases

### Registration Welcome
```php
require_once 'sms_helpers.php';
send_registration_sms($phone, $name, $role);
```

### New Order - Notify Farmer
```php
require_once 'sms_helpers.php';
send_order_notification_farmer($farmer_phone, $farmer_name, $crop_name, $quantity, $order_id);
```

### New Order - Confirm to Buyer
```php
require_once 'sms_helpers.php';
send_order_confirmation_buyer($buyer_phone, $buyer_name, $crop_name, $quantity, $order_id);
```

### Payment Confirmed
```php
require_once 'sms_helpers.php';
send_payment_confirmation($phone, $name, $amount, $order_id);
```

### Order Status Changed
```php
require_once 'sms_helpers.php';
// Status: confirmed, processing, shipped, delivered, cancelled
send_order_status_update($phone, $name, $status, $order_id);
```

### Dispute Raised
```php
require_once 'sms_helpers.php';
send_dispute_notification($phone, $name, $order_id, $dispute_type);
```

### Payment Reminder
```php
require_once 'sms_helpers.php';
send_payment_reminder($phone, $name, $amount, $order_id, $due_date);
```

### Verification Code
```php
require_once 'sms_helpers.php';
send_verification_code($phone, $code);
```

### Custom Message
```php
require_once 'sms_helpers.php';
$result = send_sms($phone, $message);

if ($result['success']) {
    echo "SMS sent!";
} else {
    echo "Failed: " . $result['message'];
}
```

---

## 🔧 Utilities

### Format Phone Number
```php
$formatted = format_phone_number('0788123456'); // Returns: 250788123456
```

### Validate Phone Number
```php
if (validate_phone_number($phone)) {
    // Valid Rwanda number
}
```

### Bulk SMS
```php
$phones = ['250788123456', '250788654321'];
$results = send_bulk_sms($phones, $message);
```

---

## 📋 Integration Checklist

- [ ] Get API credentials from https://sms-api.hdev.rw
- [ ] Update `sms_config.php` with credentials
- [ ] Set `SMS_ENABLED = true`
- [ ] Test with `test_sms.php`
- [ ] Top-up account balance
- [ ] Add to registration flow
- [ ] Add to order processing
- [ ] Add to payment confirmation
- [ ] Add to order status updates
- [ ] Add to dispute notifications

---

## ⚠️ Important Notes

1. **Phone Format**: Always use 250788123456 (no spaces/dashes)
2. **Message Length**: Keep under 160 characters
3. **Error Handling**: SMS failures shouldn't break your app
4. **Security**: Never commit `sms_config.php` to git
5. **Testing**: Test with real numbers before going live
6. **Balance**: Monitor SMS credits regularly

---

## 📞 Support

- HDEV SMS: info@hdevtech.cloud
- API Docs: https://sms-api.hdev.rw
- Test Page: http://localhost/FarmBridgeAI/test_sms.php
- Full Guide: SMS_SETUP_GUIDE.md

# SMS Webhook 404 Error - FIXED ✅

## Problem
The HDEV SMS Gateway was trying to send callbacks to your ngrok endpoint but received a **404 Not Found** error because the webhook endpoint didn't exist.

**Error Message:**
```
The endpoint 475b0069b9d5.ngrok-free.app is offline. ERR_NGROK_3200
Received unexpected response code: 404 Not Found
```

## Solution
Created a dedicated webhook endpoint to receive callbacks from HDEV SMS Gateway.

## Files Created

### 1. `sms_callback.php`
- **Purpose:** Receives delivery reports and incoming SMS from HDEV SMS Gateway
- **URL:** `http://localhost/FarmBridgeAI/sms_callback.php`
- **Features:**
  - Handles delivery reports (DLR)
  - Processes incoming SMS (MO - Mobile Originated)
  - Logs all callbacks for debugging
  - Returns proper JSON responses

### 2. `test_sms_webhook.php`
- **Purpose:** Test and configure the webhook
- **URL:** `http://localhost/FarmBridgeAI/test_sms_webhook.php`
- **Features:**
  - Check webhook status
  - View callback logs
  - Test webhook functionality
  - Ngrok setup instructions

## How to Fix the 404 Error

### Step 1: Make Sure XAMPP is Running
```bash
# Check if Apache is running
# Open XAMPP Control Panel and start Apache
```

### Step 2: Start Ngrok
```bash
# Open Command Prompt or PowerShell
ngrok http 80

# Or specify localhost explicitly
ngrok http localhost:80

# For better performance in Africa/Europe
ngrok http 80 --region eu
```

### Step 3: Copy Your Ngrok URL
Ngrok will display something like:
```
Forwarding  https://abc123def456.ngrok-free.app -> http://localhost:80
```

Copy the HTTPS URL (e.g., `https://abc123def456.ngrok-free.app`)

### Step 4: Update HDEV SMS Dashboard
1. Login to https://sms-api.hdev.rw
2. Go to Settings or Webhook Configuration
3. Set your callback URL to:
   ```
   https://YOUR-NGROK-URL.ngrok-free.app/FarmBridgeAI/sms_callback.php
   ```

**Example:**
```
https://475b0069b9d5.ngrok-free.app/FarmBridgeAI/sms_callback.php
```

### Step 5: Test the Webhook
1. Open `http://localhost/FarmBridgeAI/test_sms_webhook.php`
2. Click "Send Test Request"
3. Check if the webhook responds successfully
4. Send a real SMS and check the callback log

## Verifying the Fix

### Test Locally
```bash
# Test with curl
curl http://localhost/FarmBridgeAI/sms_callback.php

# Should return:
# {"status":"success","message":"HDEV SMS Webhook is online",...}
```

### Test via Ngrok
```bash
# Replace with your actual ngrok URL
curl https://YOUR-NGROK-URL.ngrok-free.app/FarmBridgeAI/sms_callback.php

# Should return the same success response
```

### Check Callback Logs
- Open `test_sms_webhook.php` in your browser
- View the "Recent Callback Log" section
- You should see incoming requests from HDEV SMS

## Important Notes

### Ngrok URL Changes
⚠️ **Free ngrok URLs change every time you restart ngrok!**

**Solutions:**
1. **Paid Ngrok Account:** Get a permanent URL that doesn't change
2. **Use a VPS/Cloud Server:** Deploy to a server with a permanent domain
3. **Update HDEV Dashboard:** Update the callback URL each time ngrok restarts

### Callback Log Location
All callbacks are logged to: `sms_callback_log.txt`

You can view this file to debug any issues.

### Testing Without Ngrok
For local testing only (not for production):
```php
// In test_sms.php, you can test sending SMS without callbacks
// Just make sure SMS_ENABLED is true in sms_config.php
```

## Troubleshooting

### Still Getting 404?
1. **Check the URL path:**
   - ✅ Correct: `/FarmBridgeAI/sms_callback.php`
   - ❌ Wrong: `/sms_callback.php`

2. **Verify XAMPP is running:**
   - Open http://localhost/FarmBridgeAI/sms_callback.php
   - Should show success message

3. **Check ngrok forwarding:**
   - Open ngrok web interface: http://127.0.0.1:4040
   - View incoming requests
   - Check for errors

### Ngrok Shows "ERR_NGROK_3200"
This means ngrok cannot reach your local server.

**Solutions:**
1. Restart XAMPP Apache
2. Restart ngrok
3. Check Windows Firewall settings
4. Try: `ngrok http 127.0.0.1:80`

### Callbacks Not Arriving
1. **Check HDEV Dashboard:**
   - Verify callback URL is saved correctly
   - Check if callbacks are enabled

2. **Check Logs:**
   - View `sms_callback_log.txt`
   - Check XAMPP error logs

3. **Test Manually:**
   - Use `test_sms_webhook.php` to send test requests
   - Verify webhook responds correctly

## Next Steps

1. ✅ Webhook endpoint created
2. ⏳ Start ngrok
3. ⏳ Update HDEV SMS dashboard with ngrok URL
4. ⏳ Test sending SMS
5. ⏳ Verify callbacks are received

## Quick Reference

### Webhook Endpoint
```
Local:  http://localhost/FarmBridgeAI/sms_callback.php
Ngrok:  https://YOUR-NGROK-URL.ngrok-free.app/FarmBridgeAI/sms_callback.php
```

### Test Page
```
http://localhost/FarmBridgeAI/test_sms_webhook.php
```

### Callback Log
```
c:\xampp\htdocs\FarmBridgeAI\sms_callback_log.txt
```

### HDEV SMS Dashboard
```
https://sms-api.hdev.rw
```

---

**Note:** Remember that USSD uses Africa's Talking and works with local database. This SMS webhook is specifically for HDEV SMS Gateway callbacks only.

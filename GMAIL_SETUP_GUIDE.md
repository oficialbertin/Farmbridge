# Gmail App Password Setup Guide

## Step 1: Enable 2-Factor Authentication
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Under "Signing in to Google", click **2-Step Verification**
3. Follow the setup process to enable 2FA

## Step 2: Generate App Password
1. Go back to [Google Account Security](https://myaccount.google.com/security)
2. Under "Signing in to Google", click **App passwords**
3. You may need to sign in again
4. Select **Mail** as the app type
5. Select **Other (Custom name)** as the device
6. Enter: **FarmBridge AI**
7. Click **Generate**
8. Copy the 16-character password (format: `xxxx xxxx xxxx xxxx`)

## Step 3: Update Configuration
In your `email_production.php` file, update:
```php
return [
    'smtp_username' => 'oficialbertin@gmail.com',
    'smtp_password' => 'your-16-char-app-password-here',  // Paste the App Password
    'from_email'    => 'oficialbertin@gmail.com',
    'from_name'     => 'FarmBridge AI',
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,
    'smtp_secure'   => 'tls',
];
```

## Step 4: Test the Configuration
1. Go to Admin → Test Email
2. Enter your email address
3. Click "Send Test Email"

## Troubleshooting

### If you get "Authentication failed":
- ✅ Make sure 2FA is enabled
- ✅ Use the App Password (not your regular Gmail password)
- ✅ App Password should be exactly 16 characters
- ✅ No spaces in the App Password in the config file

### If you get "STARTTLS failed":
- The updated SMTP code now handles this automatically
- It will try TLS first, then fallback to non-TLS

### If you get "Connection refused":
- Check if your server allows outbound connections on port 587
- Some hosting providers block SMTP ports

## Alternative: Use Different Email Provider
If Gmail continues to have issues, you can use:
- **Outlook/Hotmail**: smtp-mail.outlook.com:587
- **Yahoo**: smtp.mail.yahoo.com:587
- **Your hosting provider's SMTP**: Check with your hosting support

## Testing Files
- `test_gmail_auth.php` - Detailed authentication test
- `test_email.php` - Simple email sending test

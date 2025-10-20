<?php
// Email Configuration Template
// Copy this to email_production.php and update with your actual credentials

return [
    // Gmail SMTP Configuration (Recommended)
    'smtp_username' => 'your-email@gmail.com',           // Your Gmail address
    'smtp_password' => 'your-16-char-app-password',      // Gmail App Password (not your regular password)
    'from_email' => 'your-email@gmail.com',              // Same as username
    'from_name' => 'FarmBridge AI Rwanda',
    
    // SMTP Server Settings
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    
    // Alternative SMTP Providers (uncomment and configure if needed)
    
    // Outlook/Hotmail
    // 'smtp_host' => 'smtp-mail.outlook.com',
    // 'smtp_port' => 587,
    // 'smtp_secure' => 'tls',
    
    // Yahoo Mail
    // 'smtp_host' => 'smtp.mail.yahoo.com',
    // 'smtp_port' => 587,
    // 'smtp_secure' => 'tls',
    
    // Custom SMTP (if you have your own email server)
    // 'smtp_host' => 'your-smtp-server.com',
    // 'smtp_port' => 587,  // or 465 for SSL
    // 'smtp_secure' => 'tls',  // or 'ssl' for port 465
];

/*
SETUP INSTRUCTIONS:

1. FOR GMAIL (Recommended):
   - Enable 2-Factor Authentication on your Google account
   - Go to Google Account → Security → App passwords
   - Generate a new App Password for "Mail"
   - Use your Gmail address and the App Password above

2. FOR OUTLOOK/HOTMAIL:
   - Enable 2-Factor Authentication
   - Go to Security settings → Advanced security options
   - Create an App Password
   - Use your Outlook email and the App Password

3. FOR YAHOO:
   - Enable 2-Factor Authentication
   - Go to Account Security → Generate App Password
   - Use your Yahoo email and the App Password

4. FOR CUSTOM SMTP:
   - Contact your email provider for SMTP settings
   - Update the host, port, and security settings above

TESTING:
- After setting up, go to Admin → Test Email
- Send a test email to verify the configuration works
- Check the error logs if sending fails
*/
?>

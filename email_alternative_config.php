<?php
// Alternative email configuration for servers with TLS issues
// Copy this to email_production.php if the main config doesn't work

return [
    'smtp_username' => 'oficialbertin@gmail.com',
    'smtp_password' => 'ltbk zbpq mgsx asxw',  // App password with spaces
    'from_email'    => 'oficialbertin@gmail.com',
    'from_name'     => 'FarmBridge AI',
    
    // Option 1: Try SSL instead of TLS (port 465)
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 465,
    'smtp_secure'   => 'ssl',
    
    // Option 2: Try without encryption (uncomment if SSL fails)
    // 'smtp_host'     => 'smtp.gmail.com',
    // 'smtp_port'     => 25,
    // 'smtp_secure'   => 'none',
    
    // Option 3: Try different Gmail server (uncomment if needed)
    // 'smtp_host'     => 'smtp-relay.gmail.com',
    // 'smtp_port'     => 587,
    // 'smtp_secure'   => 'tls',
];
?>

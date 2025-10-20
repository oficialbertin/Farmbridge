<?php
// Production configuration file
// This file will be used on the production server

// Database configuration
$db_config = include 'db_production.php';

// Email configuration  
$email_config = include 'email_production.php';

// Site configuration
$site_config = [
    'site_url' => 'https://www.farmbridge.rw',
    'site_name' => 'FarmBridge AI Rwanda',
    'debug_mode' => false, // Set to false for production
    'timezone' => 'Africa/Kigali',
    'upload_path' => '/uploads/',
    'max_file_size' => 5 * 1024 * 1024, // 5MB
];

// Set timezone
date_default_timezone_set($site_config['timezone']);

// Error reporting (disable in production)
if (!$site_config['debug_mode']) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

return [
    'database' => $db_config,
    'email' => $email_config,
    'site' => $site_config
];


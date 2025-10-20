<?php
/**
 * FarmBridge AI USSD Application Configuration Example
 * Copy this file to config.php and update with your actual values
 */

return [
    // Database Configuration
    'database' => [
        'host' => 'localhost',
        'name' => 'farmbridge_ai',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    ],

    // Africa's Talking Configuration
    'africas_talking' => [
        'username' => 'sandbox',
        'api_key' => 'atsk_dd62d69229f596a5080624a853ca4d81c98610b6ba9f0d50d7cb7471a130f911ef3b9997',
        'company' => 'FarmBridge AI',
        'short_code' => 4627
    ],

    // Application Configuration
    'app' => [
        'env' => 'development',
        'debug' => true,
        'timezone' => 'Africa/Kigali',
        'name' => 'FarmBridge AI USSD',
        'version' => '1.0.0'
    ],

    // Session Configuration
    'session' => [
        'timeout' => 300, // 5 minutes
        'max_retry_attempts' => 3,
        'cleanup_probability' => 1 // 1% chance
    ],

    // SMS Configuration
    'sms' => [
        'enabled' => true,
        'from' => 'FarmBridge AI',
        'rate_limit' => 100, // per hour
        'retry_attempts' => 3
    ],

    // Logging Configuration
    'logging' => [
        'level' => 'info',
        'file' => 'ussd.log',
        'max_size' => '10MB',
        'max_files' => 5
    ],

    // Security Configuration
    'security' => [
        'encryption_key' => 'your_encryption_key_here',
        'jwt_secret' => 'your_jwt_secret_here',
        'rate_limiting' => true,
        'input_validation' => true
    ],

    // Feature Flags
    'features' => [
        'market_prices' => true,
        'farming_tips' => true,
        'sms_notifications' => true,
        'price_alerts' => true,
        'user_registration' => true,
        'product_listing' => true,
        'order_management' => true,
        'dispute_resolution' => true
    ],

    // Rate Limiting
    'rate_limiting' => [
        'requests' => 100,
        'window' => 3600, // 1 hour
        'enabled' => true
    ],

    // External APIs
    'external_apis' => [
        'weather' => [
            'api_key' => 'your_weather_api_key',
            'api_url' => 'https://api.openweathermap.org/data/2.5',
            'enabled' => false
        ],
        'market_data' => [
            'api_key' => 'your_market_data_api_key',
            'api_url' => 'https://api.marketdata.com',
            'enabled' => false
        ]
    ],

    // Monitoring
    'monitoring' => [
        'enabled' => true,
        'endpoint' => 'https://monitoring.farmbridgeai.com',
        'metrics' => [
            'ussd_sessions' => true,
            'sms_delivery' => true,
            'error_rates' => true,
            'response_times' => true
        ]
    ],

    // Backup Configuration
    'backup' => [
        'enabled' => true,
        'frequency' => 'daily',
        'retention_days' => 30,
        'storage' => 'local', // local, s3, ftp
        'encryption' => true
    ],

    // USSD Configuration
    'ussd' => [
        'service_code' => '*384*123#',
        'max_menu_levels' => 10,
        'input_validation' => true,
        'session_cleanup' => true,
        'error_handling' => true
    ],

    // Language Configuration
    'languages' => [
        'default' => 'en',
        'supported' => ['en', 'rw'],
        'fallback' => 'en'
    ],

    // Location Configuration
    'locations' => [
        'provinces' => [
            'KIGALI' => 'Kigali',
            'NORTHERN' => 'Northern Province',
            'SOUTHERN' => 'Southern Province',
            'EASTERN' => 'Eastern Province',
            'WESTERN' => 'Western Province'
        ],
        'districts' => [
            'KIGALI' => ['Nyarugenge', 'Gasabo', 'Kicukiro'],
            'NORTHERN' => ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
            'SOUTHERN' => ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
            'EASTERN' => ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
            'WESTERN' => ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro']
        ]
    ],

    // Crop Configuration
    'crops' => [
        'MAIZE' => 'Maize',
        'BEANS' => 'Beans',
        'POTATOES' => 'Potatoes',
        'RICE' => 'Rice',
        'WHEAT' => 'Wheat',
        'SORGHUM' => 'Sorghum',
        'CASSAVA' => 'Cassava',
        'SWEET_POTATOES' => 'Sweet Potatoes',
        'TOMATOES' => 'Tomatoes',
        'ONIONS' => 'Onions',
        'CARROTS' => 'Carrots',
        'CABBAGE' => 'Cabbage'
    ],

    // Price Configuration
    'pricing' => [
        'min_price' => 100,
        'max_price' => 1000000,
        'currency' => 'RWF',
        'decimal_places' => 0
    ],

    // Quantity Configuration
    'quantity' => [
        'min_quantity' => 1,
        'max_quantity' => 10000,
        'unit' => 'kg',
        'decimal_places' => 2
    ],

    // Order Configuration
    'orders' => [
        'statuses' => [
            'PENDING' => 'pending',
            'CONFIRMED' => 'confirmed',
            'DELIVERED' => 'delivered',
            'CANCELLED' => 'cancelled'
        ],
        'auto_confirm' => false,
        'confirmation_timeout' => 3600 // 1 hour
    ],

    // Payment Configuration
    'payments' => [
        'statuses' => [
            'PENDING' => 'pending',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed'
        ],
        'methods' => ['mobile_money', 'bank_transfer', 'cash'],
        'default_method' => 'mobile_money'
    ],

    // Farming Tips Configuration
    'farming_tips' => [
        'categories' => [
            'SEASONAL' => 'Seasonal Tips',
            'PEST_CONTROL' => 'Pest Control',
            'SOIL_MANAGEMENT' => 'Soil Management',
            'WATER_MANAGEMENT' => 'Water Management',
            'CROP_ROTATION' => 'Crop Rotation',
            'FERTILIZATION' => 'Fertilization'
        ],
        'daily_tips' => true,
        'seasonal_tips' => true,
        'weather_based_tips' => false
    ],

    // Market Prices Configuration
    'market_prices' => [
        'update_frequency' => 'daily',
        'price_alerts' => true,
        'trend_analysis' => true,
        'forecasting' => false,
        'volatility_tracking' => true
    ],

    // Notification Configuration
    'notifications' => [
        'sms' => [
            'enabled' => true,
            'rate_limit' => 100,
            'retry_attempts' => 3
        ],
        'email' => [
            'enabled' => false,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => ''
        ],
        'push' => [
            'enabled' => false,
            'api_key' => '',
            'api_secret' => ''
        ]
    ],

    // Cache Configuration
    'cache' => [
        'enabled' => true,
        'driver' => 'file', // file, redis, memcached
        'ttl' => 3600, // 1 hour
        'prefix' => 'farmbridge_ussd_'
    ],

    // Queue Configuration
    'queue' => [
        'enabled' => false,
        'driver' => 'database', // database, redis, sqs
        'retry_attempts' => 3,
        'retry_delay' => 60
    ]
];
?>

<?php
class Util {
    // Navigation options
    static $GO_BACK = "98";
    static $GO_TO_MAIN_MENU = "99";
    
    // Database configuration - using FarmBridge AI database
    static $host = 'localhost';
    static $db = 'farmbridge'; 
    static $user = 'root';          
    static $pass = '';   
    
    // SMS configuration - Africa's Talking
    static $username = "sandbox";
    static $apikey = "atsk_dd62d69229f596a5080624a853ca4d81c98610b6ba9f0d50d7cb7471a130f911ef3b9997"; // Replace with your actual API key
    static $Company = "FarmBridge AI";
    static $short_code = 4627;
    
    // User roles
    static $USER_ROLES = [
        'FARMER' => 'farmer',
        'BUYER' => 'buyer'
    ];
    
    // Supported languages
    static $LANGUAGES = [
        'ENGLISH' => 'en',
        'KINYARWANDA' => 'rw'
    ];
    
    // Common crops for market prices
    static $COMMON_CROPS = [
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
    ];
    
    // Provinces in Rwanda
    static $PROVINCES = [
        'KIGALI' => 'Kigali',
        'NORTHERN' => 'Northern Province',
        'SOUTHERN' => 'Southern Province', 
        'EASTERN' => 'Eastern Province',
        'WESTERN' => 'Western Province'
    ];
    
    // Districts mapping
    static $DISTRICTS = [
        'KIGALI' => ['Nyarugenge', 'Gasabo', 'Kicukiro'],
        'NORTHERN' => ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
        'SOUTHERN' => ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
        'EASTERN' => ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
        'WESTERN' => ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro']
    ];
    
    // Farming tips categories
    static $FARMING_TIPS_CATEGORIES = [
        'SEASONAL' => 'Seasonal Tips',
        'PEST_CONTROL' => 'Pest Control',
        'SOIL_MANAGEMENT' => 'Soil Management',
        'WATER_MANAGEMENT' => 'Water Management',
        'CROP_ROTATION' => 'Crop Rotation',
        'FERTILIZATION' => 'Fertilization'
    ];
    
    // Order status
    static $ORDER_STATUS = [
        'PENDING' => 'pending',
        'CONFIRMED' => 'confirmed',
        'DELIVERED' => 'delivered',
        'CANCELLED' => 'cancelled'
    ];
    
    // Payment status
    static $PAYMENT_STATUS = [
        'PENDING' => 'pending',
        'COMPLETED' => 'completed',
        'FAILED' => 'failed'
    ];
    
    // USSD Session timeout (in seconds)
    static $SESSION_TIMEOUT = 300; // 5 minutes
    
    // Maximum retry attempts
    static $MAX_RETRY_ATTEMPTS = 3;
    
    // Default currency
    static $CURRENCY = 'RWF';
    
    // Price ranges for validation
    static $MIN_PRICE = 100;
    static $MAX_PRICE = 1000000;
    
    // Quantity validation
    static $MIN_QUANTITY = 1;
    static $MAX_QUANTITY = 10000;
    
    // Phone number validation (Rwanda)
    static function isValidPhoneNumber($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it starts with +250 or 250 and has 9 digits after
        if (preg_match('/^(\+?250|0)?(7[0-9]{8})$/', $phone)) {
            return true;
        }
        
        return false;
    }
    
    // Format phone number for database storage
    static function formatPhoneNumber($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strpos($phone, '250') === 0) {
            return $phone;
        } elseif (strpos($phone, '0') === 0) {
            return '250' . substr($phone, 1);
        } else {
            return '250' . $phone;
        }
    }
    
    // Format price for display
    static function formatPrice($price) {
        return number_format($price) . ' ' . self::$CURRENCY;
    }
    
    // Validate email format
    static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Get current timestamp
    static function getCurrentTimestamp() {
        return date('Y-m-d H:i:s');
    }
    
    // Generate unique session ID
    static function generateSessionId() {
        return uniqid('ussd_', true);
    }
    
    // Sanitize input
    static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Get language text
    static function getLanguageText($key, $language = 'en') {
        $texts = [
            'en' => [
                'welcome' => 'Welcome to FarmBridge AI',
                'main_menu' => 'Main Menu',
                'invalid_option' => 'Invalid option. Please try again.',
                'session_expired' => 'Session expired. Please start again.',
                'error_occurred' => 'An error occurred. Please try again.',
                'registration_success' => 'Registration successful!',
                'login_success' => 'Login successful!',
                'goodbye' => 'Thank you for using FarmBridge AI. Goodbye!',
                'register_title' => 'Register with FarmBridge AI',
                'choose_role' => 'Choose your role:',
                'farmer' => 'Farmer',
                'buyer' => 'Buyer',
                'back' => 'Back',
                'registration' => 'Registration',
                'name' => 'Name',
                'enter_name' => 'Enter your full name:',
                'select_province' => 'Select your province:',
                'province' => 'Province',
                'select_district' => 'Select your district:',
                'welcome_message' => 'Welcome to FarmBridge AI,',
                'access_services' => 'You can now access all farming and trading services.',
                'dial_again' => 'Dial the code again to start using the platform.',
                'registration_failed' => 'Registration failed. Please try again later.'
            ],
            'rw' => [
                'welcome' => 'Murakaza neza muri FarmBridge AI',
                'main_menu' => 'Menu Nyamukuru',
                'invalid_option' => 'Ihitamo ridahagije. Ongera ugerageze.',
                'session_expired' => 'Igihe cyo gukoresha cyarangiye. Tangira nanone.',
                'error_occurred' => 'Ikosa ryabaye. Ongera ugerageze.',
                'registration_success' => 'Kwiyandikisha byagenze neza!',
                'login_success' => 'Kwinjira byagenze neza!',
                'goodbye' => 'Murakoze gukoresha FarmBridge AI. Murabeho!',
                'register_title' => 'Kwiyandikisha muri FarmBridge AI',
                'choose_role' => 'Hitamo uruhare rwawe:',
                'farmer' => 'Umuhinzi',
                'buyer' => 'Umuguzi',
                'back' => 'Subira inyuma',
                'registration' => 'Kwiyandikisha',
                'name' => 'Izina',
                'enter_name' => 'Andika izina ryawe ryuzuye:',
                'select_province' => 'Hitamo intara yawe:',
                'province' => 'Intara',
                'select_district' => 'Hitamo akarere kawe:',
                'welcome_message' => 'Murakaza neza muri FarmBridge AI,',
                'access_services' => 'Ubu urashobora kugera ku serivisi zose z\'ubuhinzi n\'ubucuruzi.',
                'dial_again' => 'Hamagara kode nanone kugira ngo utangire gukoresha sisiteme.',
                'registration_failed' => 'Kwiyandikisha byanze. Ongera ugerageze.'
            ]
        ];
        
        return isset($texts[$language][$key]) ? $texts[$language][$key] : $texts['en'][$key];
    }
}
?>

<?php
require_once 'util.php';
require_once 'database.php';
require_once 'sms.php';

class Menu {
    private $sessionId;
    private $phoneNumber;
    private $text;
    private $db;
    private $sms;
    private $user;
    private $language;
    private $sessionData;

    public function __construct($sessionId, $phoneNumber, $text) {
        $this->sessionId = $sessionId;
        $this->phoneNumber = $phoneNumber;
        $this->text = $text;
        $this->db = new Database();
        $this->sms = new Sms();
        
        // Load or create session
        $this->loadSession();
        
        // Get user if exists
        $this->user = $this->db->getUserByPhone($phoneNumber);
        
        // Set default language
        $this->language = isset($this->sessionData['language']) ? $this->sessionData['language'] : 'en';
    }

    private function loadSession() {
        $session = $this->db->getSession($this->sessionId);
        if ($session) {
            $this->sessionData = json_decode($session['data'], true) ?: [];
        } else {
            $this->sessionData = [];
            $this->db->createSession($this->sessionId, $this->phoneNumber, json_encode($this->sessionData));
        }
    }

    private function saveSession() {
        $this->db->updateSession($this->sessionId, json_encode($this->sessionData));
    }

    public function processMenu() {
        try {
            // Parse the text input
            $textArray = explode('*', $this->text);
            $level = count($textArray);

            // Treat empty input as first screen
            if (trim($this->text) === '') {
                return $this->showWelcomeMenu();
            }

            // Route based on level and input
            switch ($level) {
                case 0:
                    return $this->showWelcomeMenu();
                
                case 1:
                    return $this->processLevel1($textArray[0]);
                
                case 2:
                    return $this->processLevel2($textArray[0], $textArray[1]);
                
                case 3:
                    return $this->processLevel3($textArray[0], $textArray[1], $textArray[2]);
                
                case 4:
                    return $this->processLevel4($textArray[0], $textArray[1], $textArray[2], $textArray[3]);
                
                case 5:
                    return $this->processLevel5($textArray[0], $textArray[1], $textArray[2], $textArray[3], $textArray[4]);
                
                case 6:
                    return $this->processLevel6($textArray[0], $textArray[1], $textArray[2], $textArray[3], $textArray[4], $textArray[5]);

                default:
                    return $this->showMainMenu();
            }
        } catch (Exception $e) {
            error_log("Menu processing error: " . $e->getMessage());
            return "END " . Util::getLanguageText('error_occurred', $this->language);
        }
    }

    private function showWelcomeMenu() {
        $menu = "CON " . Util::getLanguageText('welcome', $this->language) . "\n";
        $menu .= "Choose your language:\n";
        $menu .= "1. English\n";
        $menu .= "2. Kinyarwanda\n";
        
        $this->sessionData['level'] = 'language_selection';
        $this->saveSession();
        
        return $menu;
    }

    private function processLevel1($input) {
        switch ($this->sessionData['level']) {
            case 'language_selection':
                return $this->handleLanguageSelection($input);
            default:
                return $this->showMainMenu();
        }
    }

    private function handleLanguageSelection($input) {
        switch ($input) {
            case '1':
                $this->language = 'en';
                $this->sessionData['language'] = 'en';
                break;
            case '2':
                $this->language = 'rw';
                $this->sessionData['language'] = 'rw';
                break;
            default:
                return $this->showWelcomeMenu();
        }
        
        $this->saveSession();
        return $this->showMainMenu();
    }

    private function showMainMenu() {
        if (!$this->user) {
            return $this->showRegistrationMenu();
        }

        $menu = "CON " . Util::getLanguageText('main_menu', $this->language) . "\n";
        $menu .= "Hello " . $this->user['name'] . "!\n\n";
        
        if ($this->user['role'] === 'farmer') {
            $menu .= "1. List Product\n";
            $menu .= "2. My Products\n";
            $menu .= "3. My Orders\n";
            $menu .= "4. Market Prices\n";
            $menu .= "5. Farming Tips\n";
            $menu .= "6. Profile\n";
            $menu .= "7. Help\n";
        } else {
            $menu .= "1. Browse Products\n";
            $menu .= "2. My Orders\n";
            $menu .= "3. Market Prices\n";
            $menu .= "4. Farming Tips\n";
            $menu .= "5. Profile\n";
            $menu .= "6. Help\n";
        }
        
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['level'] = 'main_menu';
        $this->saveSession();
        
        return $menu;
    }

    private function showRegistrationMenu() {
        $menu = "CON " . Util::getLanguageText('register_title', $this->language) . "\n";
        $menu .= Util::getLanguageText('choose_role', $this->language) . "\n";
        $menu .= "1. " . Util::getLanguageText('farmer', $this->language) . "\n";
        $menu .= "2. " . Util::getLanguageText('buyer', $this->language) . "\n";
        $menu .= "\n" . Util::$GO_BACK . ". " . Util::getLanguageText('back', $this->language) . "\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". " . Util::getLanguageText('main_menu', $this->language);
        
        $this->sessionData['level'] = 'role_selection';
        $this->saveSession();
        
        return $menu;
    }

    private function processLevel2($input1, $input2) {
        switch ($this->sessionData['level']) {
            case 'role_selection':
                return $this->handleRoleSelection($input1, $input2);
            case 'main_menu':
                return $this->handleMainMenuSelection($input1, $input2);
            default:
                return $this->showMainMenu();
        }
    }

    private function handleRoleSelection($input1, $input2) {
        if ($input2 === Util::$GO_BACK) {
            return $this->showWelcomeMenu();
        }
        
        $role = '';
        switch ($input1) {
            case '1':
                $role = 'farmer';
                break;
            case '2':
                $role = 'buyer';
                break;
            default:
                return $this->showRegistrationMenu();
        }
        
        $this->sessionData['registration_role'] = $role;
        $this->sessionData['level'] = 'registration_name';
        $this->saveSession();
        
        $menu = "CON Registration - " . ucfirst($role) . "\n";
        $menu .= "Enter your full name:\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        return $menu;
    }

    private function handleMainMenuSelection($input1, $input2) {
        if ($input2 === Util::$GO_BACK) {
            return $this->showWelcomeMenu();
        }
        
        switch ($input1) {
            case '1':
                if ($this->user['role'] === 'farmer') {
                    return $this->showListProductMenu();
                } else {
                    return $this->showBrowseProductsMenu();
                }
            case '2':
                if ($this->user['role'] === 'farmer') {
                    return $this->showMyProductsMenu();
                } else {
                    return $this->showMyOrdersMenu();
                }
            case '3':
                if ($this->user['role'] === 'farmer') {
                    return $this->showMyOrdersMenu();
                } else {
                    return $this->showMarketPricesMenu();
                }
            case '4':
                if ($this->user['role'] === 'farmer') {
                    return $this->showMarketPricesMenu();
                } else {
                    return $this->showFarmingTipsMenu();
                }
            case '5':
                if ($this->user['role'] === 'farmer') {
                    return $this->showFarmingTipsMenu();
                } else {
                    return $this->showProfileMenu();
                }
            case '6':
                if ($this->user['role'] === 'farmer') {
                    return $this->showProfileMenu();
                } else {
                    return $this->showHelpMenu();
                }
            case '7':
                if ($this->user['role'] === 'farmer') {
                    return $this->showHelpMenu();
                }
            default:
                return $this->showMainMenu();
        }
    }

    private function processLevel3($input1, $input2, $input3) {
        switch ($this->sessionData['level']) {
            case 'registration_name':
                return $this->handleRegistrationName($input1, $input2, $input3);
            // listing flow handled via explicit levels below
            case 'list_product_quantity':
                return $this->handleListProductQuantity($input1, $input2, $input3);
            case 'browse_product_details':
                return $this->handleBrowseProductDetails($input1, $input2, $input3);
            case 'manage_product':
                return $this->handleManageProduct($input1, $input2, $input3);
            case 'order_details':
                return $this->handleOrderDetails($input1, $input2, $input3);
            case 'farming_tip_details':
                return $this->handleFarmingTipDetails($input1, $input2, $input3);
            case 'profile_options':
                return $this->handleProfileOptions($input1, $input2, $input3);
            case 'help_menu':
                return $this->handleHelpMenu($input1, $input2, $input3);
            default:
                return $this->showMainMenu();
        }
    }

    private function handleRegistrationName($input1, $input2, $input3) {
        if ($input3 === Util::$GO_BACK) {
            return $this->showRegistrationMenu();
        }
        
        $name = trim($input3);
        if (empty($name)) {
            $menu = "CON Registration - " . ucfirst($this->sessionData['registration_role']) . "\n";
            $menu .= "Enter your full name:\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }
        
        $this->sessionData['registration_name'] = $name;
        $this->sessionData['level'] = 'registration_province';
        $this->saveSession();
        
        $menu = "CON " . Util::getLanguageText('registration', $this->language) . " - " . ucfirst($this->sessionData['registration_role']) . "\n";
        $menu .= Util::getLanguageText('name', $this->language) . ": $name\n";
        $menu .= Util::getLanguageText('select_province', $this->language) . "\n";
        
        $provinces = Util::$PROVINCES;
        $i = 1;
        foreach ($provinces as $key => $province) {
            $menu .= "$i. $province\n";
            $i++;
        }
        
        $menu .= "\n" . Util::$GO_BACK . ". " . Util::getLanguageText('back', $this->language) . "\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". " . Util::getLanguageText('main_menu', $this->language);
        
        return $menu;
    }

    private function processLevel4($input1, $input2, $input3, $input4) {
        switch ($this->sessionData['level']) {
            case 'registration_province':
                return $this->handleRegistrationProvince($input1, $input2, $input3, $input4);
            case 'list_product_name':
                return $this->handleListProductName($input1, $input2, $input3, $input4);
            default:
                return $this->showMainMenu();
        }
    }

    private function handleRegistrationProvince($input1, $input2, $input3, $input4) {
        if ($input4 === Util::$GO_BACK) {
            $this->sessionData['level'] = 'registration_name';
            $this->saveSession();
            
            $menu = "CON " . Util::getLanguageText('registration', $this->language) . " - " . ucfirst($this->sessionData['registration_role']) . "\n";
            $menu .= Util::getLanguageText('enter_name', $this->language) . "\n";
            $menu .= "\n" . Util::$GO_BACK . ". " . Util::getLanguageText('back', $this->language) . "\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". " . Util::getLanguageText('main_menu', $this->language);
            return $menu;
        }
        
        $provinceKeys = array_keys(Util::$PROVINCES);
        $provinceIndex = intval($input4) - 1;
        
        if ($provinceIndex < 0 || $provinceIndex >= count($provinceKeys)) {
            $menu = "CON " . Util::getLanguageText('registration', $this->language) . " - " . ucfirst($this->sessionData['registration_role']) . "\n";
            $menu .= Util::getLanguageText('name', $this->language) . ": " . $this->sessionData['registration_name'] . "\n";
            $menu .= Util::getLanguageText('select_province', $this->language) . "\n";
            
            $provinces = Util::$PROVINCES;
            $i = 1;
            foreach ($provinces as $key => $province) {
                $menu .= "$i. $province\n";
                $i++;
            }
            
            $menu .= "\n" . Util::$GO_BACK . ". " . Util::getLanguageText('back', $this->language) . "\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". " . Util::getLanguageText('main_menu', $this->language);
            return $menu;
        }
        
        $selectedProvince = $provinceKeys[$provinceIndex];
        $this->sessionData['registration_province'] = $selectedProvince;
        $this->sessionData['level'] = 'registration_district';
        $this->saveSession();
        
        $menu = "CON " . Util::getLanguageText('registration', $this->language) . " - " . ucfirst($this->sessionData['registration_role']) . "\n";
        $menu .= Util::getLanguageText('name', $this->language) . ": " . $this->sessionData['registration_name'] . "\n";
        $menu .= Util::getLanguageText('province', $this->language) . ": " . Util::$PROVINCES[$selectedProvince] . "\n";
        $menu .= Util::getLanguageText('select_district', $this->language) . "\n";
        
        $districts = Util::$DISTRICTS[$selectedProvince];
        $i = 1;
        foreach ($districts as $district) {
            $menu .= "$i. $district\n";
            $i++;
        }
        
        $menu .= "\n" . Util::$GO_BACK . ". " . Util::getLanguageText('back', $this->language) . "\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". " . Util::getLanguageText('main_menu', $this->language);
        
        return $menu;
    }

    private function processLevel5($input1, $input2, $input3, $input4, $input5) {
        switch ($this->sessionData['level']) {
            case 'registration_district':
                return $this->handleRegistrationDistrict($input1, $input2, $input3, $input4, $input5);
            default:
                return $this->showMainMenu();
        }
    }

    private function handleRegistrationDistrict($input1, $input2, $input3, $input4, $input5) {
        if ($input5 === Util::$GO_BACK) {
            $this->sessionData['level'] = 'registration_province';
            $this->saveSession();
            
            $menu = "CON " . Util::getLanguageText('registration', $this->language) . " - " . ucfirst($this->sessionData['registration_role']) . "\n";
            $menu .= Util::getLanguageText('name', $this->language) . ": " . $this->sessionData['registration_name'] . "\n";
            $menu .= Util::getLanguageText('select_province', $this->language) . "\n";
            
            $provinces = Util::$PROVINCES;
            $i = 1;
            foreach ($provinces as $key => $province) {
                $menu .= "$i. $province\n";
                $i++;
            }
            
            $menu .= "\n" . Util::$GO_BACK . ". " . Util::getLanguageText('back', $this->language) . "\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". " . Util::getLanguageText('main_menu', $this->language);
            return $menu;
        }
        
        $districts = Util::$DISTRICTS[$this->sessionData['registration_province']];
        $districtIndex = intval($input5) - 1;
        
        if ($districtIndex < 0 || $districtIndex >= count($districts)) {
            $menu = "CON " . Util::getLanguageText('registration', $this->language) . " - " . ucfirst($this->sessionData['registration_role']) . "\n";
            $menu .= Util::getLanguageText('name', $this->language) . ": " . $this->sessionData['registration_name'] . "\n";
            $menu .= Util::getLanguageText('province', $this->language) . ": " . Util::$PROVINCES[$this->sessionData['registration_province']] . "\n";
            $menu .= Util::getLanguageText('select_district', $this->language) . "\n";
            
            $i = 1;
            foreach ($districts as $district) {
                $menu .= "$i. $district\n";
                $i++;
            }
            
            $menu .= "\n" . Util::$GO_BACK . ". " . Util::getLanguageText('back', $this->language) . "\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". " . Util::getLanguageText('main_menu', $this->language);
            return $menu;
        }
        
        $selectedDistrict = $districts[$districtIndex];
        return $this->completeRegistration($selectedDistrict);
    }

    // Product listing methods
    private function showListProductMenu() {
        $menu = "CON List New Product\n";
        $menu .= "Enter product name:\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['level'] = 'list_product_name';
        $this->saveSession();
        
        return $menu;
    }

    private function handleListProductName($input1, $input2, $input3, $input4) {
        if ($input4 === Util::$GO_BACK) {
            return $this->showMainMenu();
        }
        
        $productName = trim($input4);
        if (empty($productName)) {
            $menu = "CON List New Product\n";
            $menu .= "Enter product name:\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }
        
        $this->sessionData['product_name'] = $productName;
        $this->sessionData['level'] = 'list_product_quantity';
        $this->saveSession();
        
        $menu = "CON List New Product\n";
        $menu .= "Product: $productName\n";
        $menu .= "Enter quantity (in kg):\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        return $menu;
    }

    private function handleListProductQuantity($input1, $input2, $input3) {
        if ($input3 === Util::$GO_BACK) {
            return $this->showListProductMenu();
        }
        $quantity = (int)trim($input3);
        if ($quantity < Util::$MIN_QUANTITY || $quantity > Util::$MAX_QUANTITY) {
            $menu = "CON List New Product\n";
            $menu .= "Product: " . ($this->sessionData['product_name'] ?? '') . "\n";
            $menu .= "Enter valid quantity (" . Util::$MIN_QUANTITY . "-" . Util::$MAX_QUANTITY . "):\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }
        $this->sessionData['product_quantity'] = $quantity;
        $this->sessionData['level'] = 'list_product_price';
        $this->saveSession();

        $menu = "CON List New Product\n";
        $menu .= "Product: " . $this->sessionData['product_name'] . "\n";
        $menu .= "Quantity: " . $this->sessionData['product_quantity'] . " kg\n";
        $menu .= "Enter price per kg (RWF):\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        return $menu;
    }

    private function handleListProductPrice($input1, $input2, $input3, $input4, $input5, $input6) {
        // This handler will be invoked from processLevel6
        if ($input6 === Util::$GO_BACK) {
            // go back to quantity entry
            $this->sessionData['level'] = 'list_product_quantity';
            $this->saveSession();
            $menu = "CON List New Product\n";
            $menu .= "Product: " . ($this->sessionData['product_name'] ?? '') . "\n";
            $menu .= "Enter quantity (in kg):\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }
        $price = (int)trim($input6);
        if ($price < Util::$MIN_PRICE || $price > Util::$MAX_PRICE) {
            $menu = "CON List New Product\n";
            $menu .= "Product: " . ($this->sessionData['product_name'] ?? '') . "\n";
            $menu .= "Quantity: " . ($this->sessionData['product_quantity'] ?? '') . " kg\n";
            $menu .= "Enter valid price per kg (" . Util::$MIN_PRICE . "-" . Util::$MAX_PRICE . "):\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }

        // Persist product
        $name = $this->sessionData['product_name'] ?? '';
        $quantity = (int)($this->sessionData['product_quantity'] ?? 0);
        $category = 'General';
        $description = '';
        $image = null;
        $farmerId = $this->user['id'] ?? null;
        if (!$farmerId) {
            return "END " . Util::getLanguageText('error_occurred', $this->language);
        }
        $createdId = $this->db->addProduct($farmerId, $name, $description, $quantity, $price, $category, $image);
        if ($createdId) {
            // Clear listing session keys
            unset($this->sessionData['product_name'], $this->sessionData['product_quantity']);
            $this->saveSession();
            return "END Product listed successfully.";
        }
        return "END Failed to list product. Please try again later.";
    }

    // Add a handler for level 6 inputs
    private function processLevel6($input1, $input2, $input3, $input4, $input5, $input6) {
        switch ($this->sessionData['level']) {
            case 'list_product_price':
                return $this->handleListProductPrice($input1, $input2, $input3, $input4, $input5, $input6);
            default:
                return $this->showMainMenu();
        }
    }

    private function handleBrowseProductDetails($input1, $input2, $input3) {
        if ($input3 === Util::$GO_BACK) {
            return $this->showBrowseProductsMenu();
        }
        $selection = (int)trim($input3);
        $products = $this->sessionData['browse_products'] ?? [];
        if ($selection < 1 || $selection > count($products)) {
            // Re-show list on invalid selection
            return $this->showBrowseProductsMenu();
        }
        $product = $products[$selection - 1];
        $menu = "CON Product Details\n";
        $menu .= "Name: " . $product['name'] . "\n";
        $menu .= "Quantity: " . $product['quantity'] . " kg\n";
        $menu .= "Price: " . Util::formatPrice($product['price']) . "\n";
        $menu .= "Farmer: " . ($product['farmer_name'] ?? '') . "\n";
        if (!empty($product['farmer_phone'])) {
            $menu .= "Contact: " . $product['farmer_phone'] . "\n";
        }
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";

        // Keep level to allow further navigation
        $this->sessionData['level'] = 'browse_product_details';
        $this->saveSession();
        return $menu;
    }

    // Browse products methods
    private function showBrowseProductsMenu() {
        $products = $this->db->getAllAvailableProducts(10);
        
        if (!$products || empty($products)) {
            $menu = "CON Browse Products\n";
            $menu .= "No products available at the moment.\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }
        
        $menu = "CON Browse Products\n";
        $menu .= "Available products:\n\n";
        
        $i = 1;
        foreach ($products as $product) {
            $menu .= "$i. " . $product['name'] . "\n";
            $menu .= "   Qty: " . $product['quantity'] . "kg\n";
            $menu .= "   Price: " . Util::formatPrice($product['price']) . "\n";
            $menu .= "   Farmer: " . $product['farmer_name'] . "\n\n";
            $i++;
        }
        
        $menu .= "Select product number to view details:\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['browse_products'] = $products;
        $this->sessionData['level'] = 'browse_product_details';
        $this->saveSession();
        
        return $menu;
    }

    // Market prices methods
    private function showMarketPricesMenu() {
        $menu = "CON Market Prices\n";
        $menu .= "Select crop:\n";
        
        $crops = Util::$COMMON_CROPS;
        $i = 1;
        foreach ($crops as $key => $crop) {
            $menu .= "$i. $crop\n";
            $i++;
        }
        
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['level'] = 'market_price_selection';
        $this->saveSession();
        
        return $menu;
    }

    // Farming tips methods
    private function showFarmingTipsMenu() {
        $tips = $this->db->getFarmingTips(null, 5);
        
        if (!$tips || empty($tips)) {
            $menu = "CON Farming Tips\n";
            $menu .= "No tips available at the moment.\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }
        
        $menu = "CON Farming Tips\n";
        $menu .= "Latest tips:\n\n";
        
        $i = 1;
        foreach ($tips as $tip) {
            $menu .= "$i. " . $tip['title'] . "\n";
            $menu .= "   " . substr($tip['content'], 0, 50) . "...\n\n";
            $i++;
        }
        
        $menu .= "Select tip number to view full content:\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['farming_tips'] = $tips;
        $this->sessionData['level'] = 'farming_tip_details';
        $this->saveSession();
        
        return $menu;
    }

    // Profile methods
    private function showProfileMenu() {
        $menu = "CON My Profile\n";
        $menu .= "Name: " . $this->user['name'] . "\n";
        $menu .= "Role: " . ucfirst($this->user['role']) . "\n";
        $menu .= "Phone: " . $this->user['phone'] . "\n";
        $menu .= "Email: " . $this->user['email'] . "\n";
        $menu .= "Province: " . $this->user['province'] . "\n";
        $menu .= "District: " . $this->user['district'] . "\n";
        $menu .= "Sector: " . $this->user['sector'] . "\n";
        $menu .= "\n1. Update Profile\n";
        $menu .= "2. View Statistics\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['level'] = 'profile_options';
        $this->saveSession();
        
        return $menu;
    }

    // Help methods
    private function showHelpMenu() {
        $menu = "CON Help & Support\n";
        $menu .= "FarmBridge AI USSD Help:\n\n";
        $menu .= "1. Registration: Complete your profile to access all features\n";
        $menu .= "2. Products: Farmers can list products, buyers can browse\n";
        $menu .= "3. Orders: Track your orders and transactions\n";
        $menu .= "4. Market Prices: View current crop prices\n";
        $menu .= "5. Farming Tips: Get daily agricultural advice\n\n";
        $menu .= "For technical support, contact:\n";
        $menu .= "Phone: +250 788 123 456\n";
        $menu .= "Email: support@farmbridgeai.com\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['level'] = 'help_menu';
        $this->saveSession();
        
        return $menu;
    }

    // My products methods
    private function showMyProductsMenu() {
        $products = $this->db->getProductsByFarmer($this->user['id']);
        
        if (!$products || empty($products)) {
            $menu = "CON My Products\n";
            $menu .= "You haven't listed any products yet.\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }
        
        $menu = "CON My Products\n";
        $menu .= "Your listed products:\n\n";
        
        $i = 1;
        foreach ($products as $product) {
            $menu .= "$i. " . $product['name'] . "\n";
            $menu .= "   Qty: " . $product['quantity'] . "kg\n";
            $menu .= "   Price: " . Util::formatPrice($product['price']) . "\n";
            $menu .= "   Status: " . ($product['quantity'] > 0 ? 'Available' : 'Sold Out') . "\n\n";
            $i++;
        }
        
        $menu .= "Select product number to manage:\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['my_products'] = $products;
        $this->sessionData['level'] = 'manage_product';
        $this->saveSession();
        
        return $menu;
    }

    // My orders methods
    private function showMyOrdersMenu() {
        if ($this->user['role'] === 'farmer') {
            $orders = $this->db->getOrdersByFarmer($this->user['id']);
        } else {
            $orders = $this->db->getOrdersByBuyer($this->user['id']);
        }
        
        if (!$orders || empty($orders)) {
            $menu = "CON My Orders\n";
            $menu .= "You have no orders yet.\n";
            $menu .= "\n" . Util::$GO_BACK . ". Back\n";
            $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
            return $menu;
        }
        
        $menu = "CON My Orders\n";
        $menu .= "Your orders:\n\n";
        
        $i = 1;
        foreach ($orders as $order) {
            $menu .= "$i. " . $order['product_name'] . "\n";
            $menu .= "   Qty: " . $order['quantity'] . "kg\n";
            $menu .= "   Total: " . Util::formatPrice($order['total_price']) . "\n";
            $menu .= "   Status: " . ucfirst($order['status']) . "\n";
            if ($this->user['role'] === 'farmer') {
                $menu .= "   Buyer: " . $order['buyer_name'] . "\n";
            } else {
                $menu .= "   Farmer: " . $order['farmer_name'] . "\n";
            }
            $menu .= "   Date: " . date('Y-m-d', strtotime($order['created_at'])) . "\n\n";
            $i++;
        }
        
        $menu .= "Select order number to view details:\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        $this->sessionData['my_orders'] = $orders;
        $this->sessionData['level'] = 'order_details';
        $this->saveSession();
        
        return $menu;
    }

    // Complete registration
    private function completeRegistration($district, $sector = '') {
        try {
            $userId = $this->db->registerUser(
                $this->phoneNumber,
                $this->sessionData['registration_name'],
                '', // No email required for USSD
                $this->sessionData['registration_role'],
                $this->sessionData['registration_province'],
                $district,
                $sector
            );
            
            if ($userId) {
                // Send welcome SMS
                $this->sms->sendRegistrationSMS(
                    $this->phoneNumber,
                    $this->sessionData['registration_name'],
                    $this->sessionData['registration_role']
                );
                
                // Store name before clearing session
                $userName = $this->sessionData['registration_name'];
                
                // Clear session data
                $this->sessionData = ['language' => $this->language];
                $this->saveSession();
                
                // Reload user
                $this->user = $this->db->getUserByPhone($this->phoneNumber);
                
                $menu = "END " . Util::getLanguageText('registration_success', $this->language) . "\n";
                $menu .= Util::getLanguageText('welcome_message', $this->language) . " " . $userName . "!\n";
                $menu .= Util::getLanguageText('access_services', $this->language) . "\n";
                $menu .= Util::getLanguageText('dial_again', $this->language);
                
                return $menu;
            } else {
                return "END " . Util::getLanguageText('registration_failed', $this->language);
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return "END " . Util::getLanguageText('registration_failed', $this->language);
        }
    }

    // Additional menu handlers
    private function handleManageProduct($input1, $input2, $input3) {
        if ($input3 === Util::$GO_BACK) {
            return $this->showMainMenu();
        }
        
        $selection = (int)trim($input3);
        $products = $this->sessionData['my_products'] ?? [];
        
        if ($selection < 1 || $selection > count($products)) {
            return $this->showMyProductsMenu();
        }
        
        $product = $products[$selection - 1];
        $menu = "CON Product Details\n";
        $menu .= "Name: " . $product['name'] . "\n";
        $menu .= "Quantity: " . $product['quantity'] . " kg\n";
        $menu .= "Price: " . Util::formatPrice($product['price']) . "\n";
        $menu .= "Status: " . ($product['quantity'] > 0 ? 'Available' : 'Sold Out') . "\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        return $menu;
    }

    private function handleOrderDetails($input1, $input2, $input3) {
        if ($input3 === Util::$GO_BACK) {
            return $this->showMainMenu();
        }
        
        $selection = (int)trim($input3);
        $orders = $this->sessionData['my_orders'] ?? [];
        
        if ($selection < 1 || $selection > count($orders)) {
            return $this->showMyOrdersMenu();
        }
        
        $order = $orders[$selection - 1];
        $menu = "CON Order Details\n";
        $menu .= "Product: " . $order['product_name'] . "\n";
        $menu .= "Quantity: " . $order['quantity'] . " kg\n";
        $menu .= "Total: " . Util::formatPrice($order['total_price']) . "\n";
        $menu .= "Status: " . ucfirst($order['status']) . "\n";
        if ($this->user['role'] === 'farmer') {
            $menu .= "Buyer: " . $order['buyer_name'] . "\n";
        } else {
            $menu .= "Farmer: " . $order['farmer_name'] . "\n";
        }
        $menu .= "Date: " . date('Y-m-d', strtotime($order['created_at'])) . "\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        return $menu;
    }

    private function handleFarmingTipDetails($input1, $input2, $input3) {
        if ($input3 === Util::$GO_BACK) {
            return $this->showMainMenu();
        }
        
        $selection = (int)trim($input3);
        $tips = $this->sessionData['farming_tips'] ?? [];
        
        if ($selection < 1 || $selection > count($tips)) {
            return $this->showFarmingTipsMenu();
        }
        
        $tip = $tips[$selection - 1];
        $menu = "CON " . $tip['title'] . "\n";
        $menu .= $tip['content'] . "\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        return $menu;
    }

    private function handleProfileOptions($input1, $input2, $input3) {
        if ($input3 === Util::$GO_BACK) {
            return $this->showMainMenu();
        }
        
        switch ($input3) {
            case '1':
                return "END Profile update not available via USSD. Please use the web platform.";
            case '2':
                return $this->showUserStatistics();
            default:
                return $this->showProfileMenu();
        }
    }

    private function handleHelpMenu($input1, $input2, $input3) {
        if ($input3 === Util::$GO_BACK) {
            return $this->showMainMenu();
        }
        
        return $this->showHelpMenu();
    }

    private function showUserStatistics() {
        $stats = $this->db->getUserStats($this->user['id']);
        
        $menu = "CON My Statistics\n";
        $menu .= "Total Products: " . ($stats['total_products'] ?? 0) . "\n";
        $menu .= "Total Orders: " . ($stats['total_orders'] ?? 0) . "\n";
        $menu .= "Total Sales: " . Util::formatPrice($stats['total_sales'] ?? 0) . "\n";
        $menu .= "Total Purchases: " . Util::formatPrice($stats['total_purchases'] ?? 0) . "\n";
        $menu .= "\n" . Util::$GO_BACK . ". Back\n";
        $menu .= Util::$GO_TO_MAIN_MENU . ". Main Menu";
        
        return $menu;
    }
}
?>

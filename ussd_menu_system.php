<?php
// Complete USSD Menu System for FarmBridge AI
require 'db.php';
require 'ussd_pin_manager.php';

// USSD Menu States
class USSDState {
    const WELCOME = 'welcome';
    const PIN_SETUP = 'pin_setup';
    const PIN_LOGIN = 'pin_login';
    const MAIN_MENU = 'main_menu';
    const MARKETPLACE = 'marketplace';
    const MY_ORDERS = 'my_orders';
    const ORDER_DETAILS = 'order_details';
    const CHANGE_PIN = 'change_pin';
    const ACCOUNT_INFO = 'account_info';
    const VIEW_PRODUCTS = 'view_products';
    const PRODUCT_DETAILS = 'product_details';
    const PLACE_ORDER = 'place_order';
}

// USSD Session Management
class USSDSession {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function get_session_data($session_id) {
        // In a real implementation, you'd store this in Redis or database
        // For now, we'll use a simple file-based approach
        $session_file = "ussd_sessions/" . $session_id . ".json";
        if (file_exists($session_file)) {
            return json_decode(file_get_contents($session_file), true);
        }
        return null;
    }
    
    public function set_session_data($session_id, $data) {
        if (!is_dir('ussd_sessions')) {
            mkdir('ussd_sessions', 0755, true);
        }
        $session_file = "ussd_sessions/" . $session_id . ".json";
        file_put_contents($session_file, json_encode($data));
    }
    
    public function clear_session($session_id) {
        $session_file = "ussd_sessions/" . $session_id . ".json";
        if (file_exists($session_file)) {
            unlink($session_file);
        }
    }
}

// USSD Menu Handler
class USSDMenuHandler {
    private $conn;
    private $session_manager;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->session_manager = new USSDSession($conn);
    }
    
    public function handle_request($phone, $input, $session_id) {
        $session_data = $this->session_manager->get_session_data($session_id);
        
        if (!$session_data) {
            // New session
            $session_data = [
                'phone' => $phone,
                'state' => USSDState::WELCOME,
                'user_id' => null,
                'data' => []
            ];
        }
        
        $response = $this->process_input($session_data, $input);
        
        // Update session data
        $session_data['last_input'] = $input;
        $this->session_manager->set_session_data($session_id, $session_data);
        
        return $response;
    }
    
    private function process_input($session_data, $input) {
        $state = $session_data['state'];
        $phone = $session_data['phone'];
        
        switch ($state) {
            case USSDState::WELCOME:
                return $this->handle_welcome($session_data, $input);
                
            case USSDState::PIN_SETUP:
                return $this->handle_pin_setup($session_data, $input);
                
            case USSDState::PIN_LOGIN:
                return $this->handle_pin_login($session_data, $input);
                
            case USSDState::MAIN_MENU:
                return $this->handle_main_menu($session_data, $input);
                
            case USSDState::MARKETPLACE:
                return $this->handle_marketplace($session_data, $input);
                
            case USSDState::MY_ORDERS:
                return $this->handle_my_orders($session_data, $input);
                
            case USSDState::ORDER_DETAILS:
                return $this->handle_order_details($session_data, $input);
                
            case USSDState::CHANGE_PIN:
                return $this->handle_change_pin($session_data, $input);
                
            case USSDState::ACCOUNT_INFO:
                return $this->handle_account_info($session_data, $input);
                
            case USSDState::VIEW_PRODUCTS:
                return $this->handle_view_products($session_data, $input);
                
            case USSDState::PRODUCT_DETAILS:
                return $this->handle_product_details($session_data, $input);
                
            case USSDState::PLACE_ORDER:
                return $this->handle_place_order($session_data, $input);
                
            default:
                return "Invalid session. Please restart.";
        }
    }
    
    private function handle_welcome($session_data, $input) {
        $user = find_user_by_phone($this->conn, $session_data['phone']);
        
        if (!$user) {
            // Register new user
            $result = register_ussd_user($this->conn, $session_data['phone']);
            if ($result['success']) {
                $session_data['user_id'] = $result['user_id'];
                $session_data['state'] = USSDState::PIN_SETUP;
                return "Welcome to FarmBridge AI!\n\nYou are now registered.\n\nPlease create a 4-digit PIN:\nEnter your PIN:";
            } else {
                return "Registration failed. Please try again later.";
            }
        } else {
            $session_data['user_id'] = $user['id'];
            
            if (!$user['pin']) {
                // User exists but no PIN
                $session_data['state'] = USSDState::PIN_SETUP;
                return "Welcome back!\n\nPlease create a 4-digit PIN:\nEnter your PIN:";
            } else {
                // User has PIN - go to login
                $session_data['state'] = USSDState::PIN_LOGIN;
                return "Welcome to FarmBridge AI!\n\nPlease enter your 4-digit PIN:";
            }
        }
    }
    
    private function handle_pin_setup($session_data, $input) {
        if (!preg_match('/^\d{4}$/', $input)) {
            return "Invalid PIN. Please enter exactly 4 digits:\nEnter your PIN:";
        }
        
        $result = create_user_pin($this->conn, $session_data['user_id'], $input);
        if ($result['success']) {
            $session_data['state'] = USSDState::MAIN_MENU;
            return $this->get_main_menu();
        } else {
            return "Failed to create PIN. Please try again:\nEnter your PIN:";
        }
    }
    
    private function handle_pin_login($session_data, $input) {
        if (!preg_match('/^\d{4}$/', $input)) {
            return "Invalid PIN format. Please enter exactly 4 digits:\nEnter your PIN:";
        }
        
        if (verify_user_pin($this->conn, $session_data['user_id'], $input)) {
            $session_data['state'] = USSDState::MAIN_MENU;
            return $this->get_main_menu();
        } else {
            return "Invalid PIN. Please try again:\nEnter your PIN:";
        }
    }
    
    private function handle_main_menu($session_data, $input) {
        switch ($input) {
            case '1':
                $session_data['state'] = USSDState::MARKETPLACE;
                return $this->get_marketplace_menu();
                
            case '2':
                $session_data['state'] = USSDState::MY_ORDERS;
                return $this->get_my_orders_menu();
                
            case '3':
                $session_data['state'] = USSDState::CHANGE_PIN;
                return "Change PIN\n\n You can change your PIN anytime for security.\n\nEnter your current PIN:";
                
            case '4':
                $session_data['state'] = USSDState::ACCOUNT_INFO;
                return $this->get_account_info($session_data['user_id']);
                
            case '0':
                return "Thank you for using FarmBridge AI!\n\nGoodbye!";
                
            default:
                return $this->get_main_menu() . "\n\nInvalid choice. Please select 1-4 or 0 to exit.";
        }
    }
    
    private function handle_marketplace($session_data, $input) {
        switch ($input) {
            case '1':
                $session_data['state'] = USSDState::VIEW_PRODUCTS;
                return $this->get_products_list();
                
            case '2':
                return $this->get_marketplace_menu() . "\n\nFeature coming soon!";
                
            case '0':
                $session_data['state'] = USSDState::MAIN_MENU;
                return $this->get_main_menu();
                
            default:
                return $this->get_marketplace_menu() . "\n\nInvalid choice. Please select 1-2 or 0 to go back.";
        }
    }
    
    private function handle_my_orders($session_data, $input) {
        if ($input === '0') {
            $session_data['state'] = USSDState::MAIN_MENU;
            return $this->get_main_menu();
        }
        
        // Show order details
        $order_id = (int)$input;
        return $this->get_order_details($session_data['user_id'], $order_id);
    }
    
    private function handle_change_pin($session_data, $input) {
        // This is a simplified version - in reality you'd need to handle multiple steps
        if (!preg_match('/^\d{4}$/', $input)) {
            return "Invalid PIN format. Please enter exactly 4 digits:\nEnter your current PIN:";
        }
        
        if (verify_user_pin($this->conn, $session_data['user_id'], $input)) {
            $session_data['state'] = USSDState::MAIN_MENU;
            return "PIN verified. To change PIN, please use the web platform or contact support.\n\n" . $this->get_main_menu();
        } else {
            return "Invalid PIN. Please try again:\nEnter your current PIN:";
        }
    }
    
    private function handle_account_info($session_data, $input) {
        $session_data['state'] = USSDState::MAIN_MENU;
        return $this->get_main_menu();
    }
    
    private function handle_view_products($session_data, $input) {
        if ($input === '0') {
            $session_data['state'] = USSDState::MARKETPLACE;
            return $this->get_marketplace_menu();
        }
        
        // Show product details
        $product_id = (int)$input;
        return $this->get_product_details($product_id);
    }
    
    // Menu generation methods
    private function get_main_menu() {
        return "FarmBridge AI - Main Menu\n\n1. Marketplace\n2. My Orders\n3. Change PIN\n4. Account Info\n0. Exit\n\nEnter your choice (1-4 or 0):";
    }
    
    private function get_marketplace_menu() {
        return "Marketplace\n\n1. View Available Products\n2. Search Products\n0. Back to Main Menu\n\nEnter your choice (1-2 or 0):";
    }
    
    private function get_my_orders_menu() {
        $user_id = $this->get_current_user_id();
        $orders = $this->get_user_orders($user_id);
        
        if (empty($orders)) {
            return "My Orders\n\nNo orders found.\n\n0. Back to Main Menu\n\nEnter your choice (0):";
        }
        
        $menu = "My Orders\n\n";
        foreach ($orders as $order) {
            $menu .= "{$order['id']}. Order #{$order['id']} - {$order['status']}\n";
        }
        $menu .= "0. Back to Main Menu\n\nEnter order number to view details:";
        
        return $menu;
    }
    
    private function get_products_list() {
        $products = $this->get_available_products();
        
        if (empty($products)) {
            return "Available Products\n\nNo products available at the moment.\n\n0. Back to Marketplace\n\nEnter your choice (0):";
        }
        
        $menu = "Available Products\n\n";
        foreach ($products as $product) {
            $menu .= "{$product['id']}. {$product['name']} - {$product['price']} RWF\n";
        }
        $menu .= "0. Back to Marketplace\n\nEnter product number to view details:";
        
        return $menu;
    }
    
    private function get_account_info($user_id) {
        $user = $this->get_user_info($user_id);
        return "Account Information\n\nName: {$user['name']}\nPhone: {$user['phone']}\nEmail: {$user['email']}\nRole: {$user['role']}\n\n0. Back to Main Menu\n\nEnter your choice (0):";
    }
    
    private function get_order_details($user_id, $order_id) {
        $order = $this->get_order_by_id($user_id, $order_id);
        
        if (!$order) {
            return "Order not found.\n\n0. Back to My Orders\n\nEnter your choice (0):";
        }
        
        return "Order Details\n\nOrder #{$order['id']}\nProduct: {$order['crop_name']}\nQuantity: {$order['quantity']}\nTotal: {$order['total']} RWF\nStatus: {$order['status']}\n\n0. Back to My Orders\n\nEnter your choice (0):";
    }
    
    private function get_product_details($product_id) {
        $product = $this->get_product_by_id($product_id);
        
        if (!$product) {
            return "Product not found.\n\n0. Back to Products\n\nEnter your choice (0):";
        }
        
        return "Product Details\n\n{$product['name']}\nDescription: {$product['description']}\nPrice: {$product['price']} RWF per {$product['unit']}\nAvailable: {$product['quantity']} {$product['unit']}\n\nNote: To place orders, please use our web platform.\n\n0. Back to Products\n\nEnter your choice (0):";
    }
    
    // Database helper methods
    private function get_user_orders($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT o.*, c.name as crop_name FROM orders o JOIN crops c ON o.crop_id = c.id WHERE o.buyer_id = ? ORDER BY o.created_at DESC LIMIT 10");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function get_available_products() {
        try {
            $stmt = $this->conn->prepare("SELECT id, name, price, unit, quantity FROM crops WHERE status = 'available' AND quantity > 0 ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function get_user_info($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT name, phone, email, role FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return ['name' => 'Unknown', 'phone' => 'Unknown', 'email' => 'Unknown', 'role' => 'Unknown'];
        }
    }
    
    private function get_order_by_id($user_id, $order_id) {
        try {
            $stmt = $this->conn->prepare("SELECT o.*, c.name as crop_name FROM orders o JOIN crops c ON o.crop_id = c.id WHERE o.id = ? AND o.buyer_id = ?");
            $stmt->bind_param('ii', $order_id, $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function get_product_by_id($product_id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM crops WHERE id = ? AND status = 'available'");
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function get_current_user_id() {
        // This would need to be passed through session data
        return 1; // Placeholder
    }
}

// Handle USSD requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $input = $_POST['input'] ?? '';
    $session_id = $_POST['session_id'] ?? uniqid();
    
    if (empty($phone)) {
        echo json_encode(['response' => 'Invalid request']);
        exit;
    }
    
    $menu_handler = new USSDMenuHandler($conn);
    $response = $menu_handler->handle_request($phone, $input, $session_id);
    
    echo json_encode(['response' => $response]);
    exit;
}

// Admin interface for testing
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    echo "<h2>USSD Menu System Test</h2>";
    
    if (isset($_POST['test_ussd_menu'])) {
        $test_phone = $_POST['test_phone'];
        $test_input = $_POST['test_input'];
        $test_session = $_POST['test_session'] ?? uniqid();
        
        echo "<h3>USSD Menu Test Result:</h3>";
        echo "<p><strong>Phone:</strong> $test_phone</p>";
        echo "<p><strong>Input:</strong> $test_input</p>";
        echo "<p><strong>Session:</strong> $test_session</p>";
        
        $menu_handler = new USSDMenuHandler($conn);
        $response = $menu_handler->handle_request($test_phone, $test_input, $test_session);
        
        echo "<p><strong>Response:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; white-space: pre-wrap;'>" . htmlspecialchars($response) . "</pre>";
    }
    
    echo "<form method='post' style='max-width: 600px; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";
    echo "<h4>Test USSD Menu System</h4>";
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>Phone Number</label>";
    echo "<input type='text' name='test_phone' class='form-control' placeholder='250788123456' required>";
    echo "</div>";
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>Session ID (optional)</label>";
    echo "<input type='text' name='test_session' class='form-control' placeholder='Leave empty for new session'>";
    echo "</div>";
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>USSD Input</label>";
    echo "<input type='text' name='test_input' class='form-control' placeholder='Enter USSD input'>";
    echo "</div>";
    echo "<button type='submit' name='test_ussd_menu' class='btn btn-primary'>Test USSD Menu</button>";
    echo "</form>";
    
    echo "<hr>";
    echo "<p><a href='admin.php'>← Back to Admin Dashboard</a></p>";
}
?>

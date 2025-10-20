<?php
// USSD PIN Management System
require 'db.php';

// Function to check if PIN column exists
function pin_column_exists($conn) {
    try {
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'pin'");
        return ($result && $result->num_rows > 0);
    } catch (Exception $e) {
        return false;
    }
}

// Function to create PIN for user
function create_user_pin($conn, $user_id, $pin) {
    if (!pin_column_exists($conn)) {
        return ['success' => false, 'error' => 'PIN column not available'];
    }
    
    try {
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET pin = ? WHERE id = ?");
        $stmt->bind_param('si', $hashed_pin, $user_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'PIN created successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to create PIN'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to verify PIN
function verify_user_pin($conn, $user_id, $pin) {
    if (!pin_column_exists($conn)) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT pin FROM users WHERE id = ? AND pin IS NOT NULL");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            return password_verify($pin, $row['pin']);
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

// Function to update PIN
function update_user_pin($conn, $user_id, $old_pin, $new_pin) {
    if (!pin_column_exists($conn)) {
        return ['success' => false, 'error' => 'PIN column not available'];
    }
    
    try {
        // First verify old PIN
        if (!verify_user_pin($conn, $user_id, $old_pin)) {
            return ['success' => false, 'error' => 'Invalid current PIN'];
        }
        
        $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET pin = ? WHERE id = ?");
        $stmt->bind_param('si', $hashed_pin, $user_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'PIN updated successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to update PIN'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to find user by phone for USSD
function find_user_by_phone($conn, $phone) {
    try {
        $stmt = $conn->prepare("SELECT id, name, role, phone, email, pin FROM users WHERE phone = ? LIMIT 1");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Function to register new user via USSD
function register_ussd_user($conn, $phone, $name = null) {
    try {
        // Generate a temporary email if not provided
        $temp_email = $phone . '@ussd.temp';
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, role) VALUES (?, ?, ?, 'buyer')");
        $name = $name ?: 'USSD User';
        $stmt->bind_param('sss', $name, $temp_email, $phone);
        
        if ($stmt->execute()) {
            return ['success' => true, 'user_id' => $conn->insert_id];
        } else {
            return ['success' => false, 'error' => 'Failed to create user'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// USSD Menu Handler
function handle_ussd_menu($conn, $phone, $input) {
    $user = find_user_by_phone($conn, $phone);
    
    // If no user found, register them
    if (!$user) {
        $result = register_ussd_user($conn, $phone);
        if ($result['success']) {
            return "Welcome to FarmBridge AI!\n\nYou are now registered.\n\nPlease create a 4-digit PIN:\nEnter your PIN:";
        } else {
            return "Registration failed. Please try again later.";
        }
    }
    
    // Check if user has PIN set
    if (!$user['pin']) {
        // User exists but no PIN - prompt to create PIN
        return "Welcome back!\n\nPlease create a 4-digit PIN:\nEnter your PIN:";
    }
    
    // User has PIN - show main menu
    return "Welcome to FarmBridge AI!\n\nMain Menu:\n1. View Marketplace\n2. My Orders\n3. Change PIN\n4. Account Info\n\nEnter your choice (1-4):";
}

// USSD PIN Setup Handler
function handle_pin_setup($conn, $user_id, $pin) {
    // Validate PIN (4 digits)
    if (!preg_match('/^\d{4}$/', $pin)) {
        return "Invalid PIN. Please enter exactly 4 digits.";
    }
    
    $result = create_user_pin($conn, $user_id, $pin);
    if ($result['success']) {
        return "PIN created successfully!\n\nMain Menu:\n1. View Marketplace\n2. My Orders\n3. Change PIN\n4. Account Info\n\nEnter your choice (1-4):";
    } else {
        return "Failed to create PIN. Please try again.";
    }
}

// USSD Login Handler
function handle_ussd_login($conn, $phone, $pin) {
    $user = find_user_by_phone($conn, $phone);
    
    if (!$user) {
        return "User not found. Please register first.";
    }
    
    if (verify_user_pin($conn, $user['id'], $pin)) {
        return "Login successful!\n\nMain Menu:\n1. View Marketplace\n2. My Orders\n3. Change PIN\n4. Account Info\n\nEnter your choice (1-4):";
    } else {
        return "Invalid PIN. Please try again.";
    }
}

// Handle USSD requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $input = $_POST['input'] ?? '';
    $session_id = $_POST['session_id'] ?? '';
    
    if (empty($phone) || empty($input)) {
        echo json_encode(['response' => 'Invalid request']);
        exit;
    }
    
    // Handle different USSD states based on input
    $response = '';
    
    if (strlen($input) === 4 && is_numeric($input)) {
        // This looks like a PIN
        $user = find_user_by_phone($conn, $phone);
        if ($user && !$user['pin']) {
            // User needs to set PIN
            $response = handle_pin_setup($conn, $user['id'], $input);
        } else {
            // User is trying to login with PIN
            $response = handle_ussd_login($conn, $phone, $input);
        }
    } else {
        // Handle menu navigation
        $response = handle_ussd_menu($conn, $phone, $input);
    }
    
    echo json_encode(['response' => $response]);
    exit;
}

// Admin interface for testing USSD
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    echo "<h2>USSD PIN Management System</h2>";
    
    // Test USSD functionality
    if (isset($_POST['test_ussd'])) {
        $test_phone = $_POST['test_phone'];
        $test_input = $_POST['test_input'];
        
        echo "<h3>USSD Test Result:</h3>";
        echo "<p><strong>Phone:</strong> $test_phone</p>";
        echo "<p><strong>Input:</strong> $test_input</p>";
        
        $response = handle_ussd_menu($conn, $test_phone, $test_input);
        echo "<p><strong>Response:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($response) . "</pre>";
    }
    
    echo "<form method='post' style='max-width: 500px; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";
    echo "<h4>Test USSD System</h4>";
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>Phone Number</label>";
    echo "<input type='text' name='test_phone' class='form-control' placeholder='250788123456' required>";
    echo "</div>";
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>USSD Input</label>";
    echo "<input type='text' name='test_input' class='form-control' placeholder='Enter USSD input'>";
    echo "</div>";
    echo "<button type='submit' name='test_ussd' class='btn btn-primary'>Test USSD</button>";
    echo "</form>";
    
    echo "<hr>";
    echo "<p><a href='admin.php'>← Back to Admin Dashboard</a></p>";
}

?>

<?php
/**
 * SMS Integration Examples for FarmBridgeAI
 * 
 * This file contains practical examples of how to integrate SMS notifications
 * into your existing PHP files. Copy and adapt these examples as needed.
 */

// ============================================================================
// EXAMPLE 1: Registration Welcome SMS (register.php)
// ============================================================================

/*
// Add this after successful user registration in register.php

require_once 'sms_helpers.php';

// Assuming you have these variables after registration
$user_phone = $_POST['phone']; // or from database
$user_name = $_POST['name'];
$user_role = $_POST['role']; // 'farmer' or 'buyer'

// Format and validate phone number
$formatted_phone = format_phone_number($user_phone);

if (validate_phone_number($formatted_phone)) {
    // Send welcome SMS
    $sms_result = send_registration_sms($formatted_phone, $user_name, $user_role);
    
    // Optional: Log the result
    if ($sms_result['success']) {
        error_log("Welcome SMS sent to $user_name");
    } else {
        error_log("Failed to send welcome SMS: " . $sms_result['message']);
    }
}
*/

// ============================================================================
// EXAMPLE 2: New Order Notification (process_order.php)
// ============================================================================

/*
// Add this after order is successfully created in process_order.php

require_once 'sms_helpers.php';
require_once 'db.php';

// Assuming you have the order details
$order_id = $inserted_order_id;
$crop_name = $_POST['crop_name'];
$quantity = $_POST['quantity'];

// Get farmer details from database
$farmer_query = "SELECT u.name, u.phone 
                 FROM users u 
                 JOIN crops c ON u.id = c.user_id 
                 WHERE c.id = ?";
$stmt = $conn->prepare($farmer_query);
$stmt->bind_param("i", $crop_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

// Get buyer details (from session)
$buyer_query = "SELECT name, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($buyer_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$buyer = $stmt->get_result()->fetch_assoc();

// Format phone numbers
$farmer_phone = format_phone_number($farmer['phone']);
$buyer_phone = format_phone_number($buyer['phone']);

// Send notification to farmer
if (validate_phone_number($farmer_phone)) {
    send_order_notification_farmer(
        $farmer_phone,
        $farmer['name'],
        $crop_name,
        $quantity,
        $order_id
    );
}

// Send confirmation to buyer
if (validate_phone_number($buyer_phone)) {
    send_order_confirmation_buyer(
        $buyer_phone,
        $buyer['name'],
        $crop_name,
        $quantity,
        $order_id
    );
}
*/

// ============================================================================
// EXAMPLE 3: Payment Confirmation (confirm_payment.php or afripay_callback.php)
// ============================================================================

/*
// Add this after payment is verified

require_once 'sms_helpers.php';
require_once 'db.php';

// Get order and payment details
$order_query = "SELECT o.id, o.total_price, 
                       b.name as buyer_name, b.phone as buyer_phone,
                       f.name as farmer_name, f.phone as farmer_phone
                FROM orders o
                JOIN users b ON o.user_id = b.id
                JOIN crops c ON o.crop_id = c.id
                JOIN users f ON c.user_id = f.id
                WHERE o.id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Format phone numbers
$buyer_phone = format_phone_number($order['buyer_phone']);
$farmer_phone = format_phone_number($order['farmer_phone']);

// Notify buyer
if (validate_phone_number($buyer_phone)) {
    send_payment_confirmation(
        $buyer_phone,
        $order['buyer_name'],
        $order['total_price'],
        $order['id']
    );
}

// Notify farmer
if (validate_phone_number($farmer_phone)) {
    send_payment_confirmation(
        $farmer_phone,
        $order['farmer_name'],
        $order['total_price'],
        $order['id']
    );
}
*/

// ============================================================================
// EXAMPLE 4: Order Status Update (update_order_status.php)
// ============================================================================

/*
// Add this when order status is updated

require_once 'sms_helpers.php';
require_once 'db.php';

// Get order and user details
$query = "SELECT o.id, o.status, u.name, u.phone
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE o.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Format phone number
$phone = format_phone_number($order['phone']);

// Send status update
if (validate_phone_number($phone)) {
    send_order_status_update(
        $phone,
        $order['name'],
        $new_status, // 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'
        $order['id']
    );
}
*/

// ============================================================================
// EXAMPLE 5: Dispute Notification (raise_dispute.php)
// ============================================================================

/*
// Add this after dispute is created

require_once 'sms_helpers.php';
require_once 'db.php';

// Get the other party's details (if buyer raises dispute, notify farmer and vice versa)
if ($_SESSION['role'] === 'buyer') {
    // Notify farmer
    $query = "SELECT u.name, u.phone
              FROM users u
              JOIN crops c ON u.id = c.user_id
              JOIN orders o ON c.id = o.crop_id
              WHERE o.id = ?";
} else {
    // Notify buyer
    $query = "SELECT u.name, u.phone
              FROM users u
              JOIN orders o ON u.id = o.user_id
              WHERE o.id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$other_party = $stmt->get_result()->fetch_assoc();

// Format phone number
$phone = format_phone_number($other_party['phone']);

// Send dispute notification
if (validate_phone_number($phone)) {
    send_dispute_notification(
        $phone,
        $other_party['name'],
        $order_id,
        $dispute_type // e.g., 'Quality Issue', 'Late Delivery', etc.
    );
}
*/

// ============================================================================
// EXAMPLE 6: Email Verification Code (verify_email.php or register.php)
// ============================================================================

/*
// Add this when generating verification code

require_once 'sms_helpers.php';

// Generate verification code
$verification_code = rand(100000, 999999);

// Store in session or database
$_SESSION['verification_code'] = $verification_code;
$_SESSION['verification_expires'] = time() + 600; // 10 minutes

// Format phone number
$phone = format_phone_number($_POST['phone']);

// Send verification code
if (validate_phone_number($phone)) {
    send_verification_code($phone, $verification_code);
}
*/

// ============================================================================
// EXAMPLE 7: Custom Notification with Error Handling
// ============================================================================

/*
require_once 'sms_helpers.php';

function send_custom_notification($user_id, $message) {
    global $conn;
    
    // Get user details
    $query = "SELECT name, phone FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Format phone number
    $phone = format_phone_number($user['phone']);
    
    // Validate phone number
    if (!validate_phone_number($phone)) {
        return ['success' => false, 'message' => 'Invalid phone number'];
    }
    
    // Send SMS
    $result = send_sms($phone, $message);
    
    // Log the result
    log_sms($phone, $message, $result);
    
    return $result;
}

// Usage
$result = send_custom_notification($user_id, "Your custom message here");

if ($result['success']) {
    echo "Notification sent successfully!";
} else {
    echo "Failed to send notification: " . $result['message'];
}
*/

// ============================================================================
// EXAMPLE 8: Bulk SMS to All Farmers
// ============================================================================

/*
require_once 'sms_helpers.php';
require_once 'db.php';

// Get all farmer phone numbers
$query = "SELECT name, phone FROM users WHERE role = 'farmer' AND phone IS NOT NULL";
$result = $conn->query($query);

$message = "Important announcement: New market prices available on FarmBridgeAI!";

while ($farmer = $result->fetch_assoc()) {
    $phone = format_phone_number($farmer['phone']);
    
    if (validate_phone_number($phone)) {
        $sms_result = send_sms($phone, $message);
        
        // Log result
        error_log("SMS to {$farmer['name']}: " . ($sms_result['success'] ? 'Sent' : 'Failed'));
        
        // Small delay to avoid rate limiting
        usleep(100000); // 0.1 second
    }
}
*/

// ============================================================================
// EXAMPLE 9: Payment Reminder Cron Job
// ============================================================================

/*
// Create a file: send_payment_reminders.php
// Run this as a cron job daily

require_once 'sms_helpers.php';
require_once 'db.php';

// Get orders with pending payments due in 2 days
$query = "SELECT o.id, o.total_price, o.due_date,
                 u.name, u.phone
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE o.payment_status = 'pending'
          AND o.due_date = DATE_ADD(CURDATE(), INTERVAL 2 DAY)";

$result = $conn->query($query);

while ($order = $result->fetch_assoc()) {
    $phone = format_phone_number($order['phone']);
    
    if (validate_phone_number($phone)) {
        send_payment_reminder(
            $phone,
            $order['name'],
            $order['total_price'],
            $order['id'],
            $order['due_date']
        );
    }
}
*/

// ============================================================================
// EXAMPLE 10: Price Alert System
// ============================================================================

/*
require_once 'sms_helpers.php';
require_once 'db.php';

// When crop price changes significantly
function notify_price_change($crop_id, $new_price) {
    global $conn;
    
    // Get crop details
    $crop_query = "SELECT name FROM crops WHERE id = ?";
    $stmt = $conn->prepare($crop_query);
    $stmt->bind_param("i", $crop_id);
    $stmt->execute();
    $crop = $stmt->get_result()->fetch_assoc();
    
    // Get users interested in this crop (you might have a preferences table)
    $users_query = "SELECT u.name, u.phone 
                    FROM users u
                    JOIN user_preferences p ON u.id = p.user_id
                    WHERE p.crop_id = ? AND p.price_alerts = 1";
    $stmt = $conn->prepare($users_query);
    $stmt->bind_param("i", $crop_id);
    $stmt->execute();
    $users = $stmt->get_result();
    
    // Send alerts
    while ($user = $users->fetch_assoc()) {
        $phone = format_phone_number($user['phone']);
        
        if (validate_phone_number($phone)) {
            send_price_alert(
                $phone,
                $user['name'],
                $crop['name'],
                $new_price
            );
        }
    }
}
*/

// ============================================================================
// HELPER: Complete Order Processing with SMS
// ============================================================================

/*
function process_order_with_notifications($order_data) {
    require_once 'sms_helpers.php';
    require_once 'db.php';
    
    global $conn;
    
    // 1. Create order in database
    $query = "INSERT INTO orders (user_id, crop_id, quantity, total_price, status) 
              VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iidd", 
        $order_data['buyer_id'],
        $order_data['crop_id'],
        $order_data['quantity'],
        $order_data['total_price']
    );
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to create order'];
    }
    
    $order_id = $conn->insert_id;
    
    // 2. Get farmer and buyer details
    $details_query = "SELECT 
                        b.name as buyer_name, b.phone as buyer_phone,
                        f.name as farmer_name, f.phone as farmer_phone,
                        c.name as crop_name
                      FROM orders o
                      JOIN users b ON o.user_id = b.id
                      JOIN crops c ON o.crop_id = c.id
                      JOIN users f ON c.user_id = f.id
                      WHERE o.id = ?";
    $stmt = $conn->prepare($details_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    
    // 3. Send SMS notifications
    $farmer_phone = format_phone_number($details['farmer_phone']);
    $buyer_phone = format_phone_number($details['buyer_phone']);
    
    // Notify farmer
    if (validate_phone_number($farmer_phone)) {
        send_order_notification_farmer(
            $farmer_phone,
            $details['farmer_name'],
            $details['crop_name'],
            $order_data['quantity'],
            $order_id
        );
    }
    
    // Notify buyer
    if (validate_phone_number($buyer_phone)) {
        send_order_confirmation_buyer(
            $buyer_phone,
            $details['buyer_name'],
            $details['crop_name'],
            $order_data['quantity'],
            $order_id
        );
    }
    
    return [
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order created and notifications sent'
    ];
}
*/

?>

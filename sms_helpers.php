<?php
/**
 * SMS Helper Functions for FarmBridgeAI
 * Provides easy-to-use functions for sending SMS notifications
 */

require_once 'sms_config.php';
require_once 'sms_parse.php';

/**
 * Initialize SMS Gateway
 */
function init_sms() {
    hdev_sms::api_id(SMS_API_ID);
    hdev_sms::api_key(SMS_API_KEY);
}

/**
 * Send SMS with error handling
 * 
 * @param string $phone Phone number (e.g., 250788123456)
 * @param string $message Message to send
 * @param string $sender_id Optional sender ID (defaults to config)
 * @return array ['success' => bool, 'message' => string, 'data' => object]
 */
function send_sms($phone, $message, $sender_id = null) {
    // Check if SMS is enabled
    if (!SMS_ENABLED) {
        return [
            'success' => false,
            'message' => 'SMS service is currently disabled',
            'data' => null
        ];
    }
    
    // Use default sender ID if not provided
    if ($sender_id === null) {
        $sender_id = SMS_SENDER_ID;
    }
    
    // Initialize SMS
    init_sms();
    
    // Send SMS
    try {
        $result = hdev_sms::send($sender_id, $phone, $message);
        
        if (isset($result->status) && $result->status === 'success') {
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => $result
            ];
        } else {
            return [
                'success' => false,
                'message' => $result->message ?? 'Failed to send SMS',
                'data' => $result
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'data' => null
        ];
    }
}

/**
 * Send registration welcome SMS
 * 
 * @param string $phone Phone number
 * @param string $name User's name
 * @param string $role User role (farmer/buyer)
 * @return array Result array
 */
function send_registration_sms($phone, $name, $role) {
    $message = "Welcome to FarmBridgeAI, $name! Your $role account has been created successfully. Start connecting with " . 
               ($role === 'farmer' ? 'buyers' : 'farmers') . " today!";
    
    return send_sms($phone, $message);
}

/**
 * Send order notification to farmer
 * 
 * @param string $phone Farmer's phone number
 * @param string $farmer_name Farmer's name
 * @param string $crop_name Crop name
 * @param float $quantity Quantity ordered
 * @param string $order_id Order ID
 * @return array Result array
 */
function send_order_notification_farmer($phone, $farmer_name, $crop_name, $quantity, $order_id) {
    $message = "Hello $farmer_name, You have a new order! $quantity kg of $crop_name. Order #$order_id. Login to FarmBridgeAI to view details.";
    
    return send_sms($phone, $message);
}

/**
 * Send order confirmation to buyer
 * 
 * @param string $phone Buyer's phone number
 * @param string $buyer_name Buyer's name
 * @param string $crop_name Crop name
 * @param float $quantity Quantity ordered
 * @param string $order_id Order ID
 * @return array Result array
 */
function send_order_confirmation_buyer($phone, $buyer_name, $crop_name, $quantity, $order_id) {
    $message = "Hello $buyer_name, Your order has been placed! $quantity kg of $crop_name. Order #$order_id. We'll notify you when the farmer confirms.";
    
    return send_sms($phone, $message);
}

/**
 * Send payment confirmation SMS
 * 
 * @param string $phone Phone number
 * @param string $name User's name
 * @param float $amount Payment amount
 * @param string $order_id Order ID
 * @return array Result array
 */
function send_payment_confirmation($phone, $name, $amount, $order_id) {
    $message = "Hello $name, Payment of RWF " . number_format($amount, 0) . " received for Order #$order_id. Thank you for using FarmBridgeAI!";
    
    return send_sms($phone, $message);
}

/**
 * Send order status update SMS
 * 
 * @param string $phone Phone number
 * @param string $name User's name
 * @param string $status New status
 * @param string $order_id Order ID
 * @return array Result array
 */
function send_order_status_update($phone, $name, $status, $order_id) {
    $status_messages = [
        'confirmed' => 'Your order has been confirmed by the farmer',
        'processing' => 'Your order is being processed',
        'shipped' => 'Your order has been shipped',
        'delivered' => 'Your order has been delivered',
        'cancelled' => 'Your order has been cancelled'
    ];
    
    $status_text = $status_messages[$status] ?? "Order status updated to: $status";
    $message = "Hello $name, $status_text. Order #$order_id. Check FarmBridgeAI for details.";
    
    return send_sms($phone, $message);
}

/**
 * Send dispute notification SMS
 * 
 * @param string $phone Phone number
 * @param string $name User's name
 * @param string $order_id Order ID
 * @param string $dispute_type Type of dispute
 * @return array Result array
 */
function send_dispute_notification($phone, $name, $order_id, $dispute_type) {
    $message = "Hello $name, A dispute has been raised for Order #$order_id regarding $dispute_type. Please login to FarmBridgeAI to respond.";
    
    return send_sms($phone, $message);
}

/**
 * Send payment reminder SMS
 * 
 * @param string $phone Phone number
 * @param string $name User's name
 * @param float $amount Amount due
 * @param string $order_id Order ID
 * @param string $due_date Due date
 * @return array Result array
 */
function send_payment_reminder($phone, $name, $amount, $order_id, $due_date) {
    $message = "Hello $name, Payment reminder: RWF " . number_format($amount, 0) . " due for Order #$order_id by $due_date. Pay now on FarmBridgeAI.";
    
    return send_sms($phone, $message);
}

/**
 * Send price alert SMS
 * 
 * @param string $phone Phone number
 * @param string $name User's name
 * @param string $crop_name Crop name
 * @param float $price Current price
 * @return array Result array
 */
function send_price_alert($phone, $name, $crop_name, $price) {
    $message = "Hello $name, Price Alert: $crop_name is now at RWF " . number_format($price, 0) . "/kg. Great time to buy/sell on FarmBridgeAI!";
    
    return send_sms($phone, $message);
}

/**
 * Send verification code SMS
 * 
 * @param string $phone Phone number
 * @param string $code Verification code
 * @return array Result array
 */
function send_verification_code($phone, $code) {
    $message = "Your FarmBridgeAI verification code is: $code. This code will expire in 10 minutes. Do not share this code with anyone.";
    
    return send_sms($phone, $message);
}

/**
 * Send custom SMS
 * 
 * @param string $phone Phone number
 * @param string $message Custom message
 * @return array Result array
 */
function send_custom_sms($phone, $message) {
    return send_sms($phone, $message);
}

/**
 * Log SMS activity to file
 * 
 * @param string $phone Phone number
 * @param string $message Message sent
 * @param array $result Result from send_sms
 */
function log_sms($phone, $message, $result) {
    $log_file = __DIR__ . '/sms_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $status = $result['success'] ? 'SUCCESS' : 'FAILED';
    
    $log_entry = "[$timestamp] [$status] Phone: $phone | Message: " . substr($message, 0, 50) . "... | Response: " . $result['message'] . "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Format phone number for Rwanda
 * Converts various formats to standard format (250788123456)
 * 
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function format_phone_number($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If starts with 0, replace with 250
    if (substr($phone, 0, 1) === '0') {
        $phone = '250' . substr($phone, 1);
    }
    
    // If doesn't start with 250, add it
    if (substr($phone, 0, 3) !== '250') {
        $phone = '250' . $phone;
    }
    
    return $phone;
}

/**
 * Validate phone number format
 * 
 * @param string $phone Phone number
 * @return bool True if valid
 */
function validate_phone_number($phone) {
    $phone = format_phone_number($phone);
    
    // Rwanda phone numbers: 250 + 9 digits
    return preg_match('/^250[0-9]{9}$/', $phone);
}

/**
 * Send bulk SMS to multiple recipients
 * 
 * @param array $recipients Array of phone numbers
 * @param string $message Message to send
 * @return array Results for each recipient
 */
function send_bulk_sms($recipients, $message) {
    $results = [];
    
    foreach ($recipients as $phone) {
        $result = send_sms($phone, $message);
        $results[$phone] = $result;
        
        // Small delay to avoid rate limiting
        usleep(100000); // 0.1 second delay
    }
    
    return $results;
}
?>

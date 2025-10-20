<?php
/**
 * HDEV SMS Gateway Callback/Webhook Handler
 * This endpoint receives delivery reports and incoming SMS from HDEV SMS Gateway
 */

header('Content-Type: application/json');

// Log all incoming requests for debugging
$log_file = __DIR__ . '/sms_callback_log.txt';
$timestamp = date('Y-m-d H:i:s');

// Get all request data
$request_data = [
    'timestamp' => $timestamp,
    'method' => $_SERVER['REQUEST_METHOD'],
    'get' => $_GET,
    'post' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'headers' => getallheaders(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

// Log the request
file_put_contents($log_file, json_encode($request_data, JSON_PRETTY_PRINT) . "\n---\n", FILE_APPEND);

// Handle GET requests (for testing/verification)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'success',
        'message' => 'HDEV SMS Webhook is online',
        'endpoint' => 'FarmBridgeAI SMS Callback',
        'timestamp' => $timestamp
    ]);
    exit;
}

// Handle POST requests (actual callbacks from HDEV)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // HDEV SMS may send data as POST params or JSON
    $data = $_POST;
    
    // If POST is empty, try to parse JSON from raw input
    if (empty($data)) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];
    }
    
    // Extract callback data
    $callback_type = $data['type'] ?? $data['callback_type'] ?? 'unknown';
    $status = $data['status'] ?? '';
    $message_id = $data['message_id'] ?? $data['sms_id'] ?? '';
    $phone = $data['phone'] ?? $data['tel'] ?? $data['recipient'] ?? '';
    $message = $data['message'] ?? '';
    $delivery_status = $data['delivery_status'] ?? '';
    
    // Process based on callback type
    switch ($callback_type) {
        case 'delivery_report':
        case 'dlr':
            // Handle delivery report
            $response = [
                'status' => 'success',
                'message' => 'Delivery report received',
                'data' => [
                    'message_id' => $message_id,
                    'phone' => $phone,
                    'delivery_status' => $delivery_status
                ]
            ];
            
            // You can store this in database if needed
            // Example: UPDATE sms_logs SET delivery_status = ? WHERE message_id = ?
            
            break;
            
        case 'incoming_sms':
        case 'mo':
            // Handle incoming SMS (Mobile Originated)
            $response = [
                'status' => 'success',
                'message' => 'Incoming SMS received',
                'data' => [
                    'from' => $phone,
                    'message' => $message,
                    'timestamp' => $timestamp
                ]
            ];
            
            // You can process incoming SMS here
            // Example: Auto-reply, save to database, trigger chatbot, etc.
            
            break;
            
        default:
            // Generic callback handler
            $response = [
                'status' => 'success',
                'message' => 'Callback received',
                'callback_type' => $callback_type,
                'data' => $data
            ];
    }
    
    // Log the processed callback
    file_put_contents($log_file, "PROCESSED: " . json_encode($response, JSON_PRETTY_PRINT) . "\n---\n", FILE_APPEND);
    
    // Send response back to HDEV
    echo json_encode($response);
    exit;
}

// Handle other methods
http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed'
]);
?>

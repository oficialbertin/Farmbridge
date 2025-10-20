<?php
require_once 'menu.php';
require_once 'util.php';
require_once 'database.php';
require_once 'sms.php';

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for USSD
header('Content-type: text/plain');

try {
    // Get the USSD parameters from Africa's Talking
    $sessionId = isset($_POST["sessionId"]) ? $_POST["sessionId"] : '';
    $serviceCode = isset($_POST["serviceCode"]) ? $_POST["serviceCode"] : '';
    $phoneNumber = isset($_POST["phoneNumber"]) ? $_POST["phoneNumber"] : '';
    $text = isset($_POST["text"]) ? $_POST["text"] : '';

    // Log the incoming request for debugging
    error_log("USSD Request - SessionID: $sessionId, Phone: $phoneNumber, Text: $text");

    // Validate required parameters
    if (empty($sessionId) || empty($phoneNumber)) {
        echo "END Invalid request parameters.";
        exit;
    }

    // Format phone number
    $phoneNumber = Util::formatPhoneNumber($phoneNumber);

    // Initialize the menu handler
    $menu = new Menu($sessionId, $phoneNumber, $text);

    // Process the menu and get the response
    $response = $menu->processMenu();

    // Output the response
    echo $response;

    // Log the response for debugging
    error_log("USSD Response: $response");

} catch (Exception $e) {
    // Log the error
    error_log("USSD Error: " . $e->getMessage());
    
    // Return user-friendly error message
    echo "END " . Util::getLanguageText('error_occurred', 'en') . " Please try again later.";
}

// Clean up expired sessions periodically (1% chance)
if (rand(1, 100) === 1) {
    try {
        $db = new Database();
        $db->cleanupExpiredSessions();
    } catch (Exception $e) {
        error_log("Session cleanup failed: " . $e->getMessage());
    }
}
?>

<?php
// Test Afripay integration
require 'session_helper.php';

// Only admins can test
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

echo "<h2>Afripay Integration Test</h2>";

// Test 1: Check if afripay_secret.php exists
echo "<h3>1. Configuration Check:</h3>";
if (file_exists('afripay_secret.php')) {
    echo "<p style='color: green;'>✅ afripay_secret.php exists</p>";
    $config = include 'afripay_secret.php';
    echo "<p><strong>App ID:</strong> " . htmlspecialchars($config['app_id']) . "</p>";
    echo "<p><strong>Return URL:</strong> " . htmlspecialchars($config['return_url']) . "</p>";
    echo "<p><strong>Callback URL:</strong> " . htmlspecialchars($config['callback_url']) . "</p>";
} else {
    echo "<p style='color: red;'>❌ afripay_secret.php missing</p>";
}

// Test 2: Check callback log
echo "<h3>2. Callback Log:</h3>";
if (file_exists('afripay_callback_log.txt')) {
    echo "<p style='color: green;'>✅ Callback log exists</p>";
    $log_content = file_get_contents('afripay_callback_log.txt');
    if (strlen($log_content) > 0) {
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars($log_content);
        echo "</pre>";
    } else {
        echo "<p>No callback data received yet.</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No callback log yet (normal if no payments processed)</p>";
}

// Test 3: Test payment form
echo "<h3>3. Test Payment Form:</h3>";
echo "<form method='post' action='afripay_button.php' style='max-width: 500px; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>Amount (RWF)</label>";
echo "<input type='number' name='amount' value='1000' class='form-control' required>";
echo "</div>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>Currency</label>";
echo "<select name='currency' class='form-control'>";
echo "<option value='RWF'>RWF</option>";
echo "<option value='USD'>USD</option>";
echo "</select>";
echo "</div>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>Comment</label>";
echo "<input type='text' name='comment' value='Test Payment' class='form-control'>";
echo "</div>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>Client Token (Order ID)</label>";
echo "<input type='text' name='client_token' value='TEST_" . time() . "' class='form-control'>";
echo "</div>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>Return URL</label>";
echo "<input type='text' name='return_url' value='https://web.farmbridgeai.rw/thanks.php?order_id=TEST_" . time() . "' class='form-control'>";
echo "</div>";
echo "<button type='submit' class='btn btn-success'>Test Afripay Payment</button>";
echo "</form>";

// Test 4: Check callback endpoint
echo "<h3>4. Callback Endpoint Test:</h3>";
$callback_url = 'https://web.farmbridgeai.rw/afripay_callback.php';
echo "<p><strong>Callback URL:</strong> <a href='$callback_url' target='_blank'>$callback_url</a></p>";
echo "<p><strong>Note:</strong> This URL should be accessible by Afripay's servers for payment notifications.</p>";

// Test 5: Domain check
echo "<h3>5. Domain Configuration:</h3>";
$current_domain = $_SERVER['HTTP_HOST'] ?? 'unknown';
echo "<p><strong>Current Domain:</strong> $current_domain</p>";
echo "<p><strong>HTTPS:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Server IP:</strong> " . ($_SERVER['SERVER_ADDR'] ?? 'unknown') . "</p>";

echo "<hr>";
echo "<p><a href='admin.php'>← Back to Admin Dashboard</a></p>";
echo "<p><strong>Note:</strong> Delete this file after testing is complete.</p>";
?>

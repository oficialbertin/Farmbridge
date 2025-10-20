<?php
// Fix payment status issue and update database schema
require 'db.php';
require 'session_helper.php';

// Only admins can run this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

echo "<h2>Payment Status Fix & Database Schema Update</h2>";

// Step 1: Add PIN column to users table if it doesn't exist
echo "<h3>1. Database Schema Update:</h3>";
try {
    // Check if PIN column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'pin'");
    if ($result->num_rows === 0) {
        // Add PIN column
        $conn->query("ALTER TABLE users ADD COLUMN pin VARCHAR(255) NULL AFTER password");
        echo "<p style='color: green;'>✅ Added PIN column to users table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ PIN column already exists</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error updating schema: " . $e->getMessage() . "</p>";
}

// Step 2: Check current payment status issues
echo "<h3>2. Payment Status Check:</h3>";
try {
    // Check pending payments
    $result = $conn->query("SELECT p.*, o.status as order_status FROM payments p LEFT JOIN orders o ON p.order_id = o.id WHERE p.status = 'pending' ORDER BY p.id DESC LIMIT 10");
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Found " . $result->num_rows . " pending payments:</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
        echo "<tr><th>Payment ID</th><th>Order ID</th><th>Amount</th><th>Status</th><th>Order Status</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['order_id']}</td>";
            echo "<td>{$row['amount']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['order_status']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ No pending payments found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking payments: " . $e->getMessage() . "</p>";
}

// Step 3: Check callback log
echo "<h3>3. Callback Log Check:</h3>";
if (file_exists('afripay_callback_log.txt')) {
    $log_content = file_get_contents('afripay_callback_log.txt');
    if (strlen($log_content) > 0) {
        echo "<p style='color: green;'>✅ Callback log exists</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(substr($log_content, -2000)); // Show last 2000 characters
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Callback log is empty</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No callback log file found</p>";
}

// Step 4: Test callback endpoint
echo "<h3>4. Callback Endpoint Test:</h3>";
$callback_url = 'https://web.farmbridgeai.rw/afripay_callback.php';
echo "<p><strong>Callback URL:</strong> <a href='$callback_url' target='_blank'>$callback_url</a></p>";

// Test callback with sample data
if (isset($_POST['test_callback'])) {
    echo "<p style='color: blue;'>🧪 Testing callback endpoint...</p>";
    
    $test_data = [
        'status' => 'success',
        'amount' => '1000',
        'currency' => 'RWF',
        'transaction_ref' => 'TEST_' . time(),
        'payment_method' => 'MTN_MOMO',
        'client_token' => '1' // Use existing order ID
    ];
    
    // Send POST request to callback
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $callback_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $http_code</p>";
    echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
    
    if ($http_code === 200) {
        echo "<p style='color: green;'>✅ Callback endpoint is working</p>";
    } else {
        echo "<p style='color: red;'>❌ Callback endpoint returned error</p>";
    }
}

echo "<form method='post' style='margin: 20px 0;'>";
echo "<button type='submit' name='test_callback' class='btn btn-primary'>Test Callback Endpoint</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='admin.php'>← Back to Admin Dashboard</a></p>";
echo "<p><strong>Note:</strong> Delete this file after fixing the issues.</p>";
?>

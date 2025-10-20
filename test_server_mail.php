<?php
// Test server mail configuration
require 'session_helper.php';

// Only admins can test
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

echo "<h2>Server Mail Configuration Test</h2>";

// Test 1: Check if mail function exists
echo "<h3>1. PHP Mail Function</h3>";
if (function_exists('mail')) {
    echo "<p style='color: green;'>✅ PHP mail() function is available</p>";
} else {
    echo "<p style='color: red;'>❌ PHP mail() function is not available</p>";
}

// Test 2: Check mail configuration
echo "<h3>2. Mail Configuration</h3>";
$sendmail_path = ini_get('sendmail_path');
$smtp_host = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');
$sendmail_from = ini_get('sendmail_from');

echo "<p><strong>Sendmail Path:</strong> " . ($sendmail_path ?: 'Not set') . "</p>";
echo "<p><strong>SMTP Host:</strong> " . ($smtp_host ?: 'Not set') . "</p>";
echo "<p><strong>SMTP Port:</strong> " . ($smtp_port ?: 'Not set') . "</p>";
echo "<p><strong>Sendmail From:</strong> " . ($sendmail_from ?: 'Not set') . "</p>";

// Test 3: Check if we can create a socket
echo "<h3>3. Network Connectivity</h3>";
$test_hosts = [
    'smtp.gmail.com:587',
    'smtp.gmail.com:465', 
    'smtp.gmail.com:25'
];

foreach ($test_hosts as $host) {
    list($hostname, $port) = explode(':', $host);
    $socket = @fsockopen($hostname, $port, $errno, $errstr, 5);
    if ($socket) {
        echo "<p style='color: green;'>✅ Can connect to $host</p>";
        fclose($socket);
    } else {
        echo "<p style='color: red;'>❌ Cannot connect to $host: $errstr</p>";
    }
}

// Test 4: Check PHP extensions
echo "<h3>4. PHP Extensions</h3>";
$required_extensions = ['openssl', 'sockets', 'curl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ $ext extension is loaded</p>";
    } else {
        echo "<p style='color: red;'>❌ $ext extension is not loaded</p>";
    }
}

// Test 5: Try simple mail send
echo "<h3>5. Simple Mail Test</h3>";
if (isset($_POST['test_email'])) {
    $test_email = $_POST['test_email'];
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $headers = "From: FarmBridge AI <noreply@farmbridgeai.rw>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        if (mail($test_email, 'FarmBridge AI - Server Mail Test', 'This is a test email from your server.', $headers)) {
            echo "<p style='color: green;'>✅ Test email sent successfully to $test_email</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send test email to $test_email</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid email address</p>";
    }
}

echo "<form method='post' style='margin: 20px 0;'>";
echo "<input type='email' name='test_email' placeholder='Enter your email to test' required>";
echo "<button type='submit'>Send Test Email</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='test_email.php'>← Back to SMTP Test</a></p>";
echo "<p><a href='admin.php'>← Back to Admin Dashboard</a></p>";
echo "<p><strong>Note:</strong> Delete this file after testing is complete.</p>";
?>

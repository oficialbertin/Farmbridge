<?php
// Simple Gmail authentication test
require 'email_smtp_working.php';

echo "<h2>Gmail Authentication Test</h2>";

$config = get_smtp_config();

echo "<h3>Configuration:</h3>";
echo "<p><strong>Username:</strong> " . htmlspecialchars($config['username']) . "</p>";
echo "<p><strong>Password:</strong> " . str_repeat('*', strlen($config['password'])) . " (length: " . strlen($config['password']) . ")</p>";
echo "<p><strong>Host:</strong> " . htmlspecialchars($config['host']) . "</p>";
echo "<p><strong>Port:</strong> " . htmlspecialchars($config['port']) . "</p>";
echo "<p><strong>Secure:</strong> " . htmlspecialchars($config['secure']) . "</p>";

echo "<h3>Testing Connection:</h3>";

// Test basic connection
$socket = fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
if (!$socket) {
    echo "<p style='color: red;'>❌ Connection failed: $errstr ($errno)</p>";
    exit;
}

echo "<p style='color: green;'>✅ Connected to SMTP server</p>";

// Read initial response
$response = fgets($socket, 512);
echo "<p><strong>Server response:</strong> " . htmlspecialchars(trim($response)) . "</p>";

// Send EHLO
fputs($socket, "EHLO test.com\r\n");
$response = fgets($socket, 512);
echo "<p><strong>EHLO response:</strong> " . htmlspecialchars(trim($response)) . "</p>";

// Try STARTTLS
if ($config['secure'] === 'tls') {
    fputs($socket, "STARTTLS\r\n");
    $response = fgets($socket, 512);
    echo "<p><strong>STARTTLS response:</strong> " . htmlspecialchars(trim($response)) . "</p>";
    
    if (substr($response, 0, 3) === '220' || substr($response, 0, 3) === '250') {
        if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            echo "<p style='color: green;'>✅ TLS enabled successfully</p>";
            
            // Send EHLO again after TLS
            fputs($socket, "EHLO test.com\r\n");
            $response = fgets($socket, 512);
            echo "<p><strong>EHLO after TLS:</strong> " . htmlspecialchars(trim($response)) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ TLS failed</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ STARTTLS failed</p>";
    }
}

// Try authentication
fputs($socket, "AUTH LOGIN\r\n");
$response = fgets($socket, 512);
echo "<p><strong>AUTH LOGIN response:</strong> " . htmlspecialchars(trim($response)) . "</p>";

if (substr($response, 0, 3) === '334') {
    // Send username
    $username_encoded = base64_encode($config['username']);
    fputs($socket, $username_encoded . "\r\n");
    $response = fgets($socket, 512);
    echo "<p><strong>Username response:</strong> " . htmlspecialchars(trim($response)) . "</p>";
    
    if (substr($response, 0, 3) === '334') {
        // Send password
        $password_encoded = base64_encode($config['password']);
        fputs($socket, $password_encoded . "\r\n");
        $response = fgets($socket, 512);
        echo "<p><strong>Password response:</strong> " . htmlspecialchars(trim($response)) . "</p>";
        
        if (substr($response, 0, 3) === '235') {
            echo "<p style='color: green;'>✅ Authentication successful!</p>";
        } else {
            echo "<p style='color: red;'>❌ Authentication failed</p>";
            echo "<p><strong>Troubleshooting:</strong></p>";
            echo "<ul>";
            echo "<li>Make sure 2-Factor Authentication is enabled on your Gmail account</li>";
            echo "<li>Generate a new App Password for 'Mail' in Google Account settings</li>";
            echo "<li>Use the App Password (not your regular Gmail password)</li>";
            echo "<li>Make sure the App Password is exactly 16 characters</li>";
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ Username rejected</p>";
    }
} else {
    echo "<p style='color: red;'>❌ AUTH LOGIN not supported</p>";
}

fputs($socket, "QUIT\r\n");
fclose($socket);

echo "<hr>";
echo "<p><a href='test_email.php'>← Back to Email Test</a></p>";
echo "<p><strong>Note:</strong> Delete this file after testing is complete.</p>";
?>

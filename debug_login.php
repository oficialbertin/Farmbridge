<?php
// Debug login page - temporary file to help diagnose login issues
require 'session_helper.php';
require 'db.php';

echo "<h2>Login Debug Information</h2>";

// Check database connection
echo "<h3>Database Connection:</h3>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
}

// Check if users table exists and show sample users
echo "<h3>Users Table Check:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Users table exists</p>";
    
    // Show sample users (without passwords)
    $users_result = $conn->query("SELECT id, name, email, phone, role FROM users LIMIT 5");
    if ($users_result && $users_result->num_rows > 0) {
        echo "<h4>Sample Users:</h4>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th></tr>";
        while ($row = $users_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No users found in database</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Users table does not exist</p>";
}

// Check session configuration
echo "<h3>Session Configuration:</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";
echo "<p><strong>Session Save Path:</strong> " . session_save_path() . "</p>";

// Check server environment
echo "<h3>Server Environment:</h3>";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";
echo "<p><strong>HTTPS:</strong> " . ($_SERVER['HTTPS'] ?? 'Not set') . "</p>";
echo "<p><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
echo "<p><strong>SERVER_NAME:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "</p>";

// Check if we're in production mode
$is_production = in_array($_SERVER['HTTP_HOST'] ?? '', [
    'web.farmbridgeai.rw',
    'www.farmbridgeai.rw',
]);
echo "<p><strong>Production Mode:</strong> " . ($is_production ? 'YES' : 'NO') . "</p>";

// Check if production config files exist
echo "<h3>Configuration Files:</h3>";
$files_to_check = ['db_production.php', 'email_production.php', 'config_production.php'];
foreach ($files_to_check as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "<p><strong>$file:</strong> " . ($exists ? '✅ Exists' : '❌ Missing') . "</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
echo "<p><strong>Note:</strong> Delete this file after debugging is complete.</p>";
?>

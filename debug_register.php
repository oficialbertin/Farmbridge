<?php
// Debug registration functionality
require 'db.php';
require 'session_helper.php';

// Only admins can debug
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

echo "<h2>Registration Debug Information</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection</h3>";
if ($conn) {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
}

// Test 2: Users Table
echo "<h3>2. Users Table</h3>";
if ($result = $conn->query("SHOW TABLES LIKE 'users'")) {
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Users table exists</p>";
        
        // Show table structure
        if ($result2 = $conn->query("DESCRIBE users")) {
            echo "<h4>Table Structure:</h4><ul>";
            while ($row = $result2->fetch_assoc()) {
                echo "<li><strong>{$row['Field']}</strong> - {$row['Type']} " . 
                     ($row['Null'] === 'YES' ? '(nullable)' : '(required)') . 
                     ($row['Default'] ? " [default: {$row['Default']}]" : '') . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ Users table does not exist</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Cannot check users table: " . $conn->error . "</p>";
}

// Test 3: Session Configuration
echo "<h3>3. Session Configuration</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";
echo "<p><strong>Session Save Path:</strong> " . session_save_path() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";

// Test 4: File Permissions
echo "<h3>4. File Permissions</h3>";
$uploads_dir = 'uploads/';
if (is_dir($uploads_dir)) {
    echo "<p style='color: green;'>✅ Uploads directory exists</p>";
    echo "<p><strong>Uploads Directory:</strong> " . realpath($uploads_dir) . "</p>";
    echo "<p><strong>Writable:</strong> " . (is_writable($uploads_dir) ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Uploads directory does not exist</p>";
    if (@mkdir($uploads_dir, 0755, true)) {
        echo "<p style='color: green;'>✅ Created uploads directory</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create uploads directory</p>";
    }
}

// Test 5: Test Registration Process
echo "<h3>5. Test Registration</h3>";
if (isset($_POST['test_register'])) {
    $test_name = 'Test User ' . time();
    $test_email = 'test' . time() . '@example.com';
    $test_phone = '250' . rand(100000000, 999999999);
    $test_password = password_hash('test123', PASSWORD_DEFAULT);
    $test_role = 'buyer';
    
    echo "<p>Testing with:</p>";
    echo "<ul>";
    echo "<li>Name: $test_name</li>";
    echo "<li>Email: $test_email</li>";
    echo "<li>Phone: $test_phone</li>";
    echo "<li>Role: $test_role</li>";
    echo "</ul>";
    
    if ($stmt = $conn->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)')) {
        $stmt->bind_param('sssss', $test_name, $test_email, $test_phone, $test_password, $test_role);
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            echo "<p style='color: green;'>✅ Test registration successful! User ID: $user_id</p>";
            
            // Clean up test user
            if ($stmt2 = $conn->prepare('DELETE FROM users WHERE id = ?')) {
                $stmt2->bind_param('i', $user_id);
                $stmt2->execute();
                $stmt2->close();
                echo "<p style='color: blue;'>🧹 Test user cleaned up</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Test registration failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>❌ Cannot prepare test registration: " . $conn->error . "</p>";
    }
}

echo "<form method='post' style='margin: 20px 0;'>";
echo "<button type='submit' name='test_register'>Test Registration Process</button>";
echo "</form>";

// Test 6: Show recent registrations
echo "<h3>6. Recent Registrations</h3>";
if ($result = $conn->query("SELECT id, name, email, phone, role, created_at FROM users ORDER BY id DESC LIMIT 5")) {
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['phone']}</td>";
            echo "<td>{$row['role']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Cannot query users: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<p><a href='register.php'>← Back to Registration</a></p>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
echo "<p><a href='admin.php'>← Back to Admin Dashboard</a></p>";
echo "<p><strong>Note:</strong> Delete this file after debugging is complete.</p>";
?>

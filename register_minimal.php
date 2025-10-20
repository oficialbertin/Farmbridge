<?php
// Minimal registration - no complex features
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'session_helper.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'buyer');
    
    echo "<h3>Debug Info:</h3>";
    echo "<p>Name: " . htmlspecialchars($name) . "</p>";
    echo "<p>Email: " . htmlspecialchars($email) . "</p>";
    echo "<p>Phone: " . htmlspecialchars($phone) . "</p>";
    echo "<p>Role: " . htmlspecialchars($role) . "</p>";
    echo "<p>Password length: " . strlen($password) . "</p>";
    
    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $msg = '<div class="alert alert-danger">All fields are required.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = '<div class="alert alert-danger">Invalid email address.</div>';
    } else {
        // Check if email exists
        if ($stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1')) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $msg = '<div class="alert alert-danger">Email already exists.</div>';
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                if ($stmt2 = $conn->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)')) {
                    $stmt2->bind_param('sssss', $name, $email, $phone, $hashed_password, $role);
                    if ($stmt2->execute()) {
                        $user_id = $conn->insert_id;
                        echo "<p style='color: green;'>✅ User created successfully! ID: $user_id</p>";
                        $msg = '<div class="alert alert-success">Registration successful! You can now login.</div>';
                    } else {
                        $msg = '<div class="alert alert-danger">Registration failed: ' . $stmt2->error . '</div>';
                    }
                    $stmt2->close();
                } else {
                    $msg = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
                }
            }
            $stmt->close();
        } else {
            $msg = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
        }
    }
}

include 'header.php';
?>
<div class="form-centered">
    <div class="form-card">
        <h2 class="mb-4 text-center text-success"><i class="bi bi-person-plus"></i> Minimal Register</h2>
        <?= $msg ?>
        <form method="post" class="mt-3">
            <div class="mb-3 input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="mb-3 input-group">
                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="mb-3 input-group">
                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                <input type="text" name="phone" class="form-control" placeholder="Phone" required>
            </div>
            <div class="mb-3 input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="mb-3 input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                <select name="role" class="form-select" required>
                    <option value="buyer">Buyer</option>
                    <option value="farmer">Farmer</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm"><i class="bi bi-person-plus"></i> Register</button>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-link text-success"><i class="bi bi-box-arrow-in-right"></i> Already have an account? Login</a>
            </div>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">

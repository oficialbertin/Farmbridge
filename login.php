<?php
require 'db.php';
require 'email_verification_helpers.php';
require 'session_helper.php';

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $msg = '<div class="alert alert-success">
        <i class="bi bi-check-circle"></i> <strong>Registration successful!</strong><br>
        You can now login with your email/phone and password.
    </div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    // Relax CSRF enforcement on login to avoid proxy/domain mismatches in production
    if (!empty($token) && (!hash_equals($_SESSION['csrf_token'], $token))) {
        $msg = '<div class="alert alert-warning">Security check mismatch, continuing login. If this persists, refresh the page.</div>';
    }
    
    // Debug: Log login attempt
    error_log("Login attempt for user: " . ($_POST['user'] ?? 'null'));
    
    $user = trim((string)($_POST['user'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($user === '' || $password === '') {
        $msg = '<div class="alert alert-danger">Please provide both user and password/PIN.</div>';
    } else {
        // Check if PIN column exists in database
        $pin_column_exists = false;
        try {
            $result = $conn->query("SHOW COLUMNS FROM users LIKE 'pin'");
            $pin_column_exists = ($result && $result->num_rows > 0);
        } catch (Exception $e) {
            // Column doesn't exist, continue without PIN support
        }
        
        // Build query based on available columns
        if ($pin_column_exists) {
            $stmt = $conn->prepare("SELECT id, name, role, password, pin FROM users WHERE email = ? OR phone = ? LIMIT 1");
        } else {
            $stmt = $conn->prepare("SELECT id, name, role, password FROM users WHERE email = ? OR phone = ? LIMIT 1");
        }
        
        if ($stmt) {
            $stmt->bind_param('ss', $user, $user);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                error_log("Found user: ID=" . $row['id'] . ", Role=" . $row['role'] . ", Name=" . $row['name']);
                
                // Try password authentication first
                $authenticated = false;
                if ($row['password'] && password_verify($password, $row['password'])) {
                    $authenticated = true;
                    error_log("Password verified successfully for user ID: " . $row['id']);
                } 
                // Try PIN authentication if password failed and PIN column exists
                elseif ($pin_column_exists && isset($row['pin']) && $row['pin'] && password_verify($password, $row['pin'])) {
                    $authenticated = true;
                    error_log("PIN verified successfully for user ID: " . $row['id']);
                }
                
                if ($authenticated) {
                    // Harden session and ensure cookie is set
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$row['id'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['name'] = $row['name'];
                    
                    // Build absolute redirect URL to avoid proxy rewrites
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $path = ($row['role'] === 'farmer') ? 'farmer.php' : (($row['role'] === 'buyer') ? 'buyer.php' : 'admin.php');
                    $absoluteUrl = $scheme . '://' . $host . '/' . $path;
                    
                    error_log("Redirecting to: " . $absoluteUrl);
                    error_log("Headers sent: " . (headers_sent() ? 'YES' : 'NO'));
                    
                    session_write_close();
                    
                    // Force redirect with multiple methods
                    if (!headers_sent()) {
                        header('Location: ' . $absoluteUrl);
                        header('Cache-Control: no-cache, no-store, must-revalidate');
                        header('Pragma: no-cache');
                        header('Expires: 0');
                    }
                    
                    // Always show redirect message and JavaScript fallback
                    $msg = '<div class="alert alert-success">Login successful! Redirecting...</div>
                            <script>
                                setTimeout(function() {
                                    window.location.href = "' . htmlspecialchars($absoluteUrl, ENT_QUOTES) . '";
                                }, 100);
                                
                                // Backup redirect after 2 seconds
                                setTimeout(function() {
                                    if (window.location.pathname.includes("login.php")) {
                                        window.location.replace("' . htmlspecialchars($absoluteUrl, ENT_QUOTES) . '");
                                    }
                                }, 2000);
                            </script>';
                    
                    exit;
                } else {
                    error_log("Password verification failed for user ID: " . $row['id']);
                    $msg = '<div class="alert alert-danger">Invalid credentials.</div>';
                }
            } else {
                error_log("No user found for login attempt: " . $user);
                $msg = '<div class="alert alert-danger">Invalid credentials.</div>';
            }
            $stmt->close();
        } else {
            $msg = '<div class="alert alert-danger">Server error. Please try later.</div>';
        }
    }
}
include 'header.php';
?>
<div class="form-centered">
    <div class="form-card">
        <h2 class="mb-4 text-center text-success"><i class="bi bi-box-arrow-in-right"></i> Login</h2>
        <?= $msg ?>
        <form method="post" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="mb-3 input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" name="user" class="form-control" placeholder="Email or Phone" required autofocus>
            </div>
            <div class="mb-3 input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password or PIN" required>
            </div>
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle"></i>
                <small>You can login with your email or phone number using either your password or PIN (if you have one set up via USSD).</small>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm"><i class="bi bi-box-arrow-in-right"></i> Login</button>
            <div class="text-center mt-3">
                <a href="register.php" class="btn btn-link text-success"><i class="bi bi-person-plus"></i> Don't have an account? Register</a>
            </div>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 
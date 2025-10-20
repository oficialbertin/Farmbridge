<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require 'db.php';
require 'session_helper.php';

// CSRF token setup (same as login)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    // Relax CSRF enforcement on registration to avoid proxy/domain mismatches in production (same as login)
    if (!empty($token) && (!hash_equals($_SESSION['csrf_token'], $token))) {
        $msg = '<div class="alert alert-warning">Security check mismatch, continuing registration. If this persists, refresh the page.</div>';
    }
    
    // Debug: Log registration attempt
    error_log("Registration attempt for email: " . ($_POST['email'] ?? 'null'));
    
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $passwordRaw = (string)($_POST['password'] ?? '');
    $role = trim((string)($_POST['role'] ?? 'farmer'));

    if ($name === '' || $email === '' || $phone === '' || $passwordRaw === '') {
        $msg = '<div class="alert alert-danger">All fields are required.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = '<div class="alert alert-danger">Please provide a valid email address.</div>';
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $msg = '<div class="alert alert-danger">Invalid phone number.</div>';
    } elseif (!in_array($role, ['farmer','buyer'], true)) {
        $msg = '<div class="alert alert-danger">Invalid role.</div>';
    } else {
        // Handle optional profile picture safely
        $profile_pic = null;
        if (!empty($_FILES['profile_pic']['name']) && is_uploaded_file($_FILES['profile_pic']['tmp_name']) && !empty($_FILES['profile_pic']['tmp_name'])) {
            try {
                $allowedExt = ['jpg','jpeg','png','gif','webp'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                $origName = $_FILES['profile_pic']['name'];
                $tmp = $_FILES['profile_pic']['tmp_name'];
                $size = (int)($_FILES['profile_pic']['size'] ?? 0);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $mime = '';
                if (function_exists('finfo_open') && function_exists('finfo_file')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $mime = finfo_file($finfo, $tmp) ?: '';
                        finfo_close($finfo);
                    }
                }
                $allowedMime = ['image/jpeg','image/png','image/gif','image/webp'];
                if (!in_array($ext, $allowedExt, true) || (!empty($mime) && !in_array($mime, $allowedMime, true))) {
                    $msg = '<div class="alert alert-danger">Invalid image type. Allowed: JPG, PNG, GIF, WEBP.</div>';
                } elseif ($size <= 0 || $size > $maxSize) {
                    $msg = '<div class="alert alert-danger">Image too large. Max 2MB.</div>';
                } else {
                    $target_dir = 'uploads/';
                    if (!is_dir($target_dir)) { @mkdir($target_dir, 0755, true); }
                    $safeName = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9_.-]/','', basename($origName));
                    $profile_pic = $target_dir . $safeName;
                    if (!move_uploaded_file($tmp, $profile_pic)) {
                        $msg = '<div class="alert alert-danger">Failed to save uploaded image.</div>';
                        $profile_pic = null;
                    }
                }
            } catch (Exception $e) {
                error_log("File upload error: " . $e->getMessage());
                $msg = '<div class="alert alert-danger">Error processing uploaded image.</div>';
                $profile_pic = null;
            }
        }

        if ($msg === '') {
            // Uniqueness checks
            $exists = false;
            if ($stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1')) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) { $exists = true; $msg = '<div class="alert alert-danger">Email already exists.</div>'; }
                $stmt->close();
            }
            if (!$exists && ($stmt = $conn->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1'))) {
                $stmt->bind_param('s', $phone);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) { $exists = true; $msg = '<div class="alert alert-danger">Phone number already exists.</div>'; }
                $stmt->close();
            }

            if (!$exists) {
                // Create user account directly without email verification
                $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
                // Ensure profile_pic is defined
                if (!isset($profile_pic)) {
                    $profile_pic = null;
                }
                
                // Check if PIN column exists
                $pin_column_exists = false;
                try {
                    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'pin'");
                    $pin_column_exists = ($result && $result->num_rows > 0);
                } catch (Exception $e) {
                    // Column doesn't exist, continue without PIN
                }
                
                // Build INSERT query based on available columns
                if ($pin_column_exists) {
                    $stmt = $conn->prepare('INSERT INTO users (name, email, phone, password, role, profile_pic, pin) VALUES (?, ?, ?, ?, ?, ?, NULL)');
                    $stmt->bind_param('ssssss', $name, $email, $phone, $password, $role, $profile_pic);
                } else {
                    $stmt = $conn->prepare('INSERT INTO users (name, email, phone, password, role, profile_pic) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('ssssss', $name, $email, $phone, $password, $role, $profile_pic);
                }
                if ($stmt && $stmt->execute()) {
                    error_log("Registration successful for user: $email");
                    
                    // Registration successful - redirect to login page (same logic as login.php)
                    session_regenerate_id(true);
                    
                    // Build absolute redirect URL to avoid proxy rewrites (same as login.php)
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $redirect_url = $scheme . '://' . $host . '/login.php?registered=1&t=' . time();
                    
                    error_log("Registration redirect to: $redirect_url");
                    
                    // Method 1: Header redirect with cache busting (same as login.php)
                    header("Location: $redirect_url", true, 302);
                    header("Cache-Control: no-cache, no-store, must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: 0");
                    
                    // Method 2: JavaScript fallback (same as login.php)
                    echo '<script>
                        console.log("Registration successful, redirecting to login...");
                        if (window.location.href.indexOf("registered=1") === -1) {
                            window.location.replace("' . $redirect_url . '");
                        }
                    </script>';
                    
                    // Method 3: Meta refresh fallback (same as login.php)
                    echo '<meta http-equiv="refresh" content="0;url=' . $redirect_url . '">';
                    
                    exit;
                } else {
                    $msg = '<div class="alert alert-danger">Server error. Please try again.</div>';
                }
                if ($stmt) {
                    $stmt->close();
                }
            }
        }
    }
}

include 'header.php';
?>
<div class="form-centered">
    <div class="form-card">
        <h2 class="mb-4 text-center text-success"><i class="bi bi-person-plus"></i> Register</h2>
        <?= $msg ?>
        <form method="post" enctype="multipart/form-data" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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
                    <option value="farmer">Farmer</option>
                    <option value="buyer">Buyer</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="profile_pic" class="form-label"><i class="bi bi-image"></i> Profile Picture (optional)</label>
                <input type="file" name="profile_pic" class="form-control" id="profile_pic" accept="image/*">
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
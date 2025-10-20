<?php
require 'db.php';
require 'email_verification_helpers.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Only existing admins can create new admin accounts
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $msg = '<div class="alert alert-danger">Security check failed. Please try again.</div>';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $passwordRaw = (string)($_POST['password'] ?? '');
        $role = 'admin'; // Force admin role

        if ($name === '' || $email === '' || $phone === '' || $passwordRaw === '') {
            $msg = '<div class="alert alert-danger">All fields are required.</div>';
        } elseif (!validate_real_email($email)) {
            $msg = '<div class="alert alert-danger">Please provide a valid email address from a real email provider.</div>';
        } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
            $msg = '<div class="alert alert-danger">Invalid phone number.</div>';
        } else {
            // Handle optional profile picture safely
            $profile_pic = null;
            if (!empty($_FILES['profile_pic']['name']) && is_uploaded_file($_FILES['profile_pic']['tmp_name'])) {
                $allowedExt = ['jpg','jpeg','png','gif','webp'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                $origName = $_FILES['profile_pic']['name'];
                $tmp = $_FILES['profile_pic']['tmp_name'];
                $size = (int)($_FILES['profile_pic']['size'] ?? 0);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $tmp) : '';
                if ($finfo) { finfo_close($finfo); }
                $allowedMime = ['image/jpeg','image/png','image/gif','image/webp'];
                if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
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
                // Create admin account directly without email verification
                $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
                if ($stmt = $conn->prepare('INSERT INTO users (name, email, phone, password, role, profile_pic) VALUES (?, ?, ?, ?, ?, ?)')) {
                    $stmt->bind_param('ssssss', $name, $email, $phone, $password, $role, $profile_pic);
                    if ($stmt->execute()) {
                        $msg = '<div class="alert alert-success">Admin account created successfully! The new admin can now login with their credentials.</div>';
                    } else {
                        $msg = '<div class="alert alert-danger">Server error. Please try again.</div>';
                    }
                    $stmt->close();
                } else {
                    $msg = '<div class="alert alert-danger">Server error. Please try later.</div>';
                }
            }
            }
        }
    }
}
include 'header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="mb-4 text-center text-success"><i class="bi bi-person-plus"></i> Create Admin Account</h2>
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
                        <div class="mb-3">
                            <label for="profile_pic" class="form-label"><i class="bi bi-image"></i> Profile Picture (optional)</label>
                            <input type="file" name="profile_pic" class="form-control" id="profile_pic" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm"><i class="bi bi-person-plus"></i> Create Admin Account</button>
                        <div class="text-center mt-3">
                            <a href="admin.php" class="btn btn-link text-success"><i class="bi bi-arrow-left"></i> Back to Admin Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>


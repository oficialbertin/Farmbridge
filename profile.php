<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];
$msg = '';
// Fetch current user data
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $profile_pic = $user['profile_pic'];
    $password_sql = '';
    // Check for unique email/phone if changed
    if ($email !== $user['email']) {
        $check_email = $conn->query("SELECT id FROM users WHERE email='$email' AND id!=$user_id");
        if ($check_email && $check_email->num_rows > 0) {
            $msg = '<div class="alert alert-danger">Email already exists. Please use a different email.</div>';
        }
    }
    if ($phone !== $user['phone']) {
        $check_phone = $conn->query("SELECT id FROM users WHERE phone='$phone' AND id!=$user_id");
        if ($check_phone && $check_phone->num_rows > 0) {
            $msg = '<div class="alert alert-danger">Phone number already exists. Please use a different phone number.</div>';
        }
    }
    // Handle profile picture upload
    if (!$msg && !empty($_FILES['profile_pic']['name'])) {
        $target_dir = 'uploads/';
        if (!is_dir($target_dir)) mkdir($target_dir);
        $profile_pic = $target_dir . uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profile_pic);
    }
    // Handle password change
    if (!$msg && !empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_sql = ", password='$password'";
    }
    // Update if no validation errors
    if (!$msg) {
        $sql = "UPDATE users SET name='$name', email='$email', phone='$phone', profile_pic=" . ($profile_pic ? "'$profile_pic'" : "NULL") . "$password_sql WHERE id=$user_id";
        if ($conn->query($sql)) {
            $_SESSION['name'] = $name;
            $msg = '<div class="alert alert-success">Profile updated successfully!</div>';
            // Refresh user data
            $user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
        } else {
            $msg = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
}
include 'header.php'; ?>
<main class="container mt-5">
    <h2>My Profile</h2>
    <?= $msg ?>
    <form method="post" enctype="multipart/form-data" class="mt-4" style="max-width:500px;">
        <div class="mb-3 text-center">
            <?php if ($user['profile_pic']): ?>
                <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture" style="height:80px;width:80px;object-fit:cover;border-radius:50%;box-shadow:0 2px 8px #388e3c33;">
            <?php else: ?>
                <div style="height:80px;width:80px;display:inline-block;border-radius:50%;background:#c8e6c9;line-height:80px;font-size:2rem;color:#388e3c;">?</div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password <small>(leave blank to keep current)</small></label>
            <input type="password" name="password" class="form-control" placeholder="New Password">
        </div>
        <div class="mb-3">
            <label class="form-label">Profile Picture</label>
            <input type="file" name="profile_pic" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-success">Update Profile</button>
    </form>
</main>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css"> 
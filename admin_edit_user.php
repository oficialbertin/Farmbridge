<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php');
    exit;
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    
    $errors = [];
    
    // Validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Check if email is already taken by another user
    if (!empty($email)) {
        $email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $email_check->bind_param("si", $email, $user_id);
        $email_check->execute();
        if ($email_check->get_result()->num_rows > 0) {
            $errors[] = "Email is already taken by another user.";
        }
    }
    
    // Check if phone is already taken by another user
    $phone_check = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
    $phone_check->bind_param("si", $phone, $user_id);
    $phone_check->execute();
    if ($phone_check->get_result()->num_rows > 0) {
        $errors[] = "Phone number is already taken by another user.";
    }
    
    // Handle profile picture upload
    $profile_pic = $user['profile_pic']; // Keep existing image by default
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_extension = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } elseif ($_FILES['profile_pic']['size'] > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "Image file size must be less than 5MB.";
        } else {
            $new_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $_FILES['profile_pic']['name']);
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                $profile_pic = $upload_path;
                // Delete old image if it exists and is different
                if ($user['profile_pic'] && $user['profile_pic'] !== $profile_pic && file_exists($user['profile_pic'])) {
                    unlink($user['profile_pic']);
                }
            } else {
                $errors[] = "Error uploading image. Please try again.";
            }
        }
    }
    
    if (empty($errors)) {
        $update_stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, email = ?, phone = ?, role = ?, profile_pic = ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("sssssi", $name, $email, $phone, $role, $profile_pic, $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = "User updated successfully!";
            // Refresh user data
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Error updating user. Please try again.";
        }
    }
}

include 'header.php';
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="mb-0">Edit User</h3>
                </div>
                <div class="card-body">
                    <a href="admin.php" class="btn btn-link mb-3">&larr; Back to Admin Dashboard</a>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role">
                                        <option value="farmer" <?= $user['role'] === 'farmer' ? 'selected' : '' ?>>Farmer</option>
                                        <option value="buyer" <?= $user['role'] === 'buyer' ? 'selected' : '' ?>>Buyer</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($user['phone']) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_pic" class="form-label">Profile Picture</label>
                            <?php if ($user['profile_pic']): ?>
                                <div class="mb-2">
                                    <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Current profile picture" 
                                         class="img-thumbnail" style="max-height: 150px;">
                                    <p class="text-muted small">Current profile picture</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept="image/*">
                            <div class="form-text">Leave empty to keep current image. Max size: 5MB</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Information</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-muted"><strong>User ID:</strong> <?= $user['id'] ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted"><strong>Registered:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="admin.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
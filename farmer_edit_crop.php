<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: login.php');
    exit;
}

$farmer_id = $_SESSION['user_id'];
$crop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the crop belongs to this farmer
$stmt = $conn->prepare("SELECT * FROM crops WHERE id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $crop_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: farmer_crops.php');
    exit;
}

$crop = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $unit = trim($_POST['unit']);
    $price = (float)$_POST['price'];
    $status = $_POST['status'];
    $harvest_type = $_POST['harvest_type'];
    $estimated_harvest_date = $_POST['estimated_harvest_date'] ?: null;
    
    $errors = [];
    
    // Validation
    if (empty($name)) $errors[] = "Crop name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than 0.";
    if (empty($unit)) $errors[] = "Unit is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";
    if ($harvest_type === 'future' && empty($estimated_harvest_date)) {
        $errors[] = "Estimated harvest date is required for future harvests.";
    }
    
    // Handle image upload
    $image_path = $crop['image']; // Keep existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "Image file size must be less than 5MB.";
        } else {
            $new_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $_FILES['image']['name']);
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $upload_path;
                // Delete old image if it exists and is different
                if ($crop['image'] && $crop['image'] !== $image_path && file_exists($crop['image'])) {
                    unlink($crop['image']);
                }
            } else {
                $errors[] = "Error uploading image. Please try again.";
            }
        }
    }
    
    if (empty($errors)) {
        $update_stmt = $conn->prepare("
            UPDATE crops 
            SET name = ?, description = ?, quantity = ?, unit = ?, price = ?, 
                status = ?, harvest_type = ?, estimated_harvest_date = ?, image = ?
            WHERE id = ? AND farmer_id = ?
        ");
        $update_stmt->bind_param("ssissssssii", 
            $name, $description, $quantity, $unit, $price, 
            $status, $harvest_type, $estimated_harvest_date, $image_path, 
            $crop_id, $farmer_id
        );
        
        if ($update_stmt->execute()) {
            $success_message = "Crop updated successfully!";
            // Refresh crop data
            $stmt->execute();
            $crop = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Error updating crop. Please try again.";
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
                    <h3 class="mb-0">Edit Crop</h3>
                </div>
                <div class="card-body">
                    <a href="farmer_crops.php" class="btn btn-link mb-3">&larr; Back to My Crops</a>
                    
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
                                    <label for="name" class="form-label">Crop Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($crop['name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="available" <?= $crop['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="pending" <?= $crop['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="sold" <?= $crop['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?= htmlspecialchars($crop['description']) ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" 
                                           value="<?= $crop['quantity'] ?>" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="unit" class="form-label">Unit *</label>
                                    <select class="form-select" id="unit" name="unit" required>
                                        <option value="kg" <?= $crop['unit'] === 'kg' ? 'selected' : '' ?>>Kilograms (kg)</option>
                                        <option value="tons" <?= $crop['unit'] === 'tons' ? 'selected' : '' ?>>Tons</option>
                                        <option value="pieces" <?= $crop['unit'] === 'pieces' ? 'selected' : '' ?>>Pieces</option>
                                        <option value="bunches" <?= $crop['unit'] === 'bunches' ? 'selected' : '' ?>>Bunches</option>
                                        <option value="bags" <?= $crop['unit'] === 'bags' ? 'selected' : '' ?>>Bags</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (RWF) *</label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?= $crop['price'] ?>" min="1" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="harvest_type" class="form-label">Harvest Type</label>
                                    <select class="form-select" id="harvest_type" name="harvest_type">
                                        <option value="in_stock" <?= $crop['harvest_type'] === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                                        <option value="future" <?= $crop['harvest_type'] === 'future' ? 'selected' : '' ?>>Future Harvest</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estimated_harvest_date" class="form-label">Estimated Harvest Date</label>
                                    <input type="date" class="form-control" id="estimated_harvest_date" name="estimated_harvest_date" 
                                           value="<?= $crop['estimated_harvest_date'] ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Crop Image</label>
                            <?php if ($crop['image']): ?>
                                <div class="mb-2">
                                    <img src="<?= htmlspecialchars($crop['image']) ?>" alt="Current crop image" 
                                         class="img-thumbnail" style="max-height: 150px;">
                                    <p class="text-muted small">Current image</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Leave empty to keep current image. Max size: 5MB</div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="farmer_crops.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Crop</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('harvest_type').addEventListener('change', function() {
    const dateField = document.getElementById('estimated_harvest_date');
    if (this.value === 'future') {
        dateField.required = true;
        dateField.parentElement.style.display = 'block';
    } else {
        dateField.required = false;
        dateField.parentElement.style.display = 'block';
    }
});
</script>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css"> 
<?php 
require 'db.php'; 
require 'session_helper.php'; 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') { 
    header('Location: login.php'); 
    exit; 
}

// Handle delete crop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_crop_id'])) {
    $crop_id = (int)$_POST['delete_crop_id'];
    $farmer_id = $_SESSION['user_id'];
    
    // Verify the crop belongs to this farmer
    $stmt = $conn->prepare("SELECT id FROM crops WHERE id = ? AND farmer_id = ?");
    $stmt->bind_param("ii", $crop_id, $farmer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Check if there are any orders for this crop
        $order_check = $conn->prepare("SELECT id FROM orders WHERE crop_id = ? AND status IN ('pending', 'paid')");
        $order_check->bind_param("i", $crop_id);
        $order_check->execute();
        $order_result = $order_check->get_result();
        
        if ($order_result->num_rows > 0) {
            $error_message = "Cannot delete crop with active orders. Please complete or cancel existing orders first.";
        } else {
            // Delete the crop
            $delete_stmt = $conn->prepare("DELETE FROM crops WHERE id = ? AND farmer_id = ?");
            $delete_stmt->bind_param("ii", $crop_id, $farmer_id);
            if ($delete_stmt->execute()) {
                $success_message = "Crop deleted successfully!";
            } else {
                $error_message = "Error deleting crop. Please try again.";
            }
        }
    } else {
        $error_message = "Crop not found or you don't have permission to delete it.";
    }
}

include 'header.php'; 
?>
<main class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Your Listed Crops</h2>
        <a href="farmer_add_crop.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add New Crop
        </a>
    </div>
    
    <a href="farmer.php" class="btn btn-link mb-3">&larr; Back to Dashboard</a>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <div id="farmer-crop-list">
        <?php 
        $farmer_id = $_SESSION['user_id']; 
        $result = $conn->query("SELECT * FROM crops WHERE farmer_id = $farmer_id ORDER BY listed_at DESC"); 
        if ($result && $result->num_rows > 0) { 
            echo '<div class="row">'; 
            while ($crop = $result->fetch_assoc()) { 
                echo '<div class="col-md-4 mb-3">'; 
                echo '<div class="card h-100">'; 
                if ($crop['image']) { 
                    echo '<img src="' . htmlspecialchars($crop['image']) . '" class="card-img-top" style="height:180px;object-fit:cover;" alt="Crop Image">'; 
                } else {
                    echo '<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                          </div>';
                }
                echo '<div class="card-body d-flex flex-column">'; 
                echo '<h5 class="card-title">' . htmlspecialchars($crop['name']) . '</h5>'; 
                echo '<p class="card-text">' . htmlspecialchars($crop['description']) . '</p>'; 
                echo '<p class="card-text"><strong>Quantity:</strong> ' . $crop['quantity'] . ' ' . htmlspecialchars($crop['unit']) . '</p>'; 
                echo '<p class="card-text"><strong>Price:</strong> ' . number_format($crop['price'], 0) . ' RWF</p>'; 
                echo '<p class="card-text"><strong>Status:</strong> <span class="badge bg-' . ($crop['status'] === 'available' ? 'success' : ($crop['status'] === 'sold' ? 'danger' : 'warning')) . '">' . ucfirst(htmlspecialchars($crop['status'])) . '</span></p>'; 
                echo '<p class="card-text"><small class="text-muted">Listed: ' . date('M d, Y', strtotime($crop['listed_at'])) . '</small></p>'; 
                
                echo '<div class="mt-auto">';
                echo '<div class="btn-group w-100" role="group">';
                echo '<a href="farmer_edit_crop.php?id=' . $crop['id'] . '" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                      </a>';
                echo '<button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete(' . $crop['id'] . ', \'' . htmlspecialchars($crop['name']) . '\')">
                        <i class="bi bi-trash"></i> Delete
                      </button>';
                echo '</div>';
                echo '</div>';
                
                echo '</div></div></div>'; 
            } 
            echo '</div>'; 
        } else { 
            echo '<div class="alert alert-info">You have not listed any crops yet. <a href="farmer_add_crop.php" class="alert-link">Add your first crop</a>!</div>'; 
        } 
        ?>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="cropName"></span>"?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="delete_crop_id" id="deleteCropId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">

<script>
function confirmDelete(cropId, cropName) {
    document.getElementById('cropName').textContent = cropName;
    document.getElementById('deleteCropId').value = cropId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script> 
<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order data with related information
$stmt = $conn->prepare("
    SELECT o.*, c.name AS crop_name, c.unit, c.image, u1.name as buyer_name, u2.name as farmer_name
    FROM orders o 
    JOIN crops c ON o.crop_id = c.id 
    JOIN users u1 ON o.buyer_id = u1.id
    JOIN users u2 ON c.farmer_id = u2.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php');
    exit;
}

$order = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = (int)$_POST['quantity'];
    $total = (float)$_POST['total'];
    $status = $_POST['status'];
    $delivery_status = $_POST['delivery_status'];
    $delivery_option = $_POST['delivery_option'];
    $delivery_fee = (float)$_POST['delivery_fee'];
    $escrow_status = $_POST['escrow_status'];
    $harvest_status = $_POST['harvest_status'];
    $estimated_delivery_date = $_POST['estimated_delivery_date'] ?: null;
    $confirmation_buyer = isset($_POST['confirmation_buyer']) ? 1 : 0;
    $confirmation_farmer = isset($_POST['confirmation_farmer']) ? 1 : 0;
    $dispute_flag = isset($_POST['dispute_flag']) ? 1 : 0;
    $buyer_notes = trim($_POST['buyer_notes']);
    $farmer_notes = trim($_POST['farmer_notes']);
    
    $errors = [];
    
    // Validation
    if ($quantity <= 0) $errors[] = "Quantity must be greater than 0.";
    if ($total <= 0) $errors[] = "Total must be greater than 0.";
    if ($delivery_fee < 0) $errors[] = "Delivery fee cannot be negative.";
    
    if (empty($errors)) {
        $update_stmt = $conn->prepare("
            UPDATE orders 
            SET quantity = ?, total = ?, status = ?, delivery_status = ?, delivery_option = ?,
                delivery_fee = ?, escrow_status = ?, harvest_status = ?, estimated_delivery_date = ?,
                confirmation_buyer = ?, confirmation_farmer = ?, dispute_flag = ?, 
                buyer_notes = ?, farmer_notes = ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("idsssdsisiiissi", 
            $quantity, $total, $status, $delivery_status, $delivery_option,
            $delivery_fee, $escrow_status, $harvest_status, $estimated_delivery_date,
            $confirmation_buyer, $confirmation_farmer, $dispute_flag, 
            $buyer_notes, $farmer_notes, $order_id
        );
        
        if ($update_stmt->execute()) {
            $success_message = "Order updated successfully!";
            // Refresh order data
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Error updating order. Please try again.";
        }
    }
}

include 'header.php';
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="mb-0">Edit Order #<?= $order_id ?></h3>
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
                    
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Order Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">Quantity *</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                                   value="<?= $order['quantity'] ?>" min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="total" class="form-label">Total (RWF) *</label>
                                            <input type="number" class="form-control" id="total" name="total" 
                                                   value="<?= $order['total'] ?>" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Order Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="delivery_status" class="form-label">Delivery Status</label>
                                            <select class="form-select" id="delivery_status" name="delivery_status">
                                                <option value="pending" <?= $order['delivery_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="farmer_confirmed" <?= $order['delivery_status'] === 'farmer_confirmed' ? 'selected' : '' ?>>Farmer Confirmed</option>
                                                <option value="out_for_delivery" <?= $order['delivery_status'] === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                                <option value="delivered" <?= $order['delivery_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="completed" <?= $order['delivery_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="delivery_option" class="form-label">Delivery Option</label>
                                            <select class="form-select" id="delivery_option" name="delivery_option">
                                                <option value="buyer" <?= $order['delivery_option'] === 'buyer' ? 'selected' : '' ?>>Buyer Pickup</option>
                                                <option value="farmer" <?= $order['delivery_option'] === 'farmer' ? 'selected' : '' ?>>Farmer Delivery</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="delivery_fee" class="form-label">Delivery Fee (RWF)</label>
                                            <input type="number" class="form-control" id="delivery_fee" name="delivery_fee" 
                                                   value="<?= $order['delivery_fee'] ?>" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="escrow_status" class="form-label">Escrow Status</label>
                                            <select class="form-select" id="escrow_status" name="escrow_status">
                                                <option value="pending" <?= $order['escrow_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="released" <?= $order['escrow_status'] === 'released' ? 'selected' : '' ?>>Released</option>
                                                <option value="disputed" <?= $order['escrow_status'] === 'disputed' ? 'selected' : '' ?>>Disputed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="harvest_status" class="form-label">Harvest Status</label>
                                            <select class="form-select" id="harvest_status" name="harvest_status">
                                                <option value="not_harvested" <?= $order['harvest_status'] === 'not_harvested' ? 'selected' : '' ?>>Not Harvested</option>
                                                <option value="harvesting" <?= $order['harvest_status'] === 'harvesting' ? 'selected' : '' ?>>Harvesting</option>
                                                <option value="harvested" <?= $order['harvest_status'] === 'harvested' ? 'selected' : '' ?>>Harvested</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="estimated_delivery_date" class="form-label">Estimated Delivery Date</label>
                                    <input type="date" class="form-control" id="estimated_delivery_date" name="estimated_delivery_date" 
                                           value="<?= $order['estimated_delivery_date'] ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Confirmation & Notes</h5>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirmation_buyer" name="confirmation_buyer" 
                                               <?= $order['confirmation_buyer'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="confirmation_buyer">
                                            Buyer Confirmed Delivery
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirmation_farmer" name="confirmation_farmer" 
                                               <?= $order['confirmation_farmer'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="confirmation_farmer">
                                            Farmer Confirmed Delivery
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="dispute_flag" name="dispute_flag" 
                                               <?= $order['dispute_flag'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="dispute_flag">
                                            Dispute Flagged
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="buyer_notes" class="form-label">Buyer Notes</label>
                                    <textarea class="form-control" id="buyer_notes" name="buyer_notes" rows="3"><?= htmlspecialchars($order['buyer_notes']) ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="farmer_notes" class="form-label">Farmer Notes</label>
                                    <textarea class="form-control" id="farmer_notes" name="farmer_notes" rows="3"><?= htmlspecialchars($order['farmer_notes']) ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Order Details</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="text-muted"><strong>Order ID:</strong> #<?= $order['id'] ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="text-muted"><strong>Crop:</strong> <?= htmlspecialchars($order['crop_name']) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="text-muted"><strong>Buyer:</strong> <?= htmlspecialchars($order['buyer_name']) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="text-muted"><strong>Farmer:</strong> <?= htmlspecialchars($order['farmer_name']) ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-muted"><strong>Created:</strong> <?= date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted"><strong>Updated:</strong> <?= date('M d, Y H:i', strtotime($order['updated_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="admin.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Order</button>
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

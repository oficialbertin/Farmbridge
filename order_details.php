<?php
require 'db.php';
require 'session_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$order_id) {
    header('Location: ' . ($_SESSION['role'] === 'farmer' ? 'farmer.php' : 'buyer.php'));
    exit;
}

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, c.name as crop_name, c.image, c.description,
           buyer.name as buyer_name, buyer.phone as buyer_phone,
           farmer.name as farmer_name, farmer.phone as farmer_phone
    FROM orders o
    JOIN crops c ON o.crop_id = c.id
    JOIN users buyer ON o.buyer_id = buyer.id
    JOIN users farmer ON c.farmer_id = farmer.id
    WHERE o.id = ? AND (o.buyer_id = ? OR c.farmer_id = ?)
");
$stmt->bind_param("iii", $order_id, $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: ' . ($_SESSION['role'] === 'farmer' ? 'farmer.php' : 'buyer.php'));
    exit;
}

// Fetch order status history
$stmt = $conn->prepare("
    SELECT osh.*, u.name as changed_by_name
    FROM order_status_history osh
    JOIN users u ON osh.changed_by = u.id
    WHERE osh.order_id = ?
    ORDER BY osh.changed_at DESC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$status_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-receipt"></i> Order #<?= $order_id ?>
                    </h4>
                    <?php if (!empty($order['dispute_flag'])): ?>
                        <span class="badge bg-danger">Dispute raised</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <!-- Order Status -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h5 class="text-success"><?= htmlspecialchars($order['crop_name']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($order['description']) ?></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : ($order['status'] === 'paid' ? 'primary' : 'warning') ?> fs-6">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Order Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Order Information</h6>
                                    <div class="row">
                                        <div class="col-6"><strong>Quantity:</strong></div>
                                        <div class="col-6"><?= $order['quantity'] ?> kg</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6"><strong>Total Amount:</strong></div>
                                        <div class="col-6 text-success"><?= number_format($order['total'], 0) ?> RWF</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6"><strong>Delivery Option:</strong></div>
                                        <div class="col-6"><?= ucfirst($order['delivery_option']) ?> handles delivery</div>
                                    </div>
                                    <?php if ($order['delivery_fee'] > 0): ?>
                                    <div class="row">
                                        <div class="col-6"><strong>Delivery Fee:</strong></div>
                                        <div class="col-6"><?= number_format($order['delivery_fee'], 0) ?> RWF</div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="row">
                                        <div class="col-6"><strong>Order Date:</strong></div>
                                        <div class="col-6"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6"><strong>Estimated Delivery:</strong></div>
                                        <div class="col-6"><?= date('M d, Y', strtotime($order['estimated_delivery_date'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Contact Information</h6>
                                    <?php if ($_SESSION['role'] === 'buyer'): ?>
                                    <div class="row">
                                        <div class="col-6"><strong>Farmer:</strong></div>
                                        <div class="col-6"><?= htmlspecialchars($order['farmer_name']) ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6"><strong>Phone:</strong></div>
                                        <div class="col-6"><?= htmlspecialchars($order['farmer_phone']) ?></div>
                                    </div>
                                    <?php else: ?>
                                    <div class="row">
                                        <div class="col-6"><strong>Buyer:</strong></div>
                                        <div class="col-6"><?= htmlspecialchars($order['buyer_name']) ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6"><strong>Phone:</strong></div>
                                        <div class="col-6"><?= htmlspecialchars($order['buyer_phone']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Order Actions</h6>
                                    <?php if ($_SESSION['role'] === 'farmer' && !$order['confirmation_farmer']): ?>
                                    <form method="POST" action="update_order_status.php" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                        <input type="hidden" name="action" value="confirm_farmer">
                                        <button type="submit" class="btn btn-success me-2">
                                            <i class="bi bi-check-circle"></i> Confirm Order & Start Processing
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role'] === 'farmer' && $order['confirmation_farmer'] && $order['delivery_status'] === 'farmer_confirmed'): ?>
                                    <form method="POST" action="update_order_status.php" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                        <input type="hidden" name="action" value="out_for_delivery">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="bi bi-truck"></i> Mark as Out for Delivery
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role'] === 'buyer' && $order['delivery_status'] === 'out_for_delivery' && !$order['confirmation_buyer']): ?>
                                    <form method="POST" action="update_order_status.php" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                        <input type="hidden" name="action" value="confirm_delivery">
                                        <button type="submit" class="btn btn-success me-2">
                                            <i class="bi bi-check-circle"></i> Confirm Delivery Received
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($_SESSION['role'], ['buyer','farmer'])): ?>
                                        <?php if (empty($order['dispute_flag'])): ?>
                                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#disputeModal">
                                            <i class="bi bi-exclamation-triangle"></i> Raise Dispute
                                        </button>
                                        <div class="form-text mt-1">If you have any issue (delays, quality, quantity, damages), raise a dispute and our team will review it.</div>
                                        <?php else: ?>
                                        <span class="badge bg-danger ms-2">Dispute already raised</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status History -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Order Status History</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($status_history as $status): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?= ucfirst($status['status']) ?></h6>
                                        <p class="text-muted mb-1"><?= $status['notes'] ?></p>
                                        <small class="text-muted">
                                            <?= date('M d, Y H:i', strtotime($status['changed_at'])) ?> by <?= htmlspecialchars($status['changed_by_name']) ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dispute Modal -->
<div class="modal fade" id="disputeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Raise Dispute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="raise_dispute.php">
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Dispute type</label>
                        <select name="dispute_type" class="form-select" required>
                            <option value="">Select a type...</option>
                            <option value="Delay">Delay (Not delivered on time)</option>
                            <option value="Wrong quality">Wrong quality (not as described)</option>
                            <option value="Wrong quantity">Wrong quantity</option>
                            <option value="Damaged goods">Damaged goods</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details</label>
                        <textarea name="message" class="form-control" rows="4" required 
                                  placeholder="Describe the issue clearly. Include dates, quantities, quality issues, etc."></textarea>
                        <div class="form-text">Provide clear details to help us resolve it faster.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Submit Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #28a745;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #28a745;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -29px;
    top: 17px;
    width: 2px;
    height: calc(100% + 10px);
    background: #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #28a745;
}
</style>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-open dispute modal if URL contains #raiseDispute
if (window.location.hash === '#raiseDispute') {
    window.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('disputeModal'));
        modal.show();
    });
}
</script>
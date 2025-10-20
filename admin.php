<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user_id'])) {
        $user_id = (int)$_POST['delete_user_id'];
        if ($user_id != $_SESSION['user_id']) {
            // Check if user has associated data
            $check_orders = $conn->prepare("SELECT id FROM orders WHERE buyer_id = ? OR farmer_id IN (SELECT id FROM crops WHERE farmer_id = ?)");
            $check_orders->bind_param("ii", $user_id, $user_id);
            $check_orders->execute();
            $order_result = $check_orders->get_result();
            
            if ($order_result->num_rows > 0) {
                $error_message = "Cannot delete user with existing orders. Please handle orders first.";
            } else {
                $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $delete_stmt->bind_param("i", $user_id);
                if ($delete_stmt->execute()) {
                    $success_message = "User deleted successfully!";
                } else {
                    $error_message = "Error deleting user. Please try again.";
                }
            }
        } else {
            $error_message = "You cannot delete your own account.";
        }
    } elseif (isset($_POST['delete_crop_id'])) {
        $crop_id = (int)$_POST['delete_crop_id'];
        
        // Check if crop has orders
        $order_check = $conn->prepare("SELECT id FROM orders WHERE crop_id = ?");
        $order_check->bind_param("i", $crop_id);
        $order_check->execute();
        $order_result = $order_check->get_result();
        
        if ($order_result->num_rows > 0) {
            $error_message = "Cannot delete crop with existing orders. Please handle orders first.";
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM crops WHERE id = ?");
            $delete_stmt->bind_param("i", $crop_id);
            if ($delete_stmt->execute()) {
                $success_message = "Crop deleted successfully!";
            } else {
                $error_message = "Error deleting crop. Please try again.";
            }
        }
    } elseif (isset($_POST['delete_order_id'])) {
        $order_id = (int)$_POST['delete_order_id'];
        
        $delete_stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $delete_stmt->bind_param("i", $order_id);
        if ($delete_stmt->execute()) {
            $success_message = "Order deleted successfully!";
        } else {
            $error_message = "Error deleting order. Please try again.";
        }
    }
}

include 'header.php';
?>
<main class="container mt-5">
    <div class="mb-4">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>User Management</h2>
        <div class="d-flex gap-2">
            <a href="test_email.php" class="btn btn-outline-info"><i class="bi bi-envelope-check"></i> Test Email</a>
            <a href="admin_register.php" class="btn btn-success"><i class="bi bi-person-plus"></i> Create Admin Account</a>
        </div>
    </div>
    <div id="user-list">
        <?php
        $result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive"><table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Profile Picture</th><th>Registered At</th><th>Actions</th></tr></thead><tbody>';
            while ($user = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($user['name']) . '</td>';
                echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                echo '<td>' . htmlspecialchars($user['phone']) . '</td>';
                echo '<td><span class="badge bg-' . ($user['role'] === 'admin' ? 'danger' : ($user['role'] === 'farmer' ? 'success' : 'primary')) . '">' . ucfirst(htmlspecialchars($user['role'])) . '</span></td>';
                echo '<td>';
                if ($user['profile_pic']) {
                    echo '<img src="' . htmlspecialchars($user['profile_pic']) . '" style="height:40px;width:40px;object-fit:cover;border-radius:50%;">';
                } else {
                    echo '-';
                }
                echo '</td>';
                echo '<td>' . date('M d, Y', strtotime($user['created_at'])) . '</td>';
                echo '<td>';
                echo '<div class="btn-group btn-group-sm" role="group">';
                echo '<a href="admin_edit_user.php?id=' . $user['id'] . '" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                      </a>';
                if ($user['id'] != $_SESSION['user_id']) {
                    echo '<button type="button" class="btn btn-outline-danger" onclick="confirmDeleteUser(' . $user['id'] . ', \'' . htmlspecialchars($user['name']) . '\')">
                            <i class="bi bi-trash"></i> Delete
                          </button>';
                }
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="alert alert-info">No users found.</div>';
        }
        ?>
    </div>
    <hr>
    <h2>Crop Management</h2>
    <div id="admin-crop-list">
        <?php
        $result = $conn->query("SELECT crops.*, users.name AS farmer_name FROM crops JOIN users ON crops.farmer_id = users.id ORDER BY crops.listed_at DESC");
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive"><table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Name</th><th>Farmer</th><th>Quantity</th><th>Unit</th><th>Price</th><th>Status</th><th>Image</th><th>Listed At</th><th>Actions</th></tr></thead><tbody>';
            while ($crop = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($crop['name']) . '</td>';
                echo '<td>' . htmlspecialchars($crop['farmer_name']) . '</td>';
                echo '<td>' . $crop['quantity'] . '</td>';
                echo '<td>' . htmlspecialchars($crop['unit']) . '</td>';
                echo '<td>' . number_format($crop['price'], 0) . ' RWF</td>';
                echo '<td><span class="badge bg-' . ($crop['status'] === 'available' ? 'success' : ($crop['status'] === 'sold' ? 'danger' : 'warning')) . '">' . ucfirst(htmlspecialchars($crop['status'])) . '</span></td>';
                echo '<td>';
                if ($crop['image']) {
                    echo '<img src="' . htmlspecialchars($crop['image']) . '" style="height:40px;width:40px;object-fit:cover;">';
                } else {
                    echo '-';
                }
                echo '</td>';
                echo '<td>' . date('M d, Y', strtotime($crop['listed_at'])) . '</td>';
                echo '<td>';
                echo '<div class="btn-group btn-group-sm" role="group">';
                echo '<a href="admin_edit_crop.php?id=' . $crop['id'] . '" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                      </a>';
                echo '<button type="button" class="btn btn-outline-danger" onclick="confirmDeleteCrop(' . $crop['id'] . ', \'' . htmlspecialchars($crop['name']) . '\')">
                        <i class="bi bi-trash"></i> Delete
                      </button>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="alert alert-info">No crops found.</div>';
        }
        ?>
    </div>
    <hr>
    <h2>Order Management</h2>
    <div id="admin-order-list">
        <?php
        $result = $conn->query("
            SELECT o.*, c.name AS crop_name, c.unit, u1.name as buyer_name, u2.name as farmer_name
            FROM orders o 
            JOIN crops c ON o.crop_id = c.id 
            JOIN users u1 ON o.buyer_id = u1.id
            JOIN users u2 ON c.farmer_id = u2.id
            ORDER BY o.created_at DESC
        ");
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive"><table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Order ID</th><th>Crop</th><th>Buyer</th><th>Farmer</th><th>Quantity</th><th>Total</th><th>Status</th><th>Delivery</th><th>Created At</th><th>Actions</th></tr></thead><tbody>';
            while ($order = $result->fetch_assoc()) {
                $status_color = $order['status'] === 'completed' ? 'success' : ($order['status'] === 'paid' ? 'primary' : 'warning');
                echo '<tr>';
                echo '<td>#' . $order['id'] . '</td>';
                echo '<td>' . htmlspecialchars($order['crop_name']) . '</td>';
                echo '<td>' . htmlspecialchars($order['buyer_name']) . '</td>';
                echo '<td>' . htmlspecialchars($order['farmer_name']) . '</td>';
                echo '<td>' . $order['quantity'] . ' ' . htmlspecialchars($order['unit']) . '</td>';
                echo '<td>' . number_format($order['total'], 0) . ' RWF</td>';
                echo '<td><span class="badge bg-' . $status_color . '">' . ucfirst($order['status']) . '</span></td>';
                echo '<td><span class="badge bg-info">' . ucfirst($order['delivery_status']) . '</span></td>';
                echo '<td>' . date('M d, Y', strtotime($order['created_at'])) . '</td>';
                echo '<td>';
                echo '<div class="btn-group btn-group-sm" role="group">';
                echo '<a href="admin_edit_order.php?id=' . $order['id'] . '" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                      </a>';
                echo '<button type="button" class="btn btn-outline-danger" onclick="confirmDeleteOrder(' . $order['id'] . ')">
                        <i class="bi bi-trash"></i> Delete
                      </button>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="alert alert-info">No orders found.</div>';
        }
        ?>
    </div>
    <hr>
    <h2>Reports</h2>
    <div id="report-list">
        <?php
        // KPIs
        $users = (int)($conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0);
        $crops = (int)($conn->query("SELECT COUNT(*) c FROM crops")->fetch_assoc()['c'] ?? 0);
        $orders = (int)($conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'] ?? 0);
        $revenue = (float)($conn->query("SELECT SUM(amount) s FROM payments WHERE status IN ('success','released')")->fetch_assoc()['s'] ?? 0);
        echo '<div class="row g-3 mb-3">';
        echo '<div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Users</div><div class="h4">' . $users . '</div></div></div></div>';
        echo '<div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Crops</div><div class="h4">' . $crops . '</div></div></div></div>';
        echo '<div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Orders</div><div class="h4">' . $orders . '</div></div></div></div>';
        echo '<div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Revenue (RWF)</div><div class="h4">' . number_format($revenue, 0) . '</div></div></div></div>';
        echo '</div>';

        // Top crops table
        $topCrops = $conn->query("SELECT c.name, COUNT(o.id) as orders_count, SUM(o.total) as total_value FROM orders o JOIN crops c ON o.crop_id = c.id GROUP BY c.name ORDER BY orders_count DESC LIMIT 5");
        if ($topCrops && $topCrops->num_rows > 0) {
            echo '<div class="card mb-3"><div class="card-body">';
            echo '<h5 class="card-title">Top Crops</h5>';
            echo '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Crop</th><th>Orders</th><th>Total Value (RWF)</th></tr></thead><tbody>';
            while ($r = $topCrops->fetch_assoc()) {
                echo '<tr><td>' . htmlspecialchars($r['name']) . '</td><td>' . (int)$r['orders_count'] . '</td><td>' . number_format((float)$r['total_value'], 0) . '</td></tr>';
            }
            echo '</tbody></table></div>';
            echo '</div></div>';
        }

        // Recent payments
        $recentPay = $conn->query("SELECT p.id, p.amount, p.status, p.paid_at, u.name as buyer FROM payments p JOIN orders o ON p.order_id = o.id JOIN users u ON o.buyer_id = u.id ORDER BY p.paid_at DESC LIMIT 10");
        if ($recentPay && $recentPay->num_rows > 0) {
            echo '<div class="card mb-3"><div class="card-body">';
            echo '<h5 class="card-title">Recent Payments</h5>';
            echo '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Buyer</th><th>Amount (RWF)</th><th>Status</th><th>Date</th></tr></thead><tbody>';
            while ($p = $recentPay->fetch_assoc()) {
                echo '<tr><td>#' . (int)$p['id'] . '</td><td>' . htmlspecialchars($p['buyer']) . '</td><td>' . number_format((float)$p['amount'], 0) . '</td><td>' . htmlspecialchars($p['status']) . '</td><td>' . htmlspecialchars($p['paid_at'] ?? '-') . '</td></tr>';
            }
            echo '</tbody></table></div>';
            echo '</div></div>';
        }
        ?>
    </div>
</main>

<!-- Delete Confirmation Modals -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user "<span id="userName"></span>"?</p>
                <p class="text-danger"><small>This action cannot be undone and will delete all associated data.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="delete_user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteCropModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete Crop</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete crop "<span id="cropName"></span>"?</p>
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

<div class="modal fade" id="deleteOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete order #<span id="orderId"></span>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="delete_order_id" id="deleteOrderId">
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
function confirmDeleteUser(userId, userName) {
    document.getElementById('userName').textContent = userName;
    document.getElementById('deleteUserId').value = userId;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

function confirmDeleteCrop(cropId, cropName) {
    document.getElementById('cropName').textContent = cropName;
    document.getElementById('deleteCropId').value = cropId;
    new bootstrap.Modal(document.getElementById('deleteCropModal')).show();
}

function confirmDeleteOrder(orderId) {
    document.getElementById('orderId').textContent = orderId;
    document.getElementById('deleteOrderId').value = orderId;
    new bootstrap.Modal(document.getElementById('deleteOrderModal')).show();
}
</script> 
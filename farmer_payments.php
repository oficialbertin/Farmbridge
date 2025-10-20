<?php require 'db.php'; require 'session_helper.php'; if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') { header('Location: login.php'); exit; } include 'header.php'; ?>
<main class="container mt-5">
    <h2>Payment Status</h2>
    <a href="farmer.php" class="btn btn-link mb-3">&larr; Back to Dashboard</a>
    <div id="payment-status-list">
        <?php $farmer_id = $_SESSION['user_id']; $result = $conn->query("SELECT orders.*, crops.name AS crop_name, payments.status AS payment_status FROM orders JOIN crops ON orders.crop_id = crops.id LEFT JOIN payments ON payments.order_id = orders.id WHERE crops.farmer_id = $farmer_id ORDER BY orders.created_at DESC"); if ($result && $result->num_rows > 0) { echo '<div class="table-responsive"><table class="table table-bordered table-striped">'; echo '<thead><tr><th>Crop</th><th>Quantity</th><th>Total (RWF)</th><th>Order Status</th><th>Payment Status</th><th>Ordered At</th></tr></thead><tbody>'; while ($row = $result->fetch_assoc()) { echo '<tr>'; echo '<td>' . htmlspecialchars($row['crop_name']) . '</td>'; echo '<td>' . $row['quantity'] . '</td>'; echo '<td>' . $row['total'] . '</td>'; echo '<td>' . htmlspecialchars($row['status']) . '</td>'; echo '<td>' . htmlspecialchars($row['payment_status'] ?? 'pending') . '</td>'; echo '<td>' . $row['created_at'] . '</td>'; echo '</tr>'; } echo '</tbody></table></div>'; } else { echo '<div class="alert alert-info">No orders or payments yet.</div>'; } ?>
    </div>
</main>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css"> 
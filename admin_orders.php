<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
include 'header.php';
?>
<main class="container mt-5">
    <h2>All Orders</h2>
    <a href="admin.php" class="btn btn-link mb-3">&larr; Back to Dashboard</a>
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <input type="text" id="orderSearch" class="form-control w-auto" placeholder="Search orders...">
        <select id="statusFilter" class="form-select w-auto">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="cancelled">Cancelled</option>
            <option value="completed">Completed</option>
        </select>
        <button class="btn btn-outline-success ms-auto" id="exportOrdersCsv"><i class="bi bi-download"></i> Export CSV</button>
    </div>
    <div id="orders-list">
        <?php
        $result = $conn->query("SELECT o.*, c.name AS crop_name, u1.name AS buyer_name, u2.name AS farmer_name FROM orders o JOIN crops c ON o.crop_id = c.id JOIN users u1 ON o.buyer_id = u1.id JOIN users u2 ON c.farmer_id = u2.id ORDER BY o.created_at DESC");
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive"><table class="table table-bordered table-striped" id="ordersTable">';
            echo '<thead><tr><th>Order ID</th><th>Crop</th><th>Buyer</th><th>Farmer</th><th>Quantity</th><th>Status</th><th>Total (RWF)</th><th>Ordered At</th><th>Actions</th></tr></thead><tbody>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . htmlspecialchars($row['crop_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['buyer_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['farmer_name']) . '</td>';
                echo '<td>' . $row['quantity'] . '</td>';
                echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                echo '<td>' . number_format($row['total'], 0) . '</td>';
                echo '<td>' . $row['created_at'] . '</td>';
                echo '<td>';
                echo '<form method="POST" action="admin_orders.php" style="display:inline-block;">';
                echo '<input type="hidden" name="order_id" value="' . $row['id'] . '">';
                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
                echo '<select name="status" class="form-select form-select-sm d-inline w-auto me-1">';
                foreach (["pending", "paid", "cancelled", "completed"] as $status) {
                    $selected = ($row['status'] === $status) ? 'selected' : '';
                    echo '<option value="' . $status . '" ' . $selected . '>' . ucfirst($status) . '</option>';
                }
                echo '</select>';
                echo '<button type="submit" name="update_status" class="btn btn-sm btn-success me-1">Update</button>';
                echo '</form>';
                echo '<form method="POST" action="admin_orders.php" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this order? This will also delete related payment and history records.\');">';
                echo '<input type="hidden" name="order_id" value="' . $row['id'] . '">';
                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
                echo '<button type="submit" name="delete_order" class="btn btn-sm btn-danger">Delete</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="alert alert-info">No orders found.</div>';
        }
        ?>
    </div>
</main>
<?php
// Handle status update and delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        echo '<script>alert("Security check failed"); window.location.href="admin_orders.php";</script>';
        exit;
    }
    if (isset($_POST['update_status']) && isset($_POST['status'])) {
        $status = $_POST['status'];
        if (!in_array($status, ['pending','paid','cancelled','completed'], true)) { $status = 'pending'; }
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $order_id);
        $stmt->execute();
        echo '<script>window.location.href="admin_orders.php";</script>';
        exit;
    } elseif (isset($_POST['delete_order'])) {
        $conn->begin_transaction();
        try {
            // Delete child rows first to satisfy foreign keys
            $stmt = $conn->prepare("DELETE FROM order_status_history WHERE order_id=?");
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $stmt = $conn->prepare("DELETE FROM payments WHERE order_id=?");
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            // Finally delete the order
            $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
        }
        echo '<script>window.location.href="admin_orders.php";</script>';
        exit;
    }
}
?>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<script>
// Search and filter
const searchInput = document.getElementById('orderSearch');
const statusFilter = document.getElementById('statusFilter');
const table = document.getElementById('ordersTable');
searchInput.addEventListener('input', filterTable);
statusFilter.addEventListener('change', filterTable);
function filterTable() {
    const search = searchInput.value.toLowerCase();
    const status = statusFilter.value;
    for (const row of table.tBodies[0].rows) {
        const text = row.textContent.toLowerCase();
        const rowStatus = row.cells[5].textContent.trim();
        row.style.display = (text.includes(search) && (!status || rowStatus === status)) ? '' : 'none';
    }
}
// Sortable columns
let sortCol = -1, sortAsc = true;
for (let th of table.tHead.rows[0].cells) {
    th.style.cursor = 'pointer';
    th.onclick = function() {
        let col = th.cellIndex;
        if (sortCol === col) sortAsc = !sortAsc; else { sortCol = col; sortAsc = true; }
        let rows = Array.from(table.tBodies[0].rows).filter(r => r.style.display !== 'none');
        rows.sort((a, b) => {
            let v1 = a.cells[col].textContent.trim();
            let v2 = b.cells[col].textContent.trim();
            let n1 = parseFloat(v1.replace(/[^\d.\-]/g, ''));
            let n2 = parseFloat(v2.replace(/[^\d.\-]/g, ''));
            if (!isNaN(n1) && !isNaN(n2)) { v1 = n1; v2 = n2; }
            return (v1 > v2 ? 1 : v1 < v2 ? -1 : 0) * (sortAsc ? 1 : -1);
        });
        for (let row of rows) table.tBodies[0].appendChild(row);
    };
}
// Export CSV
function tableToCsv(table) {
    let csv = [];
    for (const row of table.rows) {
        let rowData = [];
        for (const cell of row.cells) {
            rowData.push('"' + cell.textContent.replace(/"/g, '""') + '"');
        }
        csv.push(rowData.join(','));
    }
    return csv.join('\n');
}
document.getElementById('exportOrdersCsv').onclick = function() {
    let visibleRows = Array.from(table.tBodies[0].rows).filter(r => r.style.display !== 'none');
    let csv = tableToCsv({
        rows: [table.tHead.rows[0], ...visibleRows]
    });
    let blob = new Blob([csv], {type: 'text/csv'});
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'orders.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};
</script>
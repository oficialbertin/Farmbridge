<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'header.php';
?>
<main class="container mt-5">
    <h2>Crop Management</h2>
    <a href="admin.php" class="btn btn-link mb-3">&larr; Back to Dashboard</a>
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <input type="text" id="cropSearch" class="form-control w-auto" placeholder="Search crops...">
        <select id="statusFilter" class="form-select w-auto">
            <option value="">All Statuses</option>
            <option value="available">Available</option>
            <option value="sold">Sold</option>
            <option value="pending">Pending</option>
        </select>
        <button class="btn btn-outline-success ms-auto" id="exportCropsCsv"><i class="bi bi-download"></i> Export CSV</button>
    </div>
    <div id="admin-crop-list">
        <?php
        $result = $conn->query("SELECT crops.*, users.name AS farmer_name FROM crops JOIN users ON crops.farmer_id = users.id ORDER BY crops.listed_at DESC");
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive"><table class="table table-bordered table-striped" id="cropsTable">';
            echo '<thead><tr><th>Name</th><th>Farmer</th><th>Quantity</th><th>Unit</th><th>Price</th><th>Status</th><th>Image</th><th>Listed At</th><th>Actions</th></tr></thead><tbody>';
            while ($crop = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($crop['name']) . '</td>';
                echo '<td>' . htmlspecialchars($crop['farmer_name']) . '</td>';
                echo '<td>' . $crop['quantity'] . '</td>';
                echo '<td>' . htmlspecialchars($crop['unit']) . '</td>';
                echo '<td>' . number_format($crop['price'], 0) . ' RWF</td>';
                echo '<td>' . htmlspecialchars($crop['status']) . '</td>';
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
                echo '<a href="admin_edit_crop.php?id=' . $crop['id'] . '" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>';
                echo '<form method="POST" action="admin_crops.php" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this crop?\');">';
                echo '<input type="hidden" name="delete_crop_id" value="' . $crop['id'] . '">';
                echo '<button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>';
                echo '</form>';
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
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<script>
const searchInput = document.getElementById('cropSearch');
const statusFilter = document.getElementById('statusFilter');
const table = document.getElementById('cropsTable');
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
document.getElementById('exportCropsCsv').onclick = function() {
    let visibleRows = Array.from(table.tBodies[0].rows).filter(r => r.style.display !== 'none');
    let csv = tableToCsv({
        rows: [table.tHead.rows[0], ...visibleRows]
    });
    let blob = new Blob([csv], {type: 'text/csv'});
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'crops.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};
</script>
<?php
// Handle crop deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_crop_id'])) {
    $crop_id = (int)$_POST['delete_crop_id'];
    $order_check = $conn->prepare("SELECT id FROM orders WHERE crop_id = ?");
    $order_check->bind_param("i", $crop_id);
    $order_check->execute();
    $order_result = $order_check->get_result();
    if ($order_result->num_rows > 0) {
        echo '<script>alert("Cannot delete crop with existing orders. Please handle orders first.");window.location.href="admin_crops.php";</script>';
        exit;
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM crops WHERE id = ?");
        $delete_stmt->bind_param("i", $crop_id);
        if ($delete_stmt->execute()) {
            echo '<script>alert("Crop deleted successfully!");window.location.href="admin_crops.php";</script>';
            exit;
        } else {
            echo '<script>alert("Error deleting crop. Please try again.");window.location.href="admin_crops.php";</script>';
            exit;
        }
    }
}
?>
<?php include 'footer.php'; ?>
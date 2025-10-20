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
    <h2>User Management</h2>
    <a href="admin.php" class="btn btn-link mb-3">&larr; Back to Dashboard</a>
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <input type="text" id="userSearch" class="form-control w-auto" placeholder="Search users...">
        <select id="roleFilter" class="form-select w-auto">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="farmer">Farmer</option>
            <option value="buyer">Buyer</option>
        </select>
        <button class="btn btn-outline-success ms-auto" id="exportUsersCsv"><i class="bi bi-download"></i> Export CSV</button>
    </div>
    <div id="user-list">
        <?php
        $result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive"><table class="table table-bordered table-striped" id="usersTable">';
            echo '<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Profile Picture</th><th>Registered At</th><th>Actions</th></tr></thead><tbody>';
            while ($user = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($user['name']) . '</td>';
                echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                echo '<td>' . htmlspecialchars($user['phone']) . '</td>';
                echo '<td>' . htmlspecialchars($user['role']) . '</td>';
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
                echo '<a href="admin_edit_user.php?id=' . $user['id'] . '" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>';
                if ($user['id'] != $_SESSION['user_id']) {
                    echo '<form method="POST" action="admin_users.php" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this user?\');">';
                    echo '<input type="hidden" name="delete_user_id" value="' . $user['id'] . '">';
                    echo '<button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>';
                    echo '</form>';
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
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<script>
const searchInput = document.getElementById('userSearch');
const roleFilter = document.getElementById('roleFilter');
const table = document.getElementById('usersTable');
searchInput.addEventListener('input', filterTable);
roleFilter.addEventListener('change', filterTable);
function filterTable() {
    const search = searchInput.value.toLowerCase();
    const role = roleFilter.value;
    for (const row of table.tBodies[0].rows) {
        const text = row.textContent.toLowerCase();
        const rowRole = row.cells[3].textContent.trim();
        row.style.display = (text.includes(search) && (!role || rowRole === role)) ? '' : 'none';
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
document.getElementById('exportUsersCsv').onclick = function() {
    let visibleRows = Array.from(table.tBodies[0].rows).filter(r => r.style.display !== 'none');
    let csv = tableToCsv({
        rows: [table.tHead.rows[0], ...visibleRows]
    });
    let blob = new Blob([csv], {type: 'text/csv'});
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'users.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};
</script>
<?php
// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $user_id = (int)$_POST['delete_user_id'];
    if ($user_id != $_SESSION['user_id']) {
        $check_orders = $conn->prepare("SELECT id FROM orders WHERE buyer_id = ? OR farmer_id IN (SELECT id FROM crops WHERE farmer_id = ?)");
        $check_orders->bind_param("ii", $user_id, $user_id);
        $check_orders->execute();
        $order_result = $check_orders->get_result();
        if ($order_result->num_rows > 0) {
            echo '<script>alert("Cannot delete user with existing orders. Please handle orders first.");window.location.href="admin_users.php";</script>';
            exit;
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_stmt->bind_param("i", $user_id);
            if ($delete_stmt->execute()) {
                echo '<script>alert("User deleted successfully!");window.location.href="admin_users.php";</script>';
                exit;
            } else {
                echo '<script>alert("Error deleting user. Please try again.");window.location.href="admin_users.php";</script>';
                exit;
            }
        }
    } else {
        echo '<script>alert("You cannot delete your own account.");window.location.href="admin_users.php";</script>';
        exit;
    }
}
?>
<?php include 'footer.php'; ?>
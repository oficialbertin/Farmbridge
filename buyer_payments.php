<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit;
}
include 'header.php';
$buyer_id = $_SESSION['user_id'];
?>
<main class="container mt-5">
    <h2>My Payments</h2>
    <a href="buyer.php" class="btn btn-link mb-3">&larr; Back to Dashboard</a>
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <input type="text" id="paymentSearch" class="form-control w-auto" placeholder="Search payments...">
        <select id="statusFilter" class="form-select w-auto">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="success">Success</option>
            <option value="failed">Failed</option>
            <option value="released">Released</option>
        </select>
        <button class="btn btn-outline-success ms-auto" id="exportPaymentsCsv"><i class="bi bi-download"></i> Export CSV</button>
    </div>
    <div id="payments-list">
        <?php
        $result = $conn->query("SELECT payments.*, orders.total AS order_total FROM payments JOIN orders ON payments.order_id = orders.id WHERE orders.buyer_id = $buyer_id ORDER BY payments.paid_at DESC");
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive"><table class="table table-bordered table-striped" id="paymentsTable">';
            echo '<thead><tr><th>Order ID</th><th>Order Total (RWF)</th><th>Payment Amount (RWF)</th><th>Status</th><th>Paid At</th></tr></thead><tbody>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['order_id'] . '</td>';
                echo '<td>' . number_format($row['order_total'], 0) . '</td>';
                echo '<td>' . number_format($row['amount'], 0) . '</td>';
                echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                echo '<td>' . $row['paid_at'] . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="alert alert-info">No payments found.</div>';
        }
        ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<script>
const searchInput = document.getElementById('paymentSearch');
const statusFilter = document.getElementById('statusFilter');
const table = document.getElementById('paymentsTable');
searchInput.addEventListener('input', filterTable);
statusFilter.addEventListener('change', filterTable);
function filterTable() {
    const search = searchInput.value.toLowerCase();
    const status = statusFilter.value;
    for (const row of table.tBodies[0].rows) {
        const text = row.textContent.toLowerCase();
        const rowStatus = row.cells[3].textContent.trim();
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
document.getElementById('exportPaymentsCsv').onclick = function() {
    let visibleRows = Array.from(table.tBodies[0].rows).filter(r => r.style.display !== 'none');
    let csv = tableToCsv({
        rows: [table.tHead.rows[0], ...visibleRows]
    });
    let blob = new Blob([csv], {type: 'text/csv'});
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'payments.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};
</script>
<?php include 'footer.php'; ?>
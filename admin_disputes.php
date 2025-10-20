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
	<h2>Dispute Management</h2>
	<a href="admin.php" class="btn btn-link mb-3">&larr; Back to Dashboard</a>
	<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
		<input type="text" id="disputeSearch" class="form-control w-auto" placeholder="Search disputes...">
		<select id="statusFilter" class="form-select w-auto">
			<option value="">All Statuses</option>
			<option value="open">Open</option>
			<option value="under_review">Under Review</option>
			<option value="resolved">Resolved</option>
			<option value="closed">Closed</option>
		</select>
		<button class="btn btn-outline-success ms-auto" id="exportDisputesCsv"><i class="bi bi-download"></i> Export CSV</button>
	</div>
	<div id="disputes-list">
		<?php
		$result = $conn->query("SELECT d.*, o.id AS order_id, o.total, u1.name AS raised_by_name, u1.role AS raised_by_role, u2.name AS resolved_by_name FROM disputes d JOIN orders o ON d.order_id = o.id JOIN users u1 ON d.raised_by = u1.id LEFT JOIN users u2 ON d.resolved_by = u2.id ORDER BY d.status = 'open' DESC, d.created_at DESC");
		if ($result && $result->num_rows > 0) {
			echo '<div class="table-responsive"><table class="table table-bordered table-striped" id="disputesTable">';
			echo '<thead><tr><th>Order ID</th><th>Raised By</th><th>Role</th><th>Reason</th><th>Status</th><th>Created At</th><th>Resolution</th><th>Actions</th></tr></thead><tbody>';
			while ($row = $result->fetch_assoc()) {
				echo '<tr>';
				echo '<td>' . $row['order_id'] . '</td>';
				echo '<td>' . htmlspecialchars($row['raised_by_name']) . '</td>';
				echo '<td>' . htmlspecialchars($row['raised_by_role']) . '</td>';
				echo '<td>' . htmlspecialchars($row['reason']) . '</td>';
				echo '<td>' . htmlspecialchars($row['status']) . '</td>';
				echo '<td>' . $row['created_at'] . '</td>';
				echo '<td>' . htmlspecialchars($row['resolution'] ?? '-') . '</td>';
				echo '<td>';
				echo '<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#resolveModal' . $row['id'] . '">Review/Resolve</button>';
				echo '</td>';
				echo '</tr>';
				// Modal for resolving dispute
				echo '<div class="modal fade" id="resolveModal' . $row['id'] . '" tabindex="-1"><div class="modal-dialog"><div class="modal-content">';
				echo '<form method="POST" action="admin_disputes.php">';
				echo '<div class="modal-header"><h5 class="modal-title">Resolve Dispute (Order #' . $row['order_id'] . ')</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>';
				echo '<div class="modal-body">';
				echo '<p><b>Raised By:</b> ' . htmlspecialchars($row['raised_by_name']) . ' (' . htmlspecialchars($row['raised_by_role']) . ')</p>';
				echo '<p><b>Reason:</b> ' . htmlspecialchars($row['reason']) . '</p>';
				echo '<div class="mb-3"><label class="form-label">Resolution/Notes</label><textarea name="resolution" class="form-control" rows="3" required>' . htmlspecialchars($row['resolution'] ?? '') . '</textarea></div>';
				echo '<input type="hidden" name="dispute_id" value="' . $row['id'] . '">';
				echo '<input type="hidden" name="order_id" value="' . $row['order_id'] . '">';
				echo '<div class="mb-3"><label class="form-label">Mark as</label><select name="status" class="form-select">';
				foreach (["resolved", "closed", "under_review"] as $status) {
					$selected = ($row['status'] === $status) ? 'selected' : '';
					echo '<option value="' . $status . '" ' . $selected . '>' . ucfirst($status) . '</option>';
				}
				echo '</select></div>';
				echo '</div>';
				echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save</button></div>';
				echo '</form></div></div></div>';
			}
			echo '</tbody></table></div>';
		} else {
			echo '<div class="alert alert-info">No disputes found.</div>';
		}
		?>
	</div>
</main>
<?php
// Handle dispute resolution form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispute_id'], $_POST['resolution'], $_POST['status'], $_POST['order_id'])) {
	$dispute_id = (int)$_POST['dispute_id'];
	$order_id = (int)$_POST['order_id'];
	$resolution = $conn->real_escape_string(trim($_POST['resolution']));
	$status = $conn->real_escape_string($_POST['status']);
	$admin_id = $_SESSION['user_id'];

	$conn->begin_transaction();
	try {
		$conn->query("UPDATE disputes SET resolution='$resolution', status='$status', resolved_by=$admin_id, resolved_at=NOW() WHERE id=$dispute_id");
		// Update order escrow based on resolution
		if ($status === 'resolved') {
			// release funds to farmer
			$conn->query("UPDATE orders SET escrow_status='released' WHERE id=$order_id");
			$stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'dispute_resolved', ?, ?)");
			$note = 'Dispute resolved; escrow released to farmer';
			$stmt->bind_param("isi", $order_id, $note, $admin_id);
			$stmt->execute();
		} elseif ($status === 'closed') {
			// refund buyer (closed in buyer favor)
			$conn->query("UPDATE orders SET escrow_status='refunded' WHERE id=$order_id");
			$stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'dispute_closed', ?, ?)");
			$note = 'Dispute closed; escrow refunded to buyer';
			$stmt->bind_param("isi", $order_id, $note, $admin_id);
			$stmt->execute();
		} else {
			$stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'dispute_under_review', ?, ?)");
			$note = 'Dispute under review';
			$stmt->bind_param("isi", $order_id, $note, $admin_id);
			$stmt->execute();
		}
		$conn->commit();
		echo '<script>window.location.href="admin_disputes.php";</script>';
		exit;
	} catch (Exception $e) {
		$conn->rollback();
		echo '<script>alert("Error updating dispute: ' . htmlspecialchars($e->getMessage()) . '"); window.location.href="admin_disputes.php";</script>';
		exit;
	}
}
?>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<script>
const searchInput = document.getElementById('disputeSearch');
const statusFilter = document.getElementById('statusFilter');
const table = document.getElementById('disputesTable');
if (table) {
	searchInput.addEventListener('input', filterTable);
	statusFilter.addEventListener('change', filterTable);
	function filterTable() {
		const search = (searchInput.value||'').toLowerCase();
		const status = (statusFilter.value||'');
		for (const row of table.tBodies[0].rows) {
			const text = row.textContent.toLowerCase();
			const rowStatus = row.cells[4].textContent.trim();
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
}
function tableToCsv(table) {
	let csv = [];
	for (const row of table.rows) {
		let rowData = [];
		for (const cell of row.cells) { rowData.push('"' + cell.textContent.replace(/"/g, '""') + '"'); }
		csv.push(rowData.join(','));
	}
	return csv.join('\n');
}
document.getElementById('exportDisputesCsv')?.addEventListener('click', function(){
	if(!table) return;
	let visibleRows = Array.from(table.tBodies[0].rows).filter(r => r.style.display !== 'none');
	let csv = tableToCsv({ rows: [table.tHead.rows[0], ...visibleRows] });
	let blob = new Blob([csv], {type: 'text/csv'});
	let url = URL.createObjectURL(blob);
	let a = document.createElement('a');
	a.href = url; a.download = 'disputes.csv'; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
});
</script>
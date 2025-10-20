<?php
require 'db.php';
require 'session_helper.php';

// Optional: restrict to admins; adjust as per your roles
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

$rows = [];
// Try to read recent orders with payment info
$sql = "SELECT o.id AS order_id, o.total, o.status AS order_status, o.escrow_status,
               p.status AS payment_status, p.payment_type, p.momo_ref,
               c.name AS crop_name, o.quantity, o.estimated_delivery_date, o.buyer_id
        FROM orders o
        LEFT JOIN payments p ON p.order_id = o.id
        JOIN crops c ON c.id = o.crop_id
        ORDER BY o.id DESC
        LIMIT 50";
$res = $conn->query($sql);
if ($res) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }

?>
<?php include 'header.php'; ?>
<main class="container mt-5">
    <h2>Recent Orders & Payments</h2>
    <p class="text-muted">Shows latest orders, payment status, and Afripay references.</p>
    <table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th>Order #</th>
				<th>Item</th>
				<th>Qty</th>
				<th>Total (RWF)</th>
				<th>Order Status</th>
				<th>Escrow</th>
				<th>Payment Status</th>
				<th>Type</th>
				<th>External Ref</th>
			</tr>
		</thead>
		<tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="text-center text-muted">No orders found.</td></tr>
			<?php else: foreach ($rows as $r): ?>
				<tr>
					<td><?php echo (int)$r['order_id']; ?></td>
					<td><?php echo htmlspecialchars($r['crop_name']); ?></td>
					<td><?php echo (int)$r['quantity']; ?></td>
					<td><?php echo number_format((float)$r['total']); ?></td>
					<td><?php echo htmlspecialchars($r['order_status']); ?></td>
					<td><?php echo htmlspecialchars($r['escrow_status']); ?></td>
					<td>
						<?php $ps = strtolower((string)$r['payment_status']);
						$cls = $ps === 'paid' ? 'paid' : ($ps === 'failed' ? 'failed' : 'pending'); ?>
						<span class="badge <?php echo $cls; ?>"><?php echo $r['payment_status'] ? htmlspecialchars($r['payment_status']) : 'pending'; ?></span>
					</td>
					<td><?php echo $r['payment_type'] ? htmlspecialchars($r['payment_type']) : '-'; ?></td>
                    <td><?php echo $r['momo_ref'] ? htmlspecialchars($r['momo_ref']) : '-'; ?></td>
				</tr>
			<?php endforeach; endif; ?>
		</tbody>
    </table>
    <p><a class="btn btn-outline-secondary" href="thanks.php">Back</a></p>
</main>
<?php include 'footer.php'; ?>



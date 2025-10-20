<?php
if (session_status() === PHP_SESSION_NONE) { require 'session_helper.php'; }
require_once 'db.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : (isset($_SESSION['order_id']) ? (int)$_SESSION['order_id'] : 0);

// Enhanced Afripay return handling - check for all possible parameters
$status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : '';
$transaction_ref = isset($_GET['transaction_ref']) ? trim((string)$_GET['transaction_ref']) : '';
$client_token = isset($_GET['client_token']) ? (int)$_GET['client_token'] : 0;
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
$currency = isset($_GET['currency']) ? trim((string)$_GET['currency']) : '';
$payment_method = isset($_GET['payment_method']) ? trim((string)$_GET['payment_method']) : '';

// Log all received parameters for debugging
error_log("Thanks page - Domain: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . ", Order ID: $orderId, Status: $status, Transaction Ref: $transaction_ref, Client Token: $client_token, Amount: $amount, Currency: $currency, Payment Method: $payment_method");

if ($orderId === 0 && $client_token > 0) { $orderId = $client_token; }

$payment_success = false;
$payment_message = '';

if ($orderId > 0 && $status === 'success' && $transaction_ref !== '') {
    // Enhanced payment processing - mirror afripay_callback.php logic
    try {
        $conn->begin_transaction();
        
        // Update payments table
        $payTbl = $conn->query("SHOW TABLES LIKE 'payments'");
        if ($payTbl && $payTbl->num_rows > 0) {
            $hasExternal = false;
            $colRes = $conn->query("SHOW COLUMNS FROM payments LIKE 'external_ref'");
            if ($colRes && $colRes->num_rows > 0) { $hasExternal = true; }
            
            if ($hasExternal) {
                $stmt = $conn->prepare("UPDATE payments SET status='paid', external_ref=?, payment_type='afripay', updated_at=NOW() WHERE order_id=?");
            } else {
                $stmt = $conn->prepare("UPDATE payments SET status='paid', momo_ref=?, payment_type='afripay', updated_at=NOW() WHERE order_id=?");
            }
            $stmt->bind_param("si", $transaction_ref, $orderId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET escrow_status='funded', status='processing', updated_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $stmt->close();
        
        // Add to order status history
        $histTbl = $conn->query("SHOW TABLES LIKE 'order_status_history'");
        if ($histTbl && $histTbl->num_rows > 0) {
            $note = 'Payment received via Afripay (front-channel) - Transaction Ref: ' . $transaction_ref . 
                   ($payment_method ? ' - Payment Method: ' . $payment_method : '');
            $stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status, notes, changed_by, created_at) VALUES (?, 'processing', ?, 0, NOW())");
            $stmt->bind_param("is", $orderId, $note);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        $payment_success = true;
        $payment_message = 'Payment successful! Your order has been confirmed and is being processed.';
        
        error_log("Payment processed successfully - Order ID: $orderId, Transaction Ref: $transaction_ref");
        
    } catch (Throwable $e) {
        $conn->rollback();
        $payment_message = 'Payment received but there was an issue updating the order status. Please contact support with order ID: ' . $orderId;
        error_log("Payment processing error - Order ID: $orderId, Error: " . $e->getMessage());
    }
} elseif ($orderId > 0 && $status === 'failed') {
    $payment_message = 'Payment was not successful. Please try again or contact support if you were charged.';
} elseif ($orderId > 0) {
    $payment_message = 'Payment status is being verified. Please check your order status in a few minutes.';
} else {
    $payment_message = 'Unable to process payment information. Please contact support.';
}

// Try to load order summary for display
$order = null;
if ($orderId > 0 && isset($conn)) {
	$stmt = $conn->prepare("SELECT o.id, o.total, o.status, o.escrow_status, c.name AS crop_name, o.quantity FROM orders o JOIN crops c ON o.crop_id = c.id WHERE o.id = ?");
	$stmt->bind_param("i", $orderId);
	$stmt->execute();
	$order = $stmt->get_result()->fetch_assoc();
}

include 'header.php';
?>
<div class="container-fluid py-4">
	<div class="row justify-content-center">
		<div class="col-12 col-lg-8 col-xl-6">
			<div class="card shadow-sm border-0">
				<div class="card-header text-center py-4" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 15px 15px 0 0;">
					<?php if ($payment_success): ?>
						<i class="bi bi-check-circle-fill" style="font-size: 4rem; margin-bottom: 1rem;"></i>
						<h2 class="mb-0">Payment Successful!</h2>
						<p class="mb-0">Thank you for your order</p>
					<?php else: ?>
						<i class="bi bi-clock-history" style="font-size: 4rem; margin-bottom: 1rem;"></i>
						<h2 class="mb-0">Payment Processing</h2>
						<p class="mb-0">We're verifying your payment</p>
					<?php endif; ?>
				</div>
				
				<div class="card-body p-4">
					<div class="alert <?= $payment_success ? 'alert-success' : 'alert-info' ?> mb-4">
						<i class="bi bi-info-circle"></i>
						<?= htmlspecialchars($payment_message) ?>
					</div>
					
					<?php if ($order): ?>
						<div class="order-summary mb-4">
							<h4 class="text-success mb-3"><i class="bi bi-receipt"></i> Order Summary</h4>
							<div class="row g-3">
								<div class="col-12">
									<div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
										<div>
											<h6 class="mb-1">Order #<?= (int)$order['id'] ?></h6>
											<p class="mb-0 text-muted"><?= htmlspecialchars($order['crop_name']) ?> × <?= (int)$order['quantity'] ?></p>
										</div>
										<div class="text-end">
											<h5 class="mb-0 text-success">RWF <?= number_format((float)$order['total']) ?></h5>
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row g-2">
										<div class="col-6">
											<small class="text-muted">Order Status</small><br>
											<strong class="text-primary"><?= htmlspecialchars($order['status']) ?></strong>
										</div>
										<div class="col-6">
											<small class="text-muted">Payment Status</small><br>
											<strong class="<?= $order['escrow_status'] === 'funded' ? 'text-success' : 'text-warning' ?>">
												<?= htmlspecialchars($order['escrow_status']) ?>
											</strong>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php else: ?>
						<div class="alert alert-warning">
							<i class="bi bi-exclamation-triangle"></i>
							We could not load your order details. You can check your recent orders in your account.
						</div>
					<?php endif; ?>
					
					<?php if ($transaction_ref): ?>
						<div class="payment-details mb-4">
							<h5 class="text-success mb-3"><i class="bi bi-credit-card"></i> Payment Details</h5>
							<div class="row g-2">
								<div class="col-12">
									<small class="text-muted">Transaction Reference</small><br>
									<code class="bg-light p-2 rounded d-block"><?= htmlspecialchars($transaction_ref) ?></code>
								</div>
								<?php if ($payment_method): ?>
								<div class="col-12">
									<small class="text-muted">Payment Method</small><br>
									<strong><?= htmlspecialchars($payment_method) ?></strong>
								</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
					
					<div class="next-steps mb-4">
						<h5 class="text-success mb-3"><i class="bi bi-list-check"></i> What's Next?</h5>
						<div class="row g-3">
							<div class="col-12 col-md-6">
								<div class="d-flex align-items-center p-3 bg-light rounded">
									<i class="bi bi-truck text-success me-3" style="font-size: 2rem;"></i>
									<div>
										<h6 class="mb-1">Delivery Processing</h6>
										<small class="text-muted">We'll contact you soon about delivery details</small>
									</div>
								</div>
							</div>
							<div class="col-12 col-md-6">
								<div class="d-flex align-items-center p-3 bg-light rounded">
									<i class="bi bi-envelope text-success me-3" style="font-size: 2rem;"></i>
									<div>
										<h6 class="mb-1">Email Confirmation</h6>
										<small class="text-muted">Check your email for order confirmation</small>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div class="text-center">
						<a href="buyer.php" class="btn btn-success btn-lg me-3">
							<i class="bi bi-house"></i> Back to Dashboard
						</a>
						<a href="buyer_orders.php" class="btn btn-outline-success btn-lg">
							<i class="bi bi-bag-check"></i> View My Orders
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include 'footer.php'; ?>




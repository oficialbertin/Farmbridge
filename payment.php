<?php
require 'db.php';
require 'session_helper.php';
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Check if user is logged in and has an order
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer' || !isset($_SESSION['order_id'])) {
    header('Location: buyer.php');
    exit;
}

$order_id = $_SESSION['order_id'];
$payment_amount = $_SESSION['payment_amount'];
$momo_ref = $_SESSION['momo_ref'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, c.name as crop_name, c.image, u.name as farmer_name, u.phone as farmer_phone
    FROM orders o
    JOIN crops c ON o.crop_id = c.id
    JOIN users u ON c.farmer_id = u.id
    WHERE o.id = ? AND o.buyer_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: buyer.php');
    exit;
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-credit-card"></i> Complete Payment</h4>
                </div>
                <div class="card-body">
                    <!-- Order Summary -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <?php if ($order['image']): ?>
                                <img src="<?= htmlspecialchars($order['image']) ?>" alt="<?= htmlspecialchars($order['crop_name']) ?>" class="img-fluid rounded">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h5 class="text-success"><?= htmlspecialchars($order['crop_name']) ?></h5>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Quantity:</small><br>
                                    <strong><?= $order['quantity'] ?> kg</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Total Amount:</small><br>
                                    <strong class="text-success"><?= number_format($order['total'], 0) ?> RWF</strong>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Farmer:</small><br>
                                <strong><?= htmlspecialchars($order['farmer_name']) ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="mb-4">
                        <h5><i class="bi bi-credit-card-2-front"></i> Choose Payment Method</h5>
                        
                        <!-- Afripay Payment Button -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-success btn-lg w-100" id="afripayBtn">
                                <i class="bi bi-credit-card"></i> Pay with Afripay (Online)
                            </button>
                            <small class="text-muted d-block mt-1">Secure online payment with card or mobile money</small>
                        </div>
                        
                        <div class="text-center mb-3">
                            <span class="text-muted">OR</span>
                        </div>
                        
                        <!-- Manual MTN MoMo Instructions -->
                        <div class="alert alert-info">
                            <h6><i class="bi bi-phone"></i> Manual MTN MoMo Payment</h6>
                            <p class="mb-2">To complete your order, please follow these steps:</p>
                            <ol class="mb-0">
                                <li>Dial <strong>*182*8*1*271056#</strong> on your MTN phone</li>
                                <li>Enter the amount: <strong><?= number_format($payment_amount, 0) ?> RWF</strong></li>
                                <li>Enter your PIN to confirm</li>
                                <li>Use this reference: <strong><?= $momo_ref ?></strong></li>
                            </ol>
                        </div>
                    </div>

                    <!-- Escrow Information -->
                    <div class="alert alert-success">
                        <h6><i class="bi bi-shield-check"></i> Secure Escrow Payment</h6>
                        <p class="mb-0">
                            Your payment of <strong><?= number_format($payment_amount, 0) ?> RWF</strong> will be held securely 
                            by FarmBridge AI until you confirm delivery. This protects both you and the farmer.
                        </p>
                    </div>

                    <!-- Payment Form -->
                    <form id="paymentForm" method="POST" action="confirm_payment.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <input type="hidden" name="momo_ref" value="<?= $momo_ref ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">MTN MoMo Reference Number</label>
                            <input type="text" name="momo_confirmation" class="form-control" 
                                   placeholder="Enter the reference number from your MTN MoMo transaction" required>
                            <div class="form-text">
                                This is the confirmation number you received after completing the MTN MoMo payment.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Additional Notes (Optional)</label>
                            <textarea name="payment_notes" class="form-control" rows="2" 
                                      placeholder="Any additional notes about the payment..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> Confirm Payment
                            </button>
                            <a href="buyer.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>

                    <!-- Payment Status -->
                    <div class="mt-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Order Status</h6>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: 25%"></div>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> Awaiting Payment Confirmation
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Afripay integration
document.getElementById('afripayBtn').addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    
    // Show loading state
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Initializing Payment...';
    btn.disabled = true;
    
    // Initialize Afripay payment
    if (typeof Afripay !== 'undefined') {
        Afripay.init({
            amount: <?= $payment_amount ?>,
            currency: 'RWF',
            transaction_ref: '<?= $momo_ref ?>',
            client_token: '<?= $order_id ?>',
            callback_url: '<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/afripay_callback.php',
            success_url: '<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/thanks.php?status=success&transaction_ref=<?= $momo_ref ?>',
            cancel_url: '<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/thanks.php?status=cancelled',
            error_url: '<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/thanks.php?status=error'
        });
    } else {
        // Fallback: redirect to manual payment form
        btn.innerHTML = originalText;
        btn.disabled = false;
        alert('Afripay is not available. Please use the manual MTN MoMo payment method below.');
    }
});

// Auto-submit form after payment (for demo purposes)
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const momoRef = document.querySelector('input[name="momo_confirmation"]').value;
    if (!momoRef.trim()) {
        e.preventDefault();
        alert('Please enter the MTN MoMo reference number.');
        return;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    submitBtn.disabled = true;
});
</script>

<!-- Afripay SDK -->
<script src="https://js.afripay.co/v1/afripay.js"></script>

<?php include 'footer.php'; ?>
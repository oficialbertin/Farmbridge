<?php
require_once __DIR__ . '/afripay_helpers.php';

// Expects query/body: amount, currency (RWF|USD), comment, client_token (order id), return_url (optional)
// Renders a minimal HTML form that posts to Afripay

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = $method === 'POST' ? $_POST : $_GET;

$amount = isset($input['amount']) ? (string)$input['amount'] : '0';
$currency = isset($input['currency']) ? strtoupper(trim((string)$input['currency'])) : 'RWF';
$comment = isset($input['comment']) ? (string)$input['comment'] : '';
$clientToken = isset($input['client_token']) ? (string)$input['client_token'] : '';
$returnUrlOverride = isset($input['return_url']) ? (string)$input['return_url'] : '';

$cfg = afripay_get_config();
if ($returnUrlOverride) { $cfg['return_url'] = $returnUrlOverride; }

// Use credentials from email if not configured
if (!afripay_is_configured($cfg)) {
    // Fallback to email credentials
    $cfg['app_id'] = '29cede6e11bd5c5564d880cd5cb59d0c';
    $cfg['app_secret'] = 'JDJ5JDEwJHJqQnNm';
    $cfg['return_url'] = $returnUrlOverride ?: 'https://web.farmbridgeai.rw/thanks.php';
    
    error_log("Using fallback Afripay credentials from email");
}

if (!in_array($currency, ['RWF', 'USD'], true)) { $currency = 'RWF'; }
if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) { $amount = '0'; }

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Pay with Afripay - FarmBridge AI</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
	<style>
		body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
		.payment-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
		.payment-header { background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 15px 15px 0 0; }
		.afripay-logo { max-width: 200px; height: auto; }
		.loading-spinner { display: none; }
		.payment-amount { font-size: 2rem; font-weight: bold; color: #28a745; }
		@media (max-width: 768px) {
			.payment-card { margin: 10px; }
			.payment-amount { font-size: 1.5rem; }
		}
	</style>
</head>
<body>
	<div class="container-fluid d-flex align-items-center justify-content-center min-vh-100">
		<div class="row w-100">
			<div class="col-12 col-md-6 col-lg-4 mx-auto">
				<div class="payment-card">
					<div class="payment-header text-center p-4">
						<h3 class="mb-0"><i class="bi bi-shield-check"></i> Secure Payment</h3>
						<p class="mb-0">Powered by Afripay</p>
					</div>
					<div class="p-4 text-center">
						<div class="mb-4">
							<img src="https://www.afripay.africa/logos/afripay_logo.png" alt="Afripay" class="img-fluid mb-3" style="max-height: 60px;">
						</div>
						
						<div class="payment-amount mb-3">
							<?= number_format((float)$amount, 0) ?> RWF
						</div>
						
						<div class="mb-4">
							<p class="text-muted"><?= htmlspecialchars($comment) ?></p>
						</div>
						
						<div class="alert alert-info">
							<i class="bi bi-info-circle"></i>
							<strong>Secure Payment:</strong> Your payment is processed securely by Afripay. 
							You will be redirected to complete the payment.
						</div>
						
						<form action="https://www.afripay.africa/checkout/index.php" method="post" id="afripayform" name="afripayform">
							<input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount, ENT_QUOTES); ?>">
							<input type="hidden" name="currency" value="<?php echo htmlspecialchars($currency, ENT_QUOTES); ?>">
							<input type="hidden" name="comment" value="<?php echo htmlspecialchars($comment, ENT_QUOTES); ?>">
							<input type="hidden" name="client_token" value="<?php echo htmlspecialchars($clientToken, ENT_QUOTES); ?>">
							<input type="hidden" name="return_url" value="<?php echo htmlspecialchars($cfg['return_url'] ?: (($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') ), ENT_QUOTES); ?>">
							<input type="hidden" name="app_id" value="<?php echo htmlspecialchars($cfg['app_id'], ENT_QUOTES); ?>">
							<input type="hidden" name="app_secret" value="<?php echo htmlspecialchars($cfg['app_secret'], ENT_QUOTES); ?>">
							
							<button type="submit" class="btn btn-success btn-lg w-100 mb-3" onclick="showLoading()">
								<i class="bi bi-credit-card"></i> Pay with Afripay
							</button>
						</form>
						
						<div class="loading-spinner text-center">
							<div class="spinner-border text-success" role="status">
								<span class="visually-hidden">Loading...</span>
							</div>
							<p class="mt-2">Redirecting to payment...</p>
						</div>
						
						<div class="mt-3">
							<a href="buyer.php" class="btn btn-outline-secondary">
								<i class="bi bi-arrow-left"></i> Back to Dashboard
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<script>
		function showLoading() {
			document.querySelector('.loading-spinner').style.display = 'block';
			document.querySelector('button[type="submit"]').style.display = 'none';
		}
		
		// Auto-submit after 3 seconds if user doesn't click
		setTimeout(function() {
			if (!document.querySelector('.loading-spinner').style.display || 
				document.querySelector('.loading-spinner').style.display === 'none') {
				showLoading();
				document.getElementById('afripayform').submit();
			}
		}, 3000);
	</script>
</body>
</html>



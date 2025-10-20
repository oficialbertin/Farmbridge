<?php
require 'db.php';
require 'session_helper.php';

// Afripay POST payload: status, amount, currency, transaction_ref, payment_method, client_token
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
	exit;
}

$status = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$currency = isset($_POST['currency']) ? trim((string)$_POST['currency']) : '';
$transaction_ref = isset($_POST['transaction_ref']) ? trim((string)$_POST['transaction_ref']) : '';
$payment_method = isset($_POST['payment_method']) ? trim((string)$_POST['payment_method']) : '';
$client_token = isset($_POST['client_token']) ? trim((string)$_POST['client_token']) : '';

// Enhanced logging for debugging
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'post_data' => $_POST,
    'get_data' => $_GET
];
@file_put_contents(__DIR__ . '/afripay_callback_log.txt', json_encode($log_data) . "\n", FILE_APPEND);
error_log("Afripay Callback: " . json_encode($_POST));

if ($client_token === '') {
	echo json_encode(['success' => false, 'error' => 'missing_client_token']);
	exit;
}

$order_id = (int)$client_token;
// Basic fetch order and payment
$stmt = $conn->prepare("SELECT total FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
	echo json_encode(['success' => false, 'error' => 'order_not_found']);
	exit;
}

// Optionally: verify amount/currency matches order total (allow minor rounding)
$expected = (float)$order['total'];
if ($amount > 0 && abs($expected - $amount) > 1.0) {
	// log mismatch but continue (providers may send fees net); adjust if strict
	@file_put_contents(__DIR__ . '/afripay_callback_log.txt', date('Y-m-d H:i:s') . " amount_mismatch expected={$expected} got={$amount}\n", FILE_APPEND);
}

// Update payment and order based on status
if (strtolower($status) === 'success') {
	$conn->begin_transaction();
	try {
        // Update payments table if exists; prefer momo_ref if external_ref column is absent
        $payTbl = $conn->query("SHOW TABLES LIKE 'payments'");
        if ($payTbl && $payTbl->num_rows > 0) {
            $hasExternal = false;
            $colRes = $conn->query("SHOW COLUMNS FROM payments LIKE 'external_ref'");
            if ($colRes && $colRes->num_rows > 0) { $hasExternal = true; }
            if ($hasExternal) {
                $stmt = $conn->prepare("UPDATE payments SET status='paid', external_ref=?, payment_type='afripay' WHERE order_id=?");
            } else {
                $stmt = $conn->prepare("UPDATE payments SET status='paid', momo_ref=?, payment_type='afripay' WHERE order_id=?");
            }
            $stmt->bind_param("si", $transaction_ref, $order_id);
            $stmt->execute();
        }

		// Update order escrow_status and status
		$stmt = $conn->prepare("UPDATE orders SET escrow_status='funded', status='processing' WHERE id=?");
		$stmt->bind_param("i", $order_id);
		$stmt->execute();

		// History
		$histTbl = $conn->query("SHOW TABLES LIKE 'order_status_history'");
		if ($histTbl && $histTbl->num_rows > 0) {
			$note = 'Payment received via Afripay (' . $payment_method . ') ref ' . $transaction_ref;
			$stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'processing', ?, 0)");
			$stmt->bind_param("is", $order_id, $note);
			$stmt->execute();
		}

		$conn->commit();
		echo json_encode(['success' => true]);
		exit;
	} catch (Throwable $e) {
		$conn->rollback();
		echo json_encode(['success' => false, 'error' => 'db_error']);
		exit;
	}
}

// Failure or pending
echo json_encode(['success' => true, 'status' => $status]);




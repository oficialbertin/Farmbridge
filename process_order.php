<?php
require 'db.php';
require_once __DIR__ . '/settings_helpers.php';
require_once __DIR__ . '/email_helpers.php';
if (session_status() === PHP_SESSION_NONE) {
	require 'session_helper.php';
}

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
	header('Location: login.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: buyer.php');
	exit;
}

$buyer_id = $_SESSION['user_id'];
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
	$_SESSION['error'] = 'Security check failed. Please try again.';
	header('Location: buyer.php');
	exit;
}
$crop_id = (int)$_POST['crop_id'];
$farmer_id = (int)$_POST['farmer_id'];
$quantity = (int)$_POST['quantity'];
// Platform delivery (auto)
$delivery_option = 'buyer';

// Delivery via hierarchical IDs
$province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : 0;
$district_id = isset($_POST['district_id']) ? (int)$_POST['district_id'] : 0;
$sector_id   = isset($_POST['sector_id']) ? (int)$_POST['sector_id'] : 0;
$address_details = trim($_POST['address_details'] ?? '');
$delivery_phone  = trim($_POST['delivery_phone'] ?? '');

$buyer_notes_raw = trim($_POST['buyer_notes'] ?? '');

// Enforce minimum quantity from settings
$min_qty_setting = function_exists('settings_get_int') ? settings_get_int('min_quantity', 1) : 1;
$min_qty = max(1, (int)$min_qty_setting);
if ($quantity < $min_qty) {
	$_SESSION['error'] = "Invalid quantity selected.";
	header('Location: buyer.php');
	exit;
}
if ($province_id <= 0 || $district_id <= 0 || $sector_id <= 0 || $delivery_phone === '') {
	$_SESSION['error'] = "Please provide full delivery details (Province, District, Sector, Phone).";
	header('Location: buyer.php');
	exit;
}

// Fetch crop details
$stmt = $conn->prepare("SELECT * FROM crops WHERE id = ? AND status = 'available'");
$stmt->bind_param("i", $crop_id);
$stmt->execute();
$crop = $stmt->get_result()->fetch_assoc();

if (!$crop) {
	$_SESSION['error'] = "Crop not available.";
	header('Location: buyer.php');
	exit;
}

// Check quantity
if ($quantity > $crop['quantity']) {
	$_SESSION['error'] = "Requested quantity exceeds available stock.";
	header('Location: buyer.php');
	exit;
}

// Unit price with platform markup passed from checkout
$unit_price_with_markup = isset($_POST['unit_price_with_markup']) ? (float)$_POST['unit_price_with_markup'] : (float)$crop['price'];

// Calculate totals using settings
$product_price = $quantity * $unit_price_with_markup;
$delivery_enabled = function_exists('settings_get_bool') ? settings_get_bool('delivery_enabled', true) : true;
$delivery_mode = function_exists('settings_get') ? (settings_get('delivery_mode', 'auto') ?: 'auto') : 'auto';
if ($delivery_enabled && $delivery_mode === 'auto') {
	$base = function_exists('settings_get_float') ? settings_get_float('delivery_base', 4000.0) : 4000.0;
	$perUnit = function_exists('settings_get_float') ? settings_get_float('delivery_per_unit', 300.0) : 300.0;
	$delivery_fee = $base + ($perUnit * $quantity);
} else {
	$delivery_fee = 0.0; // manual mode: handled later by admin or set to zero at checkout
}
$total = $product_price + $delivery_fee;

// Estimated delivery date
$delivery_days = $crop['harvest_type'] === 'future' ? 7 : 3;
$estimated_delivery_date = date('Y-m-d', strtotime("+{$delivery_days} days"));

// Structured delivery note for legacy reference
$buyer_notes = trim($buyer_notes_raw) !== '' ? ($buyer_notes_raw) : '';

// Check if orders.delivery_address_id exists
$hasDeliveryAddressColumn = false;
$colRes = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_address_id'");
if ($colRes && $colRes->num_rows > 0) { $hasDeliveryAddressColumn = true; }

$conn->begin_transaction();

try {
	$delivery_address_id = null;
	// Insert address if addresses table exists
	$addrTable = $conn->query("SHOW TABLES LIKE 'addresses'");
	if ($addrTable && $addrTable->num_rows > 0) {
		$stmt = $conn->prepare("INSERT INTO addresses (province_id, district_id, sector_id, details) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("iiis", $province_id, $district_id, $sector_id, $address_details);
		$stmt->execute();
		$delivery_address_id = $conn->insert_id;
	}

	// Create order
	if ($hasDeliveryAddressColumn && $delivery_address_id) {
		$stmt = $conn->prepare("
        INSERT INTO orders (
            buyer_id, crop_id, quantity, total, delivery_option, delivery_fee,
            delivery_status, escrow_status, harvest_status, estimated_delivery_date,
            buyer_notes, status, delivery_address_id
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, 'pending', ?)
    ");
		$harvest_status = $crop['harvest_type'] === 'future' ? 'not_harvested' : 'harvested';
		$stmt->bind_param(
			"iiidsdsssi",
			$buyer_id,
			$crop_id,
			$quantity,
			$total,
			$delivery_option,
			$delivery_fee,
			$harvest_status,
			$estimated_delivery_date,
			$buyer_notes,
			$delivery_address_id
		);
	} else {
		$stmt = $conn->prepare("
        INSERT INTO orders (
            buyer_id, crop_id, quantity, total, delivery_option, delivery_fee,
            delivery_status, escrow_status, harvest_status, estimated_delivery_date,
            buyer_notes, status
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, 'pending')
    ");
		$harvest_status = $crop['harvest_type'] === 'future' ? 'not_harvested' : 'harvested';
		$stmt->bind_param(
			"iiidsdsss",
			$buyer_id,
			$crop_id,
			$quantity,
			$total,
			$delivery_option,
			$delivery_fee,
			$harvest_status,
			$estimated_delivery_date,
			$buyer_notes
		);
	}
	$stmt->execute();
	$order_id = $conn->insert_id;

	// Create payment record
	$momo_ref = 'MOMO_' . time() . '_' . $order_id;
	$stmt = $conn->prepare("
        INSERT INTO payments (order_id, momo_ref, amount, payment_type, status)
        VALUES (?, ?, ?, 'escrow', 'pending')
    ");
	$stmt->bind_param("isd", $order_id, $momo_ref, $total);
	$stmt->execute();

	// Update crop quantity
	$new_quantity = $crop['quantity'] - $quantity;
	$crop_status = $new_quantity > 0 ? 'available' : 'sold';
	$stmt = $conn->prepare("UPDATE crops SET quantity = ?, status = ? WHERE id = ?");
	$stmt->bind_param("isi", $new_quantity, $crop_status, $crop_id);
	$stmt->execute();

	// Record order status history
	$stmt = $conn->prepare("
        INSERT INTO order_status_history (order_id, status, notes, changed_by)
        VALUES (?, 'pending', 'Order created', ?)
    ");
	$stmt->bind_param("ii", $order_id, $buyer_id);
	$stmt->execute();

	$conn->commit();

	// Notify buyer via email (best-effort)
	try {
		$to = (string)($buyer['email'] ?? '');
		if ($to !== '') {
			$subject = 'Order #' . $order_id . ' placed';
			$body = '<p>Thank you for your order #' . (int)$order_id . '.</p>' .
				'<p>Total: RWF ' . number_format((float)$total) . '</p>' .
				'<p>You will be redirected to payment. We will notify you after confirmation.</p>';
			send_email($to, $subject, $body);
		}
	} catch (Throwable $e) { /* ignore email errors */ }

	// Redirect to Afripay checkout using order_id as client_token
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$return_url = $scheme . '://' . $host . '/thanks.php?order_id=' . $order_id;
	
	$query = http_build_query([
		'amount' => number_format($total, 2, '.', ''),
		'currency' => 'RWF',
		'comment' => 'FarmBridge Order #' . $order_id,
		'client_token' => (string)$order_id,
		'return_url' => $return_url,
	]);
	
	error_log("Redirecting to Afripay with order_id: $order_id, amount: $total, return_url: $return_url");
	
	// Multiple redirect methods for reliability
	header("Location: afripay_button.php?" . $query, true, 302);
	header("Cache-Control: no-cache, no-store, must-revalidate");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	// JavaScript fallback
	echo '<script>
		console.log("Redirecting to Afripay payment...");
		if (window.location.href.indexOf("afripay_button.php") === -1) {
			window.location.replace("afripay_button.php?' . $query . '");
		}
	</script>';
	
	// Meta refresh fallback
	echo '<meta http-equiv="refresh" content="0;url=afripay_button.php?' . $query . '">';
	
	exit;

} catch (Exception $e) {
	$conn->rollback();
	$_SESSION['error'] = "An error occurred while processing your order. Please try again.";
	header('Location: buyer.php');
	exit;
}
?>
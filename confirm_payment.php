<?php
require 'db.php';
require 'session_helper.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: buyer.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['error'] = 'Security check failed. Please try again.';
    header('Location: payment.php');
    exit;
}

$order_id = (int)$_POST['order_id'];
$momo_ref = $_POST['momo_ref'];
$momo_confirmation = trim($_POST['momo_confirmation']);
$payment_notes = trim($_POST['payment_notes'] ?? '');

// Validate inputs
if (empty($momo_confirmation)) {
    $_SESSION['error'] = "Please provide the MTN MoMo reference number.";
    header('Location: payment.php');
    exit;
}

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, c.name as crop_name, u.name as farmer_name, u.phone as farmer_phone
    FROM orders o
    JOIN crops c ON o.crop_id = c.id
    JOIN users u ON c.farmer_id = u.id
    WHERE o.id = ? AND o.buyer_id = ? AND o.status = 'pending'
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Order not found or already processed.";
    header('Location: buyer.php');
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Update payment record
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = 'success', paid_at = NOW() 
        WHERE order_id = ? AND momo_ref = ?
    ");
    $stmt->bind_param("is", $order_id, $momo_ref);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Payment record not found");
    }
    
    // Update order status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'paid', delivery_status = 'pending', escrow_status = 'pending'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // Record order status history
    $stmt = $conn->prepare("
        INSERT INTO order_status_history (order_id, status, notes, changed_by)
        VALUES (?, 'paid', ?, ?)
    ");
    $notes = "Payment confirmed via MTN MoMo. Ref: " . $momo_confirmation;
    if ($payment_notes) {
        $notes .= " Notes: " . $payment_notes;
    }
    $stmt->bind_param("isi", $order_id, $notes, $_SESSION['user_id']);
    $stmt->execute();
    
    // Insert into crop_sales for AI data
    $stmt = $conn->prepare("
        INSERT INTO crop_sales (crop_id, farmer_id, buyer_id, quantity, price, sale_date)
        VALUES (?, ?, ?, ?, ?, CURDATE())
    ");
    $price_per_unit = $order['total'] / $order['quantity'];
    $stmt->bind_param("iiiid", $order['crop_id'], $order['farmer_id'], $_SESSION['user_id'], $order['quantity'], $price_per_unit);
    $stmt->execute();
    
    $conn->commit();
    
    // Clear session variables
    unset($_SESSION['order_id']);
    unset($_SESSION['payment_amount']);
    unset($_SESSION['momo_ref']);
    
    // Set success message
    $_SESSION['success'] = "Payment confirmed successfully! Your order is now being processed by the farmer.";
    
    // Redirect to order details
    header('Location: order_details.php?id=' . $order_id);
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "An error occurred while confirming your payment. Please try again.";
    header('Location: payment.php');
    exit;
}
?>
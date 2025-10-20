<?php
require 'db.php';
require 'session_helper.php';

// Check if user is logged in and is buyer or farmer
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['buyer','farmer'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ($_SESSION['role'] === 'farmer' ? 'farmer.php' : 'buyer.php'));
    exit;
}

$order_id = (int)$_POST['order_id'];
$dispute_type = isset($_POST['dispute_type']) ? trim($_POST['dispute_type']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Backward compatibility: if only 'reason' is sent
$reason_input = isset($_POST['reason']) ? trim($_POST['reason']) : '';
if (!$dispute_type && !$message && $reason_input) {
    $dispute_type = 'Other';
    $message = $reason_input;
}

if (empty($dispute_type) || empty($message)) {
    $_SESSION['error'] = "Please select dispute type and provide details.";
    header('Location: order_details.php?id=' . $order_id . '#raiseDispute');
    exit;
}

$composed_reason = "Type: " . $dispute_type . " | Details: " . $message;

// Fetch order details and ensure access via buyer or crop farmer
$stmt = $conn->prepare("
    SELECT o.*, c.name as crop_name
    FROM orders o
    JOIN crops c ON o.crop_id = c.id
    WHERE o.id = ? AND (o.buyer_id = ? OR c.farmer_id = ?)
");
$stmt->bind_param("iii", $order_id, $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header('Location: ' . ($_SESSION['role'] === 'farmer' ? 'farmer.php' : 'buyer.php'));
    exit;
}

// Check if dispute already exists
$stmt = $conn->prepare("
    SELECT id FROM disputes 
    WHERE order_id = ? AND status IN ('open', 'under_review')
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = "A dispute for this order already exists.";
    header('Location: order_details.php?id=' . $order_id);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Create dispute
    $stmt = $conn->prepare("
        INSERT INTO disputes (order_id, raised_by, raised_by_role, reason, status)
        VALUES (?, ?, ?, ?, 'open')
    ");
    $role = $_SESSION['role'];
    $stmt->bind_param("iiss", $order_id, $_SESSION['user_id'], $role, $composed_reason);
    $stmt->execute();
    
    // Update order dispute flag
    $stmt = $conn->prepare("
        UPDATE orders 
        SET dispute_flag = TRUE, escrow_status = 'disputed'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // Record status history
    $stmt = $conn->prepare("
        INSERT INTO order_status_history (order_id, status, notes, changed_by)
        VALUES (?, 'dispute_raised', ?, ?)
    ");
    $notes = "Dispute raised by " . $role . ": " . $composed_reason;
    $stmt->bind_param("isi", $order_id, $notes, $_SESSION['user_id']);
    $stmt->execute();
    
    $conn->commit();
    
    $_SESSION['success'] = "Dispute raised successfully. An admin will review your case.";
    header('Location: order_details.php?id=' . $order_id);
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error raising dispute: " . $e->getMessage();
    header('Location: order_details.php?id=' . $order_id . '#raiseDispute');
    exit;
}
?>
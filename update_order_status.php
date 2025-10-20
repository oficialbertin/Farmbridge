<?php
require 'db.php';
require 'session_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ($_SESSION['role'] === 'farmer' ? 'farmer.php' : 'buyer.php'));
    exit;
}

$order_id = (int)$_POST['order_id'];
$action = $_POST['action'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, c.name as crop_name
    FROM orders o
    JOIN crops c ON o.crop_id = c.id
    WHERE o.id = ? AND (o.buyer_id = ? OR o.farmer_id = ?)
");
$stmt->bind_param("iii", $order_id, $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header('Location: ' . ($_SESSION['role'] === 'farmer' ? 'farmer.php' : 'buyer.php'));
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    $status_updated = false;
    $notes = '';
    
    switch ($action) {
        case 'confirm_farmer':
            if ($_SESSION['role'] !== 'farmer' || $order['farmer_id'] !== $_SESSION['user_id']) {
                throw new Exception("Unauthorized action");
            }
            
            if ($order['status'] !== 'paid') {
                throw new Exception("Order not paid yet");
            }
            
            // Update order status
            $stmt = $conn->prepare("
                UPDATE orders 
                SET delivery_status = 'farmer_confirmed', confirmation_farmer = TRUE, harvest_status = 'harvesting'
                WHERE id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $notes = "Farmer confirmed order and started harvesting";
            $status_updated = true;
            break;
            
        case 'out_for_delivery':
            if ($_SESSION['role'] !== 'farmer' || $order['farmer_id'] !== $_SESSION['user_id']) {
                throw new Exception("Unauthorized action");
            }
            
            if ($order['delivery_status'] !== 'farmer_confirmed') {
                throw new Exception("Order not confirmed by farmer yet");
            }
            
            // Update order status
            $stmt = $conn->prepare("
                UPDATE orders 
                SET delivery_status = 'out_for_delivery', harvest_status = 'harvested'
                WHERE id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $notes = "Order is out for delivery";
            $status_updated = true;
            break;
            
        case 'confirm_delivery':
            if ($_SESSION['role'] !== 'buyer' || $order['buyer_id'] !== $_SESSION['user_id']) {
                throw new Exception("Unauthorized action");
            }
            
            if ($order['delivery_status'] !== 'out_for_delivery') {
                throw new Exception("Order not out for delivery yet");
            }
            
            // Update order status
            $stmt = $conn->prepare("
                UPDATE orders 
                SET delivery_status = 'delivered', confirmation_buyer = TRUE, status = 'completed'
                WHERE id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            // Release escrow payment
            $stmt = $conn->prepare("
                UPDATE payments 
                SET status = 'released', released_at = NOW(), released_by = ?
                WHERE order_id = ? AND payment_type = 'escrow'
            ");
            $stmt->bind_param("ii", $_SESSION['user_id'], $order_id);
            $stmt->execute();
            
            // Update order escrow status
            $stmt = $conn->prepare("
                UPDATE orders 
                SET escrow_status = 'released'
                WHERE id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $notes = "Buyer confirmed delivery. Payment released to farmer.";
            $status_updated = true;
            break;
            
        default:
            throw new Exception("Invalid action");
    }
    
    if ($status_updated) {
        // Record status history
        $stmt = $conn->prepare("
            INSERT INTO order_status_history (order_id, status, notes, changed_by)
            VALUES (?, ?, ?, ?)
        ");
        $status = str_replace('_', ' ', $action);
        $stmt->bind_param("issi", $order_id, $status, $notes, $_SESSION['user_id']);
        $stmt->execute();
    }
    
    $conn->commit();
    
    $_SESSION['success'] = "Order status updated successfully!";
    header('Location: order_details.php?id=' . $order_id);
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
    header('Location: order_details.php?id=' . $order_id);
    exit;
}
?>
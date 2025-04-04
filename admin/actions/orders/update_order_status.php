<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Validate input parameters
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit;
}

if (!isset($_POST['status']) || empty($_POST['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Status is required']);
    exit;
}

$order_id = intval($_POST['id']);
$status = $_POST['status'];

// Validate status value
$valid_statuses = ['Pending', 'Completed', 'Cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
    exit;
}

// Check if order exists
$check_query = "SELECT * FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

$order = $result->fetch_assoc();

// Check if order is already in requested status
if ($order['status'] === $status) {
    echo json_encode(['status' => 'error', 'message' => 'Order is already in ' . $status . ' status']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Update order status
    $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('si', $status, $order_id);
    $stmt->execute();
    
    // If order is cancelled, return products to inventory
    if ($status === 'Cancelled') {
        // Get order items
        $items_query = "
            SELECT oi.product_id, oi.quantity
            FROM order_items oi
            WHERE oi.order_id = ?
        ";
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        // Return each product to inventory
        while ($item = $items_result->fetch_assoc()) {
            $update_stock_query = "
                UPDATE products
                SET stock_quantity = stock_quantity + ?
                WHERE id = ?
            ";
            $update_stmt = $conn->prepare($update_stock_query);
            $update_stmt->bind_param('ii', $item['quantity'], $item['product_id']);
            $update_stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Order status updated to ' . $status
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Error updating order: ' . $e->getMessage()]);
}
?> 
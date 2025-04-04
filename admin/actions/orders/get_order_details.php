<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit;
}

$order_id = intval($_GET['id']);

// Get order details with member information
$order_query = "
    SELECT o.*, m.fullname, m.contact
    FROM orders o
    JOIN members m ON o.user_id = m.user_id
    WHERE o.order_id = ?
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

$order = $result->fetch_assoc();

// Get order items with product details
$items_query = "
    SELECT oi.*, p.name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
";
$stmt = $conn->prepare($items_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

// Return order details and items as JSON
echo json_encode([
    'status' => 'success',
    'order' => $order,
    'items' => $items
]);
?> 
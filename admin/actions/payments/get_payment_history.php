<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if user_id is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Member ID is required']);
    exit;
}

$user_id = intval($_GET['user_id']);

// Get payment history for the member
$query = "
    SELECT p.*, m.fullname
    FROM payments p
    JOIN members m ON p.user_id = m.user_id
    WHERE p.user_id = ?
    ORDER BY p.payment_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
while ($payment = $result->fetch_assoc()) {
    $payments[] = $payment;
}

// Return payment history as JSON
echo json_encode([
    'status' => 'success',
    'payments' => $payments
]);
?> 
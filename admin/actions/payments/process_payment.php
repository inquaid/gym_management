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
    $_SESSION['error_message'] = 'Invalid request method';
    header("Location: ../../pages/payments.php");
    exit;
}

// Validate input
if (empty($_POST['user_id'])) {
    $_SESSION['error_message'] = 'Member ID is required';
    header("Location: ../../pages/payments.php");
    exit;
}

if (empty($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
    $_SESSION['error_message'] = 'Valid payment amount is required';
    header("Location: ../../pages/payments.php");
    exit;
}

if (empty($_POST['payment_method'])) {
    $_SESSION['error_message'] = 'Payment method is required';
    header("Location: ../../pages/payments.php");
    exit;
}

// Get form data
$user_id = intval($_POST['user_id']);
$amount = floatval($_POST['amount']);
$payment_method = $_POST['payment_method'];
$payment_date = date('Y-m-d H:i:s');
$payment_status = 'Success'; // Default for admin payments

// Start transaction
$conn->begin_transaction();

try {
    // Get member information and check if they exist
    $member_query = "SELECT fullname, due_amount FROM members WHERE user_id = ?";
    $stmt = $conn->prepare($member_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Member not found');
    }
    
    $member = $result->fetch_assoc();
    $current_due = $member['due_amount'];
    $member_name = $member['fullname'];
    
    // Check if payment amount is valid (not more than due amount)
    if ($amount > $current_due) {
        $_SESSION['error_message'] = 'Payment amount cannot exceed the due amount';
        header("Location: ../../pages/payments.php");
        exit;
    }
    
    // Calculate new due amount
    $new_due = $current_due - $amount;
    
    // Update member's due amount
    $update_query = "UPDATE members SET due_amount = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('di', $new_due, $user_id);
    $stmt->execute();
    
    // Record payment in payments table (order_id is NULL for membership payments)
    $payment_query = "INSERT INTO payments (user_id, payment_date, amount, payment_method, status) 
                     VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($payment_query);
    $stmt->bind_param('isdss', $user_id, $payment_date, $amount, $payment_method, $payment_status);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Set success message based on payment type
    if ($new_due > 0) {
        $_SESSION['success_message'] = "Partial payment of {$amount} Tk processed for {$member_name}. Remaining due: {$new_due} Tk";
    } else {
        $_SESSION['success_message'] = "Full payment of {$amount} Tk processed for {$member_name}. Payment complete!";
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = 'Error processing payment: ' . $e->getMessage();
}

// Redirect back to payments page
header("Location: ../../pages/payments.php");
exit; 
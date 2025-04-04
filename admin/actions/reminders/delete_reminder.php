<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid reminder ID';
    header("Location: ../../pages/reminders.php");
    exit;
}

$reminder_id = intval($_GET['id']);

// Check if reminder exists
$check_query = "SELECT id FROM reminder WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $reminder_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows === 0) {
    $_SESSION['error_message'] = 'Reminder not found';
    header("Location: ../../pages/reminders.php");
    exit;
}

// Delete reminder
$delete_query = "DELETE FROM reminder WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('i', $reminder_id);

if ($delete_stmt->execute()) {
    $_SESSION['success_message'] = 'Reminder deleted successfully';
} else {
    $_SESSION['error_message'] = 'Error deleting reminder: ' . $conn->error;
}

// Redirect back to reminders page
header("Location: ../../pages/reminders.php");
exit; 
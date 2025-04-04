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
    $_SESSION['error_message'] = 'Invalid announcement ID';
    header("Location: ../../pages/announcements.php");
    exit;
}

$announcement_id = intval($_GET['id']);

// Check if announcement exists
$check_query = "SELECT id FROM announcements WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $announcement_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows === 0) {
    $_SESSION['error_message'] = 'Announcement not found';
    header("Location: ../../pages/announcements.php");
    exit;
}

// Delete announcement
$delete_query = "DELETE FROM announcements WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('i', $announcement_id);

if ($delete_stmt->execute()) {
    $_SESSION['success_message'] = 'Announcement deleted successfully';
} else {
    $_SESSION['error_message'] = 'Error deleting announcement: ' . $conn->error;
}

// Redirect back to announcements page
header("Location: ../../pages/announcements.php");
exit;
?> 
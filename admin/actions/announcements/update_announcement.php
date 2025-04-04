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
    header("Location: ../../pages/announcements.php");
    exit;
}

// Validate input
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['error_message'] = 'Announcement ID is required';
    header("Location: ../../pages/announcements.php");
    exit;
}

if (empty($_POST['message'])) {
    $_SESSION['error_message'] = 'Announcement message is required';
    header("Location: ../../pages/edit_announcement.php?id=" . $_POST['id']);
    exit;
}

// Get form data
$announcement_id = $_POST['id'];
$message = $_POST['message'];

// Validate announcement exists
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

// Update announcement in database
try {
    $stmt = $conn->prepare("UPDATE announcements SET message = ? WHERE id = ?");
    $stmt->bind_param("si", $message, $announcement_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Announcement updated successfully';
        header("Location: ../../pages/announcements.php");
    } else {
        $_SESSION['error_message'] = 'Error updating announcement: ' . $conn->error;
        header("Location: ../../pages/edit_announcement.php?id=" . $announcement_id);
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    header("Location: ../../pages/edit_announcement.php?id=" . $announcement_id);
}
exit; 
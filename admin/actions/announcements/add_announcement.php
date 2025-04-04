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
if (empty($_POST['message'])) {
    $_SESSION['error_message'] = 'Announcement message is required';
    header("Location: ../../pages/announcements.php");
    exit;
}

// Get form data
$message = $_POST['message'];
$date = date('Y-m-d'); // Current date

// Insert announcement into database
try {
    $stmt = $conn->prepare("INSERT INTO announcements (message, date) VALUES (?, ?)");
    $stmt->bind_param("ss", $message, $date);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Announcement added successfully';
    } else {
        $_SESSION['error_message'] = 'Error adding announcement: ' . $conn->error;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
}

// Redirect back to announcements page
header("Location: ../../pages/announcements.php");
exit;
?> 
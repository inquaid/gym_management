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
    header("Location: ../../pages/reminders.php");
    exit;
}

// Validate input
if (empty($_POST['user_id'])) {
    $_SESSION['error_message'] = 'Member is required';
    header("Location: ../../pages/reminders.php");
    exit;
}

if (empty($_POST['message'])) {
    $_SESSION['error_message'] = 'Message is required';
    header("Location: ../../pages/reminders.php");
    exit;
}

if (empty($_POST['date'])) {
    $_SESSION['error_message'] = 'Date is required';
    header("Location: ../../pages/reminders.php");
    exit;
}

// Get form data
$user_id = $_POST['user_id'];
$message = $_POST['message'];
$date = $_POST['date'];
$status = 'Pending'; // Default status for new reminders
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Get member name for the name field in reminder table
$member_query = $conn->prepare("SELECT fullname FROM members WHERE user_id = ?");
$member_query->bind_param("i", $user_id);
$member_query->execute();
$member_result = $member_query->get_result();
$member = $member_result->fetch_assoc();
$name = $member ? $member['fullname'] : 'Unknown';

// Validate user ID exists in members table
$check_user = $conn->prepare("SELECT user_id FROM members WHERE user_id = ?");
$check_user->bind_param("i", $user_id);
$check_user->execute();
$check_user->store_result();

if ($check_user->num_rows === 0) {
    $_SESSION['error_message'] = 'Selected member does not exist';
    header("Location: ../../pages/reminders.php");
    exit;
}

// Insert reminder into database
try {
    // Using the reminder table structure from your database schema
    $stmt = $conn->prepare("INSERT INTO reminder (user_id, name, message, status, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $name, $message, $status, $date);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Reminder added successfully';
    } else {
        $_SESSION['error_message'] = 'Error adding reminder: ' . $conn->error;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
}

// Redirect back to reminders page
header("Location: ../../pages/reminders.php");
exit; 
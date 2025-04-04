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
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['error_message'] = 'Reminder ID is required';
    header("Location: ../../pages/reminders.php");
    exit;
}

if (empty($_POST['user_id'])) {
    $_SESSION['error_message'] = 'Member is required';
    header("Location: ../../pages/edit_reminder.php?id=" . $_POST['id']);
    exit;
}

if (empty($_POST['message'])) {
    $_SESSION['error_message'] = 'Message is required';
    header("Location: ../../pages/edit_reminder.php?id=" . $_POST['id']);
    exit;
}

if (empty($_POST['date'])) {
    $_SESSION['error_message'] = 'Date is required';
    header("Location: ../../pages/edit_reminder.php?id=" . $_POST['id']);
    exit;
}

if (empty($_POST['status'])) {
    $_SESSION['error_message'] = 'Status is required';
    header("Location: ../../pages/edit_reminder.php?id=" . $_POST['id']);
    exit;
}

// Get form data
$reminder_id = $_POST['id'];
$user_id = $_POST['user_id'];
$message = $_POST['message'];
$date = $_POST['date'];
$status = $_POST['status'];

// Validate reminder exists
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

// Validate user ID exists in members table
$check_user = $conn->prepare("SELECT user_id, fullname FROM members WHERE user_id = ?");
$check_user->bind_param("i", $user_id);
$check_user->execute();
$result = $check_user->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Selected member does not exist';
    header("Location: ../../pages/edit_reminder.php?id=" . $reminder_id);
    exit;
}

// Get the member name for the name field
$member = $result->fetch_assoc();
$name = $member['fullname'];

// Update reminder in database
try {
    $stmt = $conn->prepare("UPDATE reminder SET user_id = ?, name = ?, message = ?, status = ?, date = ? WHERE id = ?");
    $stmt->bind_param("issssi", $user_id, $name, $message, $status, $date, $reminder_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Reminder updated successfully';
        header("Location: ../../pages/reminders.php");
    } else {
        $_SESSION['error_message'] = 'Error updating reminder: ' . $conn->error;
        header("Location: ../../pages/edit_reminder.php?id=" . $reminder_id);
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    header("Location: ../../pages/edit_reminder.php?id=" . $reminder_id);
}
exit; 
<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Get form data
$fullname = sanitize($_POST['fullname']);
$username = sanitize($_POST['username']);
$password = $_POST['password']; // Store as plain text
$gender = sanitize($_POST['gender']);
$reg_date = $_POST['reg_date'];
$plan_id = (int)$_POST['plan_id'];
$due_amount = (int)$_POST['due_amount'];
$address = sanitize($_POST['address']);
$contact = sanitize($_POST['contact']);
$initial_weight = (float)$_POST['initial_weight'];
$current_weight = (float)$_POST['current_weight'];
$progress_date = $_POST['progress_date'];
$status = sanitize($_POST['status']);
$attendance_count = 0;
$reminder = 0;

// Check if username already exists
$check_query = "SELECT * FROM members WHERE username = '$username'";
$check_result = $conn->query($check_query);

if ($check_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
    exit;
}

// Insert new member
$query = "INSERT INTO members (
    fullname, username, password, gender, reg_date, plan_id, 
    due_amount, address, contact, status, attendance_count, 
    ini_weight, curr_weight, progress_date, reminder
) VALUES (
    '$fullname', '$username', '$password', '$gender', '$reg_date', 
    $plan_id, $due_amount, '$address', '$contact', '$status', 
    $attendance_count, $initial_weight, $current_weight, '$progress_date', $reminder
)";

if ($conn->query($query)) {
    echo json_encode(['status' => 'success', 'message' => 'Member added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add member: ' . $conn->error]);
}
?> 
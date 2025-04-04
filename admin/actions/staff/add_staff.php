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
$email = sanitize($_POST['email']);
$designation = sanitize($_POST['designation']);
$gender = sanitize($_POST['gender']);
$contact = sanitize($_POST['contact']);
$address = sanitize($_POST['address']);

// Check if username already exists
$check_query = "SELECT * FROM staffs WHERE username = '$username'";
$check_result = $conn->query($check_query);

if ($check_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
    exit;
}

// Check if email already exists
$check_email = "SELECT * FROM staffs WHERE email = '$email'";
$email_result = $conn->query($check_email);

if ($email_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    exit;
}

// Insert new staff
$query = "INSERT INTO staffs (
    fullname, username, password, email, designation, 
    gender, contact, address
) VALUES (
    '$fullname', '$username', '$password', '$email', '$designation', 
    '$gender', '$contact', '$address'
)";

if ($conn->query($query)) {
    echo json_encode(['status' => 'success', 'message' => 'Staff added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add staff: ' . $conn->error]);
}
?> 
<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is member
if (!isLoggedIn() || $_SESSION['role'] !== 'member') {
    header("Location: ../../login.php");
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['password_error'] = 'Invalid request method';
    header("Location: ../../pages/profile.php");
    exit;
}

// Get member information
$member_id = $_SESSION['user_id'];

// Get and validate inputs
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate required fields
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['password_error'] = 'All fields are required';
    header("Location: ../../pages/profile.php");
    exit;
}

// Validate password match
if ($new_password !== $confirm_password) {
    $_SESSION['password_error'] = 'New passwords do not match';
    header("Location: ../../pages/profile.php");
    exit;
}

// Validate password length
if (strlen($new_password) < 6) {
    $_SESSION['password_error'] = 'New password must be at least 6 characters long';
    header("Location: ../../pages/profile.php");
    exit;
}

try {
    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM members WHERE user_id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    // Verify current password
    if ($current_password !== $member['password']) {
        $_SESSION['password_error'] = 'Current password is incorrect';
        header("Location: ../../pages/profile.php");
        exit;
    }

    // Store new password as plain text
    $stmt = $conn->prepare("UPDATE members SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_password, $member_id);
    
    if ($stmt->execute()) {
        $_SESSION['password_success'] = true;
    } else {
        $_SESSION['password_error'] = 'Failed to update password';
    }
} catch (Exception $e) {
    $_SESSION['password_error'] = 'An error occurred: ' . $e->getMessage();
}

// Redirect back to profile page
header("Location: ../../pages/profile.php");
exit;
?> 
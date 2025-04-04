<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is staff
if (!isLoggedIn() || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['password_error'] = 'Invalid request method';
    header("Location: ../../pages/profile.php");
    exit;
}

// Get staff information
$staff_id = $_SESSION['user_id'];

// Get and validate inputs
$current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Validate inputs
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['password_error'] = 'All password fields are required';
    header("Location: ../../pages/profile.php");
    exit;
}

if ($new_password !== $confirm_password) {
    $_SESSION['password_error'] = 'New password and confirm password do not match';
    header("Location: ../../pages/profile.php");
    exit;
}

// Validate password length
if (strlen($new_password) < 6) {
    $_SESSION['password_error'] = 'Password must be at least 6 characters long';
    header("Location: ../../pages/profile.php");
    exit;
}

try {
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM staffs WHERE user_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        $_SESSION['password_error'] = 'Staff user not found';
        header("Location: ../../pages/profile.php");
        exit;
    }
    
    if ($result['password'] !== $current_password) {
        $_SESSION['password_error'] = 'Current password is incorrect';
        header("Location: ../../pages/profile.php");
        exit;
    }
    
    // Update password
    $stmt = $conn->prepare("UPDATE staffs SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_password, $staff_id);
    
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
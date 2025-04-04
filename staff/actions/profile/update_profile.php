<?php
require_once '../../../dbcon.php';
require_once '../../../session.php';

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $designation = trim($_POST['designation']);
    $gender = trim($_POST['gender']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $user_id = $_SESSION['user_id'];

    // Validate required fields
    if (empty($fullname) || empty($email) || empty($designation) || 
        empty($gender) || empty($contact) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    // Check if email already exists for another staff
    $stmt = $conn->prepare("SELECT user_id FROM staffs WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Update staff profile
    $stmt = $conn->prepare("UPDATE staffs SET fullname = ?, email = ?, designation = ?, 
                           gender = ?, contact = ?, address = ? WHERE user_id = ?");
    $stmt->bind_param("ssssssi", $fullname, $email, $designation, $gender, $contact, $address, $user_id);

    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['fullname'] = $fullname;
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating profile']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 
<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if form data was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input values
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $email = trim($_POST['email']);
    $designation = trim($_POST['designation']);
    $gender = trim($_POST['gender']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);

    // Validate required fields
    if (empty($fullname) || empty($username) || empty($email) || 
        empty($designation) || empty($gender) || empty($contact) || empty($address)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required except password']);
        exit;
    }

    // Check if staff exists
    $checkStaff = $conn->prepare("SELECT user_id FROM staffs WHERE user_id = ?");
    $checkStaff->bind_param("i", $id);
    $checkStaff->execute();
    $result = $checkStaff->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Staff not found']);
        exit;
    }

    // Check if username exists for other staff
    $checkUsername = $conn->prepare("SELECT user_id FROM staffs WHERE username = ? AND user_id != ?");
    $checkUsername->bind_param("si", $username, $id);
    $checkUsername->execute();
    $usernameResult = $checkUsername->get_result();
    
    if ($usernameResult->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
        exit;
    }

    // Update staff details
    $updateQuery = "UPDATE staffs SET 
                    fullname = ?, 
                    username = ?, 
                    email = ?, 
                    designation = ?, 
                    gender = ?, 
                    contact = ?, 
                    address = ?";
    
    // Add password to query if provided
    $params = [$fullname, $username, $email, $designation, $gender, $contact, $address];
    $types = "sssssss";
    
    if (!empty($password)) {
        $updateQuery .= ", password = ?";
        $plainPassword = $password;
        $params[] = $plainPassword;
        $types .= "s";
    }
    
    $updateQuery .= " WHERE user_id = ?";
    $params[] = $id;
    $types .= "i";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Set success message
        $_SESSION['success_message'] = "Staff information updated successfully";
        echo json_encode(['status' => 'success', 'message' => 'Staff information updated successfully']);
    } else {
        // Set error message
        $_SESSION['error_message'] = "Error updating staff information: " . $conn->error;
        echo json_encode(['status' => 'error', 'message' => 'Error updating staff information: ' . $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?> 
<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get member ID
    $id = (int)$_POST['id'];
    
    // Sanitize and validate input
    $fullname = sanitize($_POST['fullname']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Will be hashed if not empty
    $gender = sanitize($_POST['gender']);
    $reg_date = sanitize($_POST['reg_date']);
    $plan_id = (int)$_POST['plan_id'];
    $due_amount = (float)$_POST['due_amount'];
    $status = sanitize($_POST['status']);
    $address = sanitize($_POST['address']);
    $contact = sanitize($_POST['contact']);
    $initial_weight = (float)$_POST['initial_weight'];
    $curr_weight = (float)$_POST['curr_weight'];
    $progress_date = sanitize($_POST['progress_date']);

    // Validate required fields
    if (empty($id) || empty($fullname) || empty($username) || empty($gender) || 
        empty($reg_date) || empty($plan_id) || empty($due_amount) || empty($status) || 
        empty($address) || empty($contact) || empty($initial_weight) || 
        empty($curr_weight) || empty($progress_date)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit();
    }

    // Check if member exists
    $check_query = "SELECT user_id FROM members WHERE user_id = $id";
    $check_result = $conn->query($check_query);
    if ($check_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Member not found']);
        exit();
    }

    // Check if username already exists for another member
    $username_query = "SELECT user_id FROM members WHERE username = '$username' AND user_id != $id";
    $username_result = $conn->query($username_query);
    if ($username_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists for another member']);
        exit();
    }

    // Verify plan exists
    $plan_query = "SELECT id FROM membership_plan WHERE id = $plan_id";
    $plan_result = $conn->query($plan_query);
    if ($plan_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid membership plan']);
        exit();
    }

    // Build the update query
    $update_fields = [
        "fullname = '$fullname'",
        "username = '$username'",
        "gender = '$gender'",
        "reg_date = '$reg_date'",
        "plan_id = $plan_id",
        "due_amount = $due_amount",
        "status = '$status'",
        "address = '$address'",
        "contact = '$contact'",
        "ini_weight = $initial_weight",
        "curr_weight = $curr_weight",
        "progress_date = '$progress_date'"
    ];
    
    // Add password to update if provided
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_fields[] = "password = '$hashed_password'";
    }
    
    $update_query = "UPDATE members SET " . implode(", ", $update_fields) . " WHERE user_id = $id";
    
    if ($conn->query($update_query)) {
        // Set success message in session for redirection
        $_SESSION['success_message'] = "Member updated successfully";
        echo json_encode(['status' => 'success', 'message' => 'Member updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update member: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 
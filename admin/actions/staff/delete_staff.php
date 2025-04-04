<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if form data was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input values
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Validate staff ID
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid staff ID']);
        exit;
    }

    // Check if staff exists
    $checkStaff = $conn->prepare("SELECT user_id FROM staffs WHERE user_id = ?");
    $checkStaff->bind_param("i", $id);
    $checkStaff->execute();
    $result = $checkStaff->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Staff not found']);
        exit;
    }

    // Delete staff
    $stmt = $conn->prepare("DELETE FROM staffs WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Staff deleted successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error deleting staff: ' . $conn->error]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?> 
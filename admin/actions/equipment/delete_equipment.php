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

    // Validate equipment ID
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid equipment ID']);
        exit;
    }

    // Check if equipment exists
    $checkEquipment = $conn->prepare("SELECT id FROM equipment WHERE id = ?");
    $checkEquipment->bind_param("i", $id);
    $checkEquipment->execute();
    $result = $checkEquipment->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Equipment not found']);
        exit;
    }

    // Delete equipment
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Set success message
        $_SESSION['success_message'] = "Equipment deleted successfully";
        echo json_encode(['status' => 'success', 'message' => 'Equipment deleted successfully']);
    } else {
        // Set error message
        $_SESSION['error_message'] = "Error deleting equipment: " . $conn->error;
        echo json_encode(['status' => 'error', 'message' => 'Error deleting equipment: ' . $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?> 
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
    $name = trim($_POST['name']);
    $quantity = (int)$_POST['quantity'];
    $amount = (float)$_POST['amount'];
    $vendor = trim($_POST['vendor']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $contact = trim($_POST['contact']);
    $purchase_date = trim($_POST['purchase_date']);

    // Validate required fields
    if (empty($name) || empty($description) || $quantity <= 0 || $amount < 0 || 
        empty($vendor) || empty($address) || empty($contact) || empty($purchase_date)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required and must be valid']);
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

    // Update equipment details
    $stmt = $conn->prepare("UPDATE equipment SET 
                           name = ?, 
                           quantity = ?, 
                           amount = ?, 
                           vendor = ?, 
                           description = ?, 
                           address = ?, 
                           contact = ?, 
                           purchase_date = ? 
                           WHERE id = ?");
    $stmt->bind_param("siisssssi", $name, $quantity, $amount, $vendor, $description, $address, $contact, $purchase_date, $id);
    
    if ($stmt->execute()) {
        // Set success message
        $_SESSION['success_message'] = "Equipment updated successfully";
        echo json_encode(['status' => 'success', 'message' => 'Equipment updated successfully']);
    } else {
        // Set error message
        $_SESSION['error_message'] = "Error updating equipment: " . $conn->error;
        echo json_encode(['status' => 'error', 'message' => 'Error updating equipment: ' . $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?> 
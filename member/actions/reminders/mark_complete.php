<?php
require_once '../../../dbcon.php';
require_once '../../../session.php';

// Check if user is logged in as member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reminder_id = isset($_POST['reminder_id']) ? (int)$_POST['reminder_id'] : 0;
    $user_id = $_SESSION['user_id'];

    if ($reminder_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid reminder ID']);
        exit();
    }

    // Verify that the reminder belongs to the current user
    $stmt = $conn->prepare("SELECT id FROM reminder WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reminder_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Reminder not found or unauthorized access']);
        exit();
    }

    // Update reminder status to Completed
    $stmt = $conn->prepare("UPDATE reminder SET status = 'Completed' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reminder_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reminder marked as complete']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating reminder: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 
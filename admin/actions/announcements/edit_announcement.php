<?php
require_once '../../include/session.php';
require_once '../../include/db.php';

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
    $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    // Validate required fields
    if (!$id || !$title || !$content || !$start_date || !$end_date || !$priority || !$status) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Validate dates
    if (!strtotime($start_date) || !strtotime($end_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
        exit;
    }

    if (strtotime($end_date) < strtotime($start_date)) {
        echo json_encode(['status' => 'error', 'message' => 'End date must be after start date']);
        exit;
    }

    // Validate priority
    $valid_priorities = ['Low', 'Medium', 'High'];
    if (!in_array($priority, $valid_priorities)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid priority level']);
        exit;
    }

    // Validate status
    $valid_statuses = ['Draft', 'Published', 'Archived'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
    }

    try {
        // Check if announcement exists
        $check_stmt = $conn->prepare("SELECT id FROM announcements WHERE id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Announcement not found']);
            exit;
        }

        // Prepare SQL statement
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, start_date = ?, end_date = ?, 
                              priority = ?, status = ? WHERE id = ?");
        
        // Bind parameters
        $stmt->bind_param("ssssssi", $title, $content, $start_date, $end_date, $priority, $status, $id);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Announcement updated successfully']);
        } else {
            throw new Exception("Failed to update announcement");
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 
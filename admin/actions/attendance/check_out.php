<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get user_id and attendance_id from POST data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$attendance_id = isset($_POST['attendance_id']) ? intval($_POST['attendance_id']) : 0;

if ($user_id <= 0 || $attendance_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid member ID or attendance ID']);
    exit;
}

// Check if the member and attendance record exist
$stmt = $conn->prepare("
    SELECT a.id FROM attendance a 
    JOIN members m ON a.user_id = m.user_id 
    WHERE a.id = ? AND a.user_id = ? AND m.status = 'Active' AND a.check_out_time IS NULL
");
$stmt->bind_param("ii", $attendance_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Attendance record not found, already checked out, or member inactive']);
    exit;
}

// Update the attendance record with check-out time
$check_out_time = date('Y-m-d H:i:s');

$stmt = $conn->prepare("UPDATE attendance SET check_out_time = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $check_out_time, $attendance_id, $user_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Check-out successful']);
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
} 
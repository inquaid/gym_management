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

// Get user_id from POST data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($user_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid member ID']);
    exit;
}

// Check if the member exists
$stmt = $conn->prepare("SELECT user_id FROM members WHERE user_id = ? AND status = 'Active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Member not found or inactive']);
    exit;
}

// Check if member already has an attendance record for today
$current_date = date('Y-m-d');
$stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in_time) = ?");
$stmt->bind_param("is", $user_id, $current_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Member already checked in today']);
    exit;
}

// Create a new attendance record with check-in time
$check_in_time = date('Y-m-d H:i:s');
$present = 1;

$stmt = $conn->prepare("INSERT INTO attendance (user_id, check_in_time, present) VALUES (?, ?, ?)");
$stmt->bind_param("isi", $user_id, $check_in_time, $present);

if ($stmt->execute()) {
    // Update attendance_count in members table
    $stmt = $conn->prepare("UPDATE members SET attendance_count = attendance_count + 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Check-in successful']);
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
} 
<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in as member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_SESSION['user_id'];
    
    // Sanitize inputs
    $fullname = htmlspecialchars(trim($_POST['fullname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $gender = htmlspecialchars(trim($_POST['gender'] ?? ''), ENT_QUOTES, 'UTF-8');
    $contact = htmlspecialchars(trim($_POST['contact'] ?? ''), ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8');
    $ini_weight = (int)($_POST['ini_weight'] ?? 0);
    $curr_weight = (int)($_POST['curr_weight'] ?? 0);
    $progress_date = htmlspecialchars(trim($_POST['progress_date'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Validate required fields
    if (empty($fullname) || empty($username) || empty($gender) || empty($contact) || empty($address) || empty($progress_date)) {
        $_SESSION['update_error'] = 'All fields are required';
        header("Location: ../../pages/profile.php");
        exit;
    }

    try {
        // Check if username is already taken by another member
        $stmt = $conn->prepare("SELECT user_id FROM members WHERE username = ? AND user_id != ?");
        $stmt->bind_param("si", $username, $member_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['update_error'] = 'Username is already taken';
            header("Location: ../../pages/profile.php");
            exit;
        }

        // Update member profile
        $stmt = $conn->prepare("UPDATE members SET fullname = ?, username = ?, gender = ?, contact = ?, address = ?, ini_weight = ?, curr_weight = ?, progress_date = ? WHERE user_id = ?");
        $stmt->bind_param("sssssiisi", $fullname, $username, $gender, $contact, $address, $ini_weight, $curr_weight, $progress_date, $member_id);
        
        if ($stmt->execute()) {
            $_SESSION['update_success'] = true;
        } else {
            $_SESSION['update_error'] = 'Failed to update profile';
        }
    } catch (Exception $e) {
        $_SESSION['update_error'] = 'Error updating profile: ' . $e->getMessage();
    }
}

header("Location: ../../pages/profile.php");
exit; 
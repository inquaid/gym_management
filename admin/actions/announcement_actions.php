<?php
require_once '../../session.php';
require_once '../../dbcon.php';
requireRole('admin');

// Handle POST requests for announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add new announcement
    if (isset($_POST['add_announcement'])) {
        $message = sanitize($_POST['message']);
        $date = date('Y-m-d');
        
        // Validate input
        if (empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Announcement message cannot be empty']);
            exit();
        }
        
        // Insert announcement
        $query = "INSERT INTO announcements (message, date) VALUES ('$message', '$date')";
        
        if ($conn->query($query)) {
            echo json_encode(['status' => 'success', 'message' => 'Announcement added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add announcement: ' . $conn->error]);
        }
        exit();
    }
    
    // Update announcement
    if (isset($_POST['update_announcement'])) {
        $id = (int) $_POST['id'];
        $message = sanitize($_POST['message']);
        
        // Validate input
        if (empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Announcement message cannot be empty']);
            exit();
        }
        
        // Update announcement
        $query = "UPDATE announcements SET message = '$message' WHERE id = $id";
        
        if ($conn->query($query)) {
            echo json_encode(['status' => 'success', 'message' => 'Announcement updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update announcement: ' . $conn->error]);
        }
        exit();
    }
    
    // Delete announcement
    if (isset($_POST['delete_announcement'])) {
        $id = (int) $_POST['id'];
        
        // Delete announcement
        $query = "DELETE FROM announcements WHERE id = $id";
        
        if ($conn->query($query)) {
            echo json_encode(['status' => 'success', 'message' => 'Announcement deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete announcement: ' . $conn->error]);
        }
        exit();
    }
}

// Handle GET requests for announcements
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Get single announcement for editing
    if (isset($_GET['get_announcement'])) {
        $id = (int) $_GET['id'];
        
        $query = "SELECT * FROM announcements WHERE id = $id";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $announcement = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $announcement]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Announcement not found']);
        }
        exit();
    }
    
    // Get all announcements
    if (isset($_GET['get_all_announcements'])) {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM announcements";
        $count_result = $conn->query($count_query);
        $total = $count_result->fetch_assoc()['total'];
        
        // Get announcements with pagination
        $query = "SELECT * FROM announcements ORDER BY date DESC LIMIT $offset, $limit";
        $result = $conn->query($query);
        
        $announcements = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $announcements[] = $row;
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $announcements,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
        exit();
    }
}

// If no valid action is specified, return an error
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
exit();
?> 
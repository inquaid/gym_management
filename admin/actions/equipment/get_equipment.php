<?php
require_once '../../include/session.php';
require_once '../../include/db.php';

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get and sanitize parameters
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_GET, 'location', FILTER_SANITIZE_STRING);

    // Validate page and limit
    if ($page < 1 || $limit < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid page or limit']);
        exit;
    }

    // Calculate offset
    $offset = ($page - 1) * $limit;

    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    $types = '';

    if ($search) {
        $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }

    if ($status) {
        $where_conditions[] = "status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if ($location) {
        $where_conditions[] = "location = ?";
        $params[] = $location;
        $types .= 's';
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    try {
        // Get total count
        $count_query = "SELECT COUNT(*) as total FROM equipment $where_clause";
        $count_stmt = $conn->prepare($count_query);
        
        if (!empty($params)) {
            $count_stmt->bind_param($types, ...$params);
        }
        
        $count_stmt->execute();
        $total_result = $count_stmt->get_result()->fetch_assoc();
        $total = $total_result['total'];
        $total_pages = ceil($total / $limit);

        // Get equipment data
        $query = "SELECT * FROM equipment $where_clause ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        
        // Add limit and offset to parameters
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $equipment = [];
        while ($row = $result->fetch_assoc()) {
            $equipment[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $equipment,
            'total' => $total,
            'total_pages' => $total_pages,
            'current_page' => $page
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 
<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get and sanitize filter parameters
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    $gender = isset($_GET['gender']) ? sanitize($_GET['gender']) : '';
    $position = isset($_GET['position']) ? sanitize($_GET['position']) : '';

    // Calculate offset
    $offset = ($page - 1) * $limit;

    // Build WHERE clause
    $where_conditions = [];
    if (!empty($search)) {
        $where_conditions[] = "(fullname LIKE '%$search%' OR username LIKE '%$search%')";
    }
    if (!empty($gender)) {
        $where_conditions[] = "gender = '$gender'";
    }
    if (!empty($position)) {
        $where_conditions[] = "position = '$position'";
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM staffs $where_clause";
    $count_result = $conn->query($count_query);
    $total = $count_result->fetch_assoc()['total'];

    // Get staff with filters and pagination
    $query = "SELECT * FROM staffs $where_clause ORDER BY id DESC LIMIT $offset, $limit";
    $result = $conn->query($query);

    $staff = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $staff,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 
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
    $plan = isset($_GET['plan']) ? sanitize($_GET['plan']) : '';
    $payment_status = isset($_GET['payment_status']) ? sanitize($_GET['payment_status']) : '';

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
    if (!empty($plan)) {
        $where_conditions[] = "plan = '$plan'";
    }
    if (!empty($payment_status)) {
        switch ($payment_status) {
            case 'paid':
                $where_conditions[] = "payment_date >= CURDATE()";
                break;
            case 'pending':
                $where_conditions[] = "payment_date = CURDATE()";
                break;
            case 'overdue':
                $where_conditions[] = "payment_date < CURDATE()";
                break;
        }
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM members $where_clause";
    $count_result = $conn->query($count_query);
    $total = $count_result->fetch_assoc()['total'];

    // Get members with filters and pagination
    $query = "SELECT * FROM members $where_clause ORDER BY id DESC LIMIT $offset, $limit";
    $result = $conn->query($query);

    $members = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Determine payment status
            $payment_date = new DateTime($row['payment_date']);
            $today = new DateTime();
            $interval = $today->diff($payment_date);
            
            if ($payment_date > $today) {
                $row['payment_status'] = 'Paid';
            } elseif ($payment_date == $today) {
                $row['payment_status'] = 'Pending';
            } else {
                $row['payment_status'] = 'Overdue';
            }

            $members[] = $row;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $members,
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
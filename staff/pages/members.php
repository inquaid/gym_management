<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is staff
if (!isLoggedIn() || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

// Get staff information
$staff_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM staffs WHERE user_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

// Define filters and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Build the query based on filters
$query = "SELECT m.*, mp.name as plan_name 
          FROM members m 
          JOIN membership_plan mp ON m.plan_id = mp.id 
          WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM members m WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (m.fullname LIKE ? OR m.username LIKE ?)";
    $count_query .= " AND (m.fullname LIKE ? OR m.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($status_filter) && $status_filter != 'all') {
    $query .= " AND m.status = ?";
    $count_query .= " AND m.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($gender_filter) && $gender_filter != 'all') {
    $query .= " AND m.gender = ?";
    $count_query .= " AND m.gender = ?";
    $params[] = $gender_filter;
    $types .= "s";
}

// Add order by and limit for the main query
$query .= " ORDER BY m.fullname ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $items_per_page;
$types .= "ii";

// Prepare and execute the count query
$count_stmt = $conn->prepare($count_query);
if (!empty($types) && !empty($params)) {
    // Create array of parameters for count query (excluding pagination params)
    $count_types = substr($types, 0, -2); // Remove the last 2 characters (ii for pagination)
    $count_params = array_slice($params, 0, count($params) - 2);
    
    if (!empty($count_types)) {
        $count_bind_params = array_merge([$count_types], $count_params);
        call_user_func_array([$count_stmt, 'bind_param'], makeValuesReferenced($count_bind_params));
    }
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Prepare and execute the main query
$stmt = $conn->prepare($query);
if (!empty($types) && !empty($params)) {
    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], makeValuesReferenced($bind_params));
}
$stmt->execute();
$members = $stmt->get_result();

// Helper function to make array values referenced
function makeValuesReferenced($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Process success or error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - Friends Gym</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.2);
        }
        .main-content {
            padding: 2rem;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1.5rem;
        }
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .table {
            vertical-align: middle;
        }
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        .page-link {
            color: #495057;
            border-radius: 0.25rem;
            margin: 0 2px;
        }
        .page-link:hover {
            color: #212529;
            background-color: #e9ecef;
        }
        .page-item.active .page-link {
            background-color: #6c757d;
            border-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4>Friends Gym</h4>
                    <small class="text-muted">Staff Panel</small>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="members.php">
                                <i class="fas fa-users me-2"></i>Members
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-calendar-check me-2"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="equipment.php">
                                <i class="fas fa-dumbbell me-2"></i>Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="../../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Members Management</h2>
                    <div class="d-flex align-items-center">
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Members Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Members List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Contact</th>
                                        <th>Plan</th>
                                        <th>Status</th>
                                        <th>Registration Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($members->num_rows > 0): ?>
                                        <?php 
                                        $counter = 1;
                                        while ($member = $members->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                                <td><?php echo htmlspecialchars($member['username']); ?></td>
                                                <td><?php echo htmlspecialchars($member['contact']); ?></td>
                                                <td><?php echo htmlspecialchars($member['plan_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $member['status'] === 'Active' ? 'success' : 'danger'; ?>">
                                                        <?php echo htmlspecialchars($member['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($member['reg_date'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No members found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
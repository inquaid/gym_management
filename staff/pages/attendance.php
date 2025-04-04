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

// Get filter parameters
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$member_filter = isset($_GET['member']) ? $_GET['member'] : '';

// Prepare query based on filters
$where_clause = "1=1";
$params = [];
$types = "";

if (!empty($date_filter)) {
    $where_clause .= " AND DATE(a.check_in_time) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if (!empty($member_filter)) {
    $where_clause .= " AND (m.fullname LIKE ? OR m.username LIKE ?)";
    $params[] = "%$member_filter%";
    $params[] = "%$member_filter%";
    $types .= "ss";
}

// Get attendance records
$query = "SELECT a.*, m.fullname, m.gender
          FROM attendance a
          JOIN members m ON a.user_id = m.user_id
          WHERE $where_clause
          ORDER BY a.check_in_time DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$attendance_records = $stmt->get_result();

// Get all members for dropdown
$stmt = $conn->prepare("SELECT user_id, fullname FROM members ORDER BY fullname");
$stmt->execute();
$members = $stmt->get_result();

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get all members with their attendance for the selected date
$sql = "SELECT m.*, 
        a.check_in_time, 
        a.check_out_time,
        CASE 
            WHEN a.present = 1 THEN 'Present'
            WHEN a.present = 0 THEN 'Absent'
            ELSE 'Absent'
        END as status
        FROM members m
        LEFT JOIN attendance a ON m.user_id = a.user_id 
        AND DATE(a.check_in_time) = ?
        ORDER BY m.fullname";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$result = $stmt->get_result();

// Get total members count
$total_members = $result->num_rows;

// Get present members count
$present_count = 0;
$absent_count = 0;
$result->data_seek(0); // Reset the result pointer
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'Present') {
        $present_count++;
    } else {
        $absent_count++;
    }
}
$result->data_seek(0); // Reset the result pointer again for display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Friends Gym</title>
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
        .filter-form {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
        }
        .attendance-table th {
            font-weight: 600;
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
                            <a class="nav-link" href="members.php">
                                <i class="fas fa-users me-2"></i>Members
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="attendance.php">
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
                    <h2>Attendance List</h2>
                    <div class="d-flex align-items-center">
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Attendance Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Members</h5>
                                <h2 class="mb-0"><?php echo $total_members; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Present</h5>
                                <h2 class="mb-0"><?php echo $present_count; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Absent</h5>
                                <h2 class="mb-0"><?php echo $absent_count; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Selector -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label for="date" class="form-label">Select Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo $selected_date; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">View Attendance</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Member Name</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    while ($row = $result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                            <td><?php echo $row['check_in_time'] ? date('h:i A', strtotime($row['check_in_time'])) : 'None'; ?></td>
                                            <td><?php echo $row['check_out_time'] ? date('h:i A', strtotime($row['check_out_time'])) : 'None'; ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'Present'): ?>
                                                    <span class="badge bg-success">Present</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Absent</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
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
    <script>
        document.getElementById('attendanceDate').addEventListener('change', function() {
            window.location.href = 'attendance.php?date=' + this.value;
        });
    </script>
</body>
</html> 
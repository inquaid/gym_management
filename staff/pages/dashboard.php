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

// Get total members count
$stmt = $conn->query("SELECT COUNT(*) as total FROM members");
$total_members = $stmt->fetch_assoc()['total'];

// Get total attendance count for today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE DATE(check_in_time) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc()['total'];

// Get recent announcements
$stmt = $conn->query("SELECT id, message, date FROM announcements ORDER BY date DESC LIMIT 5");
$announcements = [];
while ($row = $stmt->fetch_assoc()) {
    // Ensure the message field exists, handle missing fields
    $row['title'] = $row['message']; // Use message as title
    $row['author'] = 'Admin'; // Default author
    $announcements[] = $row;
}

// Get recent members
$stmt = $conn->query("SELECT m.*, mp.name as plan_name 
                     FROM members m 
                     JOIN membership_plan mp ON m.plan_id = mp.id 
                     ORDER BY m.reg_date DESC LIMIT 5");
$recent_members = [];
while ($row = $stmt->fetch_assoc()) {
    $recent_members[] = $row;
}

// Get active members count
$stmt = $conn->prepare("SELECT COUNT(*) as active FROM members WHERE status = 'Active'");
$stmt->execute();
$active_members = $stmt->get_result()->fetch_assoc()['active'];

// Get equipment count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM equipment");
$stmt->execute();
$equipment_count = $stmt->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Friends Gym</title>
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
        }
        .stat-card .icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .stat-card h3 {
            font-size: 1.8rem;
            margin: 0.5rem 0;
            font-weight: bold;
        }
        .stat-card p {
            margin: 0;
            color: #6c757d;
            font-weight: 500;
        }
        .activity-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1.5rem;
        }
        .activity-card h4 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
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
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="members.php">
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
                    <h2>Welcome, <?php echo htmlspecialchars($staff['fullname']); ?></h2>
                    <div class="d-flex align-items-center">
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Statistics Row -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p>Total Members</p>
                                    <h3><?php echo $total_members; ?></h3>
                                </div>
                                <div class="icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p>Active Members</p>
                                    <h3><?php echo $active_members; ?></h3>
                                </div>
                                <div class="icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p>Today's Attendance</p>
                                    <h3><?php echo $today_attendance; ?></h3>
                                </div>
                                <div class="icon bg-info">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p>Equipment</p>
                                    <h3><?php echo $equipment_count; ?></h3>
                                </div>
                                <div class="icon bg-warning">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Access Row -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="activity-card">
                            <h4>Quick Access</h4>
                            <div class="row">
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <a href="attendance.php" class="btn btn-primary w-100 py-3">
                                        <i class="fas fa-calendar-check fa-lg mb-2"></i>
                                        <div>View Attendance</div>
                                    </a>
                                </div>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <a href="members.php" class="btn btn-success w-100 py-3">
                                        <i class="fas fa-users fa-lg mb-2"></i>
                                        <div>View Members</div>
                                    </a>
                                </div>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <a href="equipment.php" class="btn btn-warning w-100 py-3">
                                        <i class="fas fa-dumbbell fa-lg mb-2"></i>
                                        <div>Equipment</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Sections -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Members</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($recent_members) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($recent_members as $member): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($member['fullname']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($member['plan_name']); ?> Plan</small>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('d M Y', strtotime($member['reg_date'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent members</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Announcements</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($announcements) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($announcements as $announcement): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo isset($announcement['title']) ? htmlspecialchars($announcement['title']) : htmlspecialchars($announcement['message']); ?></h6>
                                                        <small class="text-muted"><?php echo date('d M Y', strtotime($announcement['date'])); ?></small>
                                                    </div>
                                                    <small class="text-muted"><?php echo isset($announcement['author']) ? htmlspecialchars($announcement['author']) : 'Admin'; ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent announcements</p>
                                <?php endif; ?>
                            </div>
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
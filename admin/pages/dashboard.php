<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get statistics
$stats = array();

// Total members
$member_query = "SELECT COUNT(*) as total FROM members";
$member_result = $conn->query($member_query);
$stats['total_members'] = $member_result->fetch_assoc()['total'];

// Active members
$active_query = "SELECT COUNT(*) as total FROM members WHERE status = 'Active'";
$active_result = $conn->query($active_query);
$stats['active_members'] = $active_result->fetch_assoc()['total'];

// Total staff
$staff_query = "SELECT COUNT(*) as total FROM staffs";
$staff_result = $conn->query($staff_query);
$stats['total_staff'] = $staff_result->fetch_assoc()['total'];

// Total equipment
$equipment_query = "SELECT COUNT(*) as total FROM equipment";
$equipment_result = $conn->query($equipment_query);
$stats['total_equipment'] = $equipment_result->fetch_assoc()['total'];

// Today's attendance count
$today = date('Y-m-d');
$attendance_query = "SELECT COUNT(*) as total FROM attendance WHERE DATE(check_in_time) = ?";
$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$stats['today_attendance'] = $result->fetch_assoc()['total'];

// Recent members
$recent_members_query = "SELECT * FROM members ORDER BY reg_date DESC LIMIT 5";
$recent_members = $conn->query($recent_members_query);

// Recent announcements
$recent_announcements_query = "SELECT * FROM announcements ORDER BY date DESC LIMIT 5";
$recent_announcements = $conn->query($recent_announcements_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Friends Gym</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
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
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1.5rem;
        }
        .stats-card {
            border-radius: 10px;
            color: white;
            padding: 1.2rem;
            height: 100%;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include('../includes/sidebar.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <div class="d-flex align-items-center">
                        <div class="text-muted me-3">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card bg-primary">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Members</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $stats['total_members']; ?></h2>
                                    <small>Active: <?php echo $stats['active_members']; ?></small>
                                </div>
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stats-card bg-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Staff</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $stats['total_staff']; ?></h2>
                                </div>
                                <i class="fas fa-user-tie fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stats-card bg-warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Equipment</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $stats['total_equipment']; ?></h2>
                                </div>
                                <i class="fas fa-dumbbell fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stats-card bg-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Today's Attendance</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $stats['today_attendance']; ?></h2>
                                </div>
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Members and Announcements -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="content-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Recent Members</h5>
                                <a href="members.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-users me-1"></i> View All
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Join Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_members->num_rows > 0): ?>
                                            <?php while($member = $recent_members->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['username']); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($member['reg_date'])); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $member['status'] == 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                                            <?php echo $member['status']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No members found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="content-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Recent Announcements</h5>
                                <a href="announcements.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-bullhorn me-1"></i> View All
                                </a>
                            </div>
                            <div class="list-group">
                                <?php if ($recent_announcements->num_rows > 0): ?>
                                    <?php while($announcement = $recent_announcements->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($announcement['message']); ?></h6>
                                                <small><?php echo date('d M Y', strtotime($announcement['date'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="list-group-item">
                                        <p class="text-center mb-0">No announcements found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <h5 class="mb-3">Quick Actions</h5>
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="add_member.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-user-plus me-2"></i>Add Member
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="add_staff.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-user-tie me-2"></i>Add Staff
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="add_equipment.php" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-dumbbell me-2"></i>Add Equipment
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="add_announcement.php" class="btn btn-info btn-lg w-100">
                                <i class="fas fa-bullhorn me-2"></i>Add Announcement
                            </a>
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
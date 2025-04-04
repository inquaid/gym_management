<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is member
if (!isLoggedIn() || $_SESSION['role'] !== 'member') {
    header("Location: ../login.php");
    exit;
}

// Get member information
$member_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT m.*, mp.price as monthly_fee 
                       FROM members m 
                       JOIN membership_plan mp ON m.plan_id = mp.id 
                       WHERE m.user_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

// Calculate due amount for current month
$current_month = date('Y-m');
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid 
                       FROM payments 
                       WHERE user_id = ? 
                       AND DATE_FORMAT(payment_date, '%Y-%m') = ? 
                       AND order_id IS NULL");
$stmt->bind_param("is", $member_id, $current_month);
$stmt->execute();
$payment_result = $stmt->get_result()->fetch_assoc();
$total_paid = $payment_result['total_paid'];
$due_amount = max(0, $member['monthly_fee'] - $total_paid);

// Get attendance count for current month
$stmt = $conn->prepare("SELECT COUNT(*) as total 
                       FROM attendance 
                       WHERE user_id = ? 
                       AND present = 1 
                       AND DATE_FORMAT(check_in_time, '%Y-%m') = ?");
$stmt->bind_param("ss", $member_id, $current_month);
$stmt->execute();
$attendance_count = $stmt->get_result()->fetch_assoc()['total'];

// Get recent attendance
$stmt = $conn->prepare("SELECT * FROM attendance 
                       WHERE user_id = ? 
                       AND present = 1 
                       ORDER BY check_in_time DESC LIMIT 5");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$recent_attendance = $stmt->get_result();

// Get membership plan details
$stmt = $conn->prepare("SELECT mp.name as plan_name, mp.price 
                       FROM membership_plan mp 
                       JOIN members m ON m.plan_id = mp.id 
                       WHERE m.user_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$plan_details = $stmt->get_result()->fetch_assoc();

// Get recent announcements
$stmt = $conn->prepare("SELECT * FROM announcements ORDER BY date DESC LIMIT 5");
$stmt->execute();
$announcements = $stmt->get_result();

// Get reminders
$stmt = $conn->prepare("SELECT * FROM reminder WHERE user_id = ? AND status != 'Completed' ORDER BY date DESC");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$reminders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Friends Gym</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1rem;
        }
        .progress-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
        }
        .announcement-card {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .announcement-date {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .reminder-card {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #2c3e50;
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
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-calendar-check me-2"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="progress.php">
                                <i class="fas fa-chart-line me-2"></i>Progress
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="shop.php">
                                <i class="fas fa-shopping-cart me-2"></i>Shop
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reminders.php">
                                <i class="fas fa-bell me-2"></i>Reminders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payments.php">
                                <i class="fas fa-credit-card me-2"></i>Payments
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
                    <h2>Welcome, <?php echo htmlspecialchars($member['fullname']); ?></h2>
                    <div class="d-flex align-items-center">
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Member Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h4 class="mb-3">Membership Details</h4>
                            <div class="row mb-2">
                                <div class="col-md-4 text-muted">Plan:</div>
                                <div class="col-md-8"><?php echo htmlspecialchars($plan_details['plan_name']); ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4 text-muted">Monthly Fee:</div>
                                <div class="col-md-8">৳<?php echo number_format($plan_details['price'], 2); ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4 text-muted">Status:</div>
                                <div class="col-md-8">
                                    <span class="badge bg-<?php echo $member['status'] === 'Active' ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($member['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4 text-muted">Join Date:</div>
                                <div class="col-md-8"><?php echo date('F j, Y', strtotime($member['reg_date'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="progress-card">
                            <h4 class="mb-3">Progress Tracking</h4>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted">Initial Weight</small>
                                    <h5><?php echo $member['ini_weight']; ?> kg</h5>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Current Weight</small>
                                    <h5><?php echo $member['curr_weight']; ?> kg</h5>
                                    <?php 
                                    $weight_diff = $member['curr_weight'] - $member['ini_weight'];
                                    if ($weight_diff !== 0):
                                    ?>
                                    <small class="text-<?php echo $weight_diff > 0 ? 'danger' : 'success'; ?>">
                                        <?php echo $weight_diff > 0 ? '+' : ''; echo $weight_diff; ?> kg
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-muted mt-2">
                                <small>Last Updated: <?php echo date('F j, Y', strtotime($member['progress_date'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Monthly Attendance</h5>
                                <h2 class="card-text"><?php echo $attendance_count; ?> days</h2>
                                <p class="text-muted">This month</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Due Amount</h5>
                                <h2 class="card-text">৳<?php echo number_format($due_amount, 2); ?></h2>
                                <p class="text-muted">Contact staff for payment</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Membership Status</h5>
                                <h2 class="card-text"><?php echo $member['status']; ?></h2>
                                <p class="text-muted">Monthly Fee: ৳<?php echo number_format($member['monthly_fee'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Attendance -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <h4 class="mb-3"><i class="fas fa-calendar-check me-2"></i>Recent Attendance</h4>
                            <?php if ($recent_attendance->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($attendance = $recent_attendance->fetch_assoc()): ?>
                                        <div class="list-group-item border-0 px-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo date('l', strtotime($attendance['check_in_time'])); ?></h6>
                                                    <small class="text-muted"><?php echo date('F j, Y', strtotime($attendance['check_in_time'])); ?></small>
                                                </div>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($attendance['check_in_time'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No recent attendance records found.</p>
                            <?php endif; ?>
                            <div class="mt-3">
                                <a href="attendance.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                        </div>
                    </div>

                    <!-- Reminders -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <h4 class="mb-4">Active Reminders</h4>
                            <?php if ($reminders->num_rows > 0): ?>
                                <?php while ($reminder = $reminders->fetch_assoc()): ?>
                                <div class="reminder-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong><?php echo htmlspecialchars($reminder['name']); ?></strong>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($reminder['date'])); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($reminder['message']); ?></p>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted">No reminders to show</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Announcements -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Announcements</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($announcements->num_rows > 0): ?>
                                    <div class="list-group">
                                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                                            <div class="list-group-item">
                                                <p class="mb-1"><?php echo htmlspecialchars($announcement['message']); ?></p>
                                                <small class="text-muted">Posted on <?php echo date('d M Y', strtotime($announcement['date'])); ?></small>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No announcements to show</p>
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
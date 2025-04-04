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
$stmt = $conn->prepare("SELECT reg_date FROM members WHERE user_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

// Get selected month (default to current month)
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_start = date('Y-m-01', strtotime($selected_month));
$month_end = date('Y-m-t', strtotime($selected_month));

// Get all days in the selected month
$days_in_month = date('t', strtotime($selected_month));
$current_day = date('d');

// Get attendance records for the selected month
$stmt = $conn->prepare("SELECT DATE(check_in_time) as date, check_in_time, check_out_time, present 
                       FROM attendance 
                       WHERE user_id = ? 
                       AND DATE(check_in_time) BETWEEN ? AND ?");
$stmt->bind_param("iss", $member_id, $month_start, $month_end);
$stmt->execute();
$attendance_result = $stmt->get_result();

// Create an array of attendance records indexed by date
$attendance_records = [];
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_records[date('Y-m-d', strtotime($row['date']))] = $row;
}

// Get available months for dropdown (from join date to current month)
$join_date = new DateTime($member['reg_date']);
$current_date = new DateTime();
$available_months = [];

while ($join_date <= $current_date) {
    $available_months[] = $join_date->format('Y-m');
    $join_date->modify('+1 month');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Friends Gym</title>
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
        .attendance-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1rem;
        }
        .attendance-row {
            transition: all 0.3s;
        }
        .attendance-row:hover {
            background-color: #f8f9fa;
        }
        .status-present {
            color: #28a745;
        }
        .status-absent {
            color: #dc3545;
        }
        .month-selector {
            max-width: 200px;
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="attendance.php">
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
                    <h2>Attendance History</h2>
                    <div class="d-flex align-items-center">
                        <select class="form-select month-selector me-3" id="monthSelector">
                            <?php foreach ($available_months as $month): ?>
                                <option value="<?php echo $month; ?>" <?php echo $month === $selected_month ? 'selected' : ''; ?>>
                                    <?php echo date('F Y', strtotime($month . '-01')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Attendance History -->
                <div class="attendance-card">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Create an array of all days in the month
                                $days = [];
                                $current_month = date('Y-m');
                                $today = date('Y-m-d');
                                
                                // If it's the current month, only show days up to today
                                if ($selected_month === $current_month) {
                                    $end_day = date('d');
                                } else {
                                    $end_day = $days_in_month;
                                }
                                
                                for ($day = 1; $day <= $end_day; $day++) {
                                    $days[] = date('Y-m-d', strtotime("$selected_month-$day"));
                                }
                                // Sort days in reverse order
                                rsort($days);
                                
                                foreach ($days as $current_date): 
                                    $is_past = strtotime($current_date) <= strtotime($today);
                                    $attendance = isset($attendance_records[$current_date]) ? $attendance_records[$current_date] : null;
                                ?>
                                <tr class="attendance-row">
                                    <td><?php echo date('d M Y', strtotime($current_date)); ?></td>
                                    <td>
                                        <?php if ($attendance && $attendance['present']): ?>
                                            <?php echo date('h:i A', strtotime($attendance['check_in_time'])); ?>
                                        <?php elseif ($is_past): ?>
                                            <span class="text-muted">None</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($attendance && $attendance['present'] && $attendance['check_out_time']): ?>
                                            <?php echo date('h:i A', strtotime($attendance['check_out_time'])); ?>
                                        <?php elseif ($is_past): ?>
                                            <span class="text-muted">None</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($attendance && $attendance['present']): ?>
                                            <span class="status-present">
                                                <i class="fas fa-check-circle me-1"></i>Present
                                            </span>
                                        <?php elseif ($is_past): ?>
                                            <span class="status-absent">
                                                <i class="fas fa-times-circle me-1"></i>Absent
                                        </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle month selection
            $('#monthSelector').change(function() {
                window.location.href = 'attendance.php?month=' + $(this).val();
            });
        });
    </script>
</body>
</html> 
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
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

// Calculate weight loss/gain
$weight_change = $member['curr_weight'] - $member['ini_weight'];
$weight_change_percentage = ($member['ini_weight'] > 0) ? 
    (($weight_change / $member['ini_weight']) * 100) : 0;

// Get attendance count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE user_id = ? AND present = 1");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc();
$attendance_count = $attendance['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress - Friends Gym</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .progress-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1rem;
        }
        .weight-change {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .weight-change.positive {
            color: #28a745;
        }
        .weight-change.negative {
            color: #dc3545;
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
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-calendar-check me-2"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="progress.php">
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
                    <h2>My Progress</h2>
                    <div class="d-flex align-items-center">
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Weight Progress -->
                <div class="progress-card">
                    <h4 class="mb-4">Weight Progress</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Initial Weight</h5>
                                    <h3 class="mb-0"><?php echo $member['ini_weight']; ?> kg</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Current Weight</h5>
                                    <h3 class="mb-0"><?php echo $member['curr_weight']; ?> kg</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Weight Change</h5>
                                    <h3 class="mb-0 weight-change <?php echo $weight_change < 0 ? 'negative' : 'positive'; ?>">
                                        <?php echo $weight_change > 0 ? '+' : ''; ?><?php echo $weight_change; ?> kg
                                    </h3>
                                    <small class="text-muted">
                                        <?php echo number_format(abs($weight_change_percentage), 1); ?>%
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <canvas id="weightChart"></canvas>
                    </div>
                </div>

                <!-- Attendance Progress -->
                <div class="progress-card">
                    <h4 class="mb-4">Attendance Progress</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Total Attendance</h5>
                                    <h3 class="mb-0"><?php echo $attendance_count; ?> days</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Last Progress Update</h5>
                                    <h3 class="mb-0"><?php echo date('d M Y', strtotime($member['progress_date'])); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Weight Progress Chart
        const ctx = document.getElementById('weightChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Initial Weight', 'Current Weight'],
                datasets: [{
                    label: 'Weight (kg)',
                    data: [<?php echo $member['ini_weight']; ?>, <?php echo $member['curr_weight']; ?>],
                    borderColor: '#007bff',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</body>
</html> 
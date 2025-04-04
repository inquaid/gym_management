<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get current date for default view
$current_date = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;

// Limit date selection to current date or earlier
if ($selected_date > $current_date) {
    $selected_date = $current_date;
}

// Get all members with their attendance status for the selected date
$stmt = $conn->prepare("
    SELECT m.user_id, m.fullname, m.username, m.contact, 
           a.id as attendance_id, a.check_in_time, a.check_out_time, a.present
    FROM members m
    LEFT JOIN attendance a ON m.user_id = a.user_id AND DATE(a.check_in_time) = ?
    WHERE m.status = 'Active'
    ORDER BY m.fullname ASC
");
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$members = $stmt->get_result();

// Get dates with attendance records (for history dropdown)
$stmt = $conn->prepare("
    SELECT DISTINCT DATE(check_in_time) as att_date 
    FROM attendance 
    ORDER BY att_date DESC 
    LIMIT 30
");
$stmt->execute();
$attendance_dates = $stmt->get_result();
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
        .attendance-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1rem;
        }
        .badge-present {
            background-color: #28a745;
        }
        .badge-absent {
            background-color: #dc3545;
        }
        .attendance-status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .dot-present {
            background-color: #28a745;
        }
        .dot-absent {
            background-color: #dc3545;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            width: 100%;
            margin: 20px 0;
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
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="attendance.php">
                                <i class="fas fa-calendar-check me-2"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="members.php">
                                <i class="fas fa-users me-2"></i>Members
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="equipment.php">
                                <i class="fas fa-dumbbell me-2"></i>Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff.php">
                                <i class="fas fa-user-tie me-2"></i>Staff
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
                            <a class="nav-link" href="shop.php">
                                <i class="fas fa-shopping-cart me-2"></i>Shop
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
                    <h2>Attendance Management</h2>
                    <div class="d-flex align-items-center">
                        <div class="text-muted me-3">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Date Selection and Filters -->
                <div class="attendance-card">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4>
                                <?php if ($selected_date == $current_date): ?>
                                    Today's Attendance
                                <?php else: ?>
                                    Attendance for <?php echo date('F j, Y', strtotime($selected_date)); ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <form id="dateForm" class="me-2 d-flex">
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="date" id="attendance-date" 
                                               value="<?php echo $selected_date; ?>" 
                                               max="<?php echo $current_date; ?>">
                                        <button class="btn btn-outline-primary" type="submit">View</button>
                                    </div>
                                </form>
                                
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="historyDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        History
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="historyDropdown">
                                        <?php if ($attendance_dates->num_rows > 0): ?>
                                            <?php while($date = $attendance_dates->fetch_assoc()): ?>
                                                <li><a class="dropdown-item <?php echo ($date['att_date'] == $selected_date) ? 'active' : ''; ?>" 
                                                       href="?date=<?php echo $date['att_date']; ?>">
                                                    <?php echo date('F j, Y', strtotime($date['att_date'])); ?>
                                                </a></li>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <li><a class="dropdown-item disabled">No attendance records</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Stats Summary -->
                <div class="attendance-card">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-8">
                            <div class="d-flex">
                                <div class="me-4">
                                    <span class="attendance-status-dot dot-present"></span>
                                    <span>Present</span>
                                </div>
                                <div>
                                    <span class="attendance-status-dot dot-absent"></span>
                                    <span>Absent</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($selected_date == $current_date): ?>
                                <button id="refresh-btn" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="loading-spinner" id="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Updating attendance...</p>
                    </div>
                    
                    <div id="attendance-table-container">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Username</th>
                                        <th>Contact</th>
                                        <th>Check-In</th>
                                        <th>Check-Out</th>
                                        <th>Status</th>
                                        <?php if ($selected_date == $current_date): ?>
                                            <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($members->num_rows > 0): ?>
                                        <?php while ($member = $members->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                                <td><?php echo htmlspecialchars($member['username']); ?></td>
                                                <td><?php echo htmlspecialchars($member['contact']); ?></td>
                                                <td>
                                                    <?php if (!empty($member['check_in_time'])): ?>
                                                        <?php echo date('h:i A', strtotime($member['check_in_time'])); ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($member['check_out_time'])): ?>
                                                        <?php echo date('h:i A', strtotime($member['check_out_time'])); ?>
                                                    <?php elseif (!empty($member['check_in_time'])): ?>
                                                        <span class="text-warning">Missing</span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($member['check_in_time'])): ?>
                                                        <span class="badge badge-present">Present</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-absent">Absent</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($selected_date == $current_date): ?>
                                                    <td>
                                                        <?php if (empty($member['check_in_time'])): ?>
                                                            <button class="btn btn-sm btn-success check-in-btn" 
                                                                    data-user-id="<?php echo $member['user_id']; ?>">
                                                                <i class="fas fa-sign-in-alt me-1"></i> Check In
                                                            </button>
                                                        <?php elseif (empty($member['check_out_time'])): ?>
                                                            <button class="btn btn-sm btn-warning check-out-btn" 
                                                                    data-user-id="<?php echo $member['user_id']; ?>"
                                                                    data-attendance-id="<?php echo $member['attendance_id']; ?>">
                                                                <i class="fas fa-sign-out-alt me-1"></i> Check Out
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-secondary" disabled>
                                                                <i class="fas fa-check me-1"></i> Completed
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?php echo ($selected_date == $current_date) ? '7' : '6'; ?>" class="text-center">
                                                No members found
                                            </td>
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
    <script>
        // Handle form submission
        document.getElementById('dateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const date = document.getElementById('attendance-date').value;
            window.location.href = 'attendance.php?date=' + date;
        });

        // AJAX for Check-In button
        $(document).on('click', '.check-in-btn', function() {
            const userId = $(this).data('user-id');
            const btn = $(this);
            
            $('#loading-spinner').show();
            
            $.ajax({
                url: '../actions/attendance/check_in.php',
                type: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        refreshAttendanceTable();
                    } else {
                        alert('Error: ' + response.message);
                        $('#loading-spinner').hide();
                    }
                },
                error: function() {
                    alert('An error occurred while processing the check-in.');
                    $('#loading-spinner').hide();
                }
            });
        });

        // AJAX for Check-Out button
        $(document).on('click', '.check-out-btn', function() {
            const userId = $(this).data('user-id');
            const attendanceId = $(this).data('attendance-id');
            const btn = $(this);
            
            $('#loading-spinner').show();
            
            $.ajax({
                url: '../actions/attendance/check_out.php',
                type: 'POST',
                data: { user_id: userId, attendance_id: attendanceId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        refreshAttendanceTable();
                    } else {
                        alert('Error: ' + response.message);
                        $('#loading-spinner').hide();
                    }
                },
                error: function() {
                    alert('An error occurred while processing the check-out.');
                    $('#loading-spinner').hide();
                }
            });
        });

        // Refresh button functionality
        $('#refresh-btn').on('click', function() {
            refreshAttendanceTable();
        });

        // Function to refresh the attendance table
        function refreshAttendanceTable() {
            $('#loading-spinner').show();
            
            $.ajax({
                url: 'get_attendance_table.php',
                type: 'GET',
                data: { date: '<?php echo $selected_date; ?>' },
                success: function(html) {
                    $('#attendance-table-container').html(html);
                    $('#loading-spinner').hide();
                },
                error: function() {
                    alert('An error occurred while refreshing the table.');
                    $('#loading-spinner').hide();
                }
            });
        }
    </script>
</body>
</html> 
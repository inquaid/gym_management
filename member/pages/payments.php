<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is member
if (!isLoggedIn() || $_SESSION['role'] !== 'member') {
    header("Location: ../login.php");
    exit;
}

// Get member information with plan details
$member_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT m.*, mp.name as plan_name, mp.price as plan_price 
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
$due_amount = max(0, $member['plan_price'] - $total_paid);

// Get payment history
$stmt = $conn->prepare("SELECT p.*, o.total_amount as order_total, 
                              CASE WHEN o.order_id IS NOT NULL THEN 'Shop Purchase' ELSE 'Membership Payment' END as payment_type 
                       FROM payments p 
                       LEFT JOIN orders o ON p.order_id = o.order_id 
                       WHERE p.user_id = ? 
                       ORDER BY p.payment_date DESC");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$payments = $stmt->get_result();

// Calculate days until next payment
$next_payment_date = "";
$days_remaining = 0;

// Get the latest membership payment
$stmt = $conn->prepare("SELECT * FROM payments WHERE user_id = ? AND order_id IS NULL ORDER BY payment_date DESC LIMIT 1");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$latest_payment = $stmt->get_result()->fetch_assoc();

if ($latest_payment) {
    // Calculate expiry date (30 days from payment date)
    $payment_date = new DateTime($latest_payment['payment_date']);
    $expiry_date = clone $payment_date;
    $expiry_date->modify('+30 days');
    $days_remaining = max(0, $expiry_date->diff(new DateTime())->days);
    $next_payment_date = $expiry_date->format('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Friends Gym</title>
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
        .payment-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1rem;
        }
        .membership-info {
            border-radius: 10px;
            background: #f8f9fa;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .membership-detail {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .membership-detail i {
            width: 30px;
            color: #2c3e50;
        }
        .membership-status {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            display: inline-block;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-expired {
            background-color: #f8d7da;
            color: #721c24;
        }
        .payment-history {
            margin-top: 2rem;
        }
        .progress-container {
            margin: 1.5rem 0;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
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
                            <a class="nav-link active" href="payments.php">
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
                    <h2>Payments</h2>
                    <div class="d-flex align-items-center">
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Membership Section -->
                <div class="payment-card">
                    <h4 class="mb-4">Membership Information</h4>
                    
                    <div class="membership-info">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Membership Status</h5>
                            <?php if ($member['status'] === 'Active'): ?>
                                <span class="membership-status status-active">
                                    <i class="fas fa-check-circle me-2"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="membership-status status-expired">
                                    <i class="fas fa-times-circle me-2"></i>Expired
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="membership-detail">
                            <i class="fas fa-id-card me-3"></i>
                            <div>
                                <small class="text-muted">Membership ID</small>
                                <div><?php echo $member['user_id']; ?></div>
                            </div>
                        </div>
                        
                        <div class="membership-detail">
                            <i class="fas fa-tag me-3"></i>
                            <div>
                                <small class="text-muted">Plan</small>
                                <div><?php echo htmlspecialchars($member['plan_name']); ?></div>
                            </div>
                        </div>

                        <div class="membership-detail">
                            <i class="fas fa-money-bill me-3"></i>
                            <div>
                                <small class="text-muted">Monthly Fee</small>
                                <div><?php echo number_format($member['plan_price'], 2); ?> Taka</div>
                            </div>
                        </div>

                        <div class="membership-detail">
                            <i class="fas fa-exclamation-circle me-3"></i>
                            <div>
                                <small class="text-muted">Due Amount</small>
                                <div class="<?php echo $due_amount > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo number_format($due_amount, 2); ?> Taka
                                    <?php if ($due_amount > 0): ?>
                                        <small class="ms-2">(Please contact staff for payment)</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($next_payment_date): ?>
                        <div class="membership-detail">
                            <i class="fas fa-calendar me-3"></i>
                            <div>
                                <small class="text-muted">Next Payment Due</small>
                                <div><?php echo date('F j, Y', strtotime($next_payment_date)); ?></div>
                            </div>
                        </div>
                        
                        <div class="progress-container">
                            <div class="d-flex justify-content-between mb-2">
                                <small>Payment Period Progress</small>
                                <small><?php echo $days_remaining; ?> days remaining</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar <?php echo $days_remaining <= 5 ? 'bg-danger' : 'bg-success'; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo ((30 - $days_remaining) / 30) * 100; ?>%" 
                                     aria-valuenow="<?php echo 30 - $days_remaining; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="30">
                                </div>
                            </div>
                            <?php if ($days_remaining <= 5): ?>
                            <div class="text-danger mt-2">
                                <small><i class="fas fa-exclamation-triangle me-1"></i>Payment due soon</small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="payment-card">
                    <h4 class="mb-4">Payment History</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payments->num_rows > 0): ?>
                                    <?php while ($payment = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo $payment['payment_type']; ?></td>
                                        <td><?php echo number_format($payment['amount'], 2); ?> Taka</td>
                                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $payment['status'] === 'Success' ? 'success' : 
                                                    ($payment['status'] === 'Pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo htmlspecialchars($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No payment history found.</td>
                                    </tr>
                                <?php endif; ?>
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
            // Handle payment form submission
            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#paymentModal').modal('hide');
                        alert('Membership renewed successfully!');
                        location.reload();
                    },
                    error: function(xhr) {
                        alert('Error renewing membership: ' + (xhr.responseJSON ? xhr.responseJSON.error : 'Unknown error'));
                    }
                });
            });
        });
    </script>
</body>
</html> 
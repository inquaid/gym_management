<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get all active members with their plan details
$members_query = "
    SELECT m.user_id, m.fullname, m.due_amount, m.reg_date, m.status,
           p.name as plan_name, p.price as plan_price
    FROM members m
    JOIN membership_plan p ON m.plan_id = p.id
    WHERE m.status = 'Active'
    ORDER BY m.fullname
";
$members_result = $conn->query($members_query);

// Check for success or error messages in session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear the session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Get current month and year for the heading
$current_month = date('F');
$current_year = date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Friends Gym</title>
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
        .form-label {
            font-weight: 500;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .status-paid {
            color: #198754;
        }
        .status-partial {
            color: #fd7e14;
        }
        .status-due {
            color: #dc3545;
        }
        .payment-history {
            max-height: 300px;
            overflow-y: auto;
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
                    <h2>Payment Management</h2>
                    <div class="text-muted">
                        <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Current Month Payment Summary -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Member Payments - <?php echo $current_month; ?> <?php echo $current_year; ?></h5>
                    </div>
                    
                    <?php if ($members_result && $members_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Membership Plan</th>
                                        <th>Monthly Fee</th>
                                        <th>Due Amount</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($member = $members_result->fetch_assoc()): ?>
                                        <?php
                                        // Determine payment status
                                        $status = '';
                                        $status_class = '';
                                        
                                        if ($member['due_amount'] <= 0) {
                                            $status = 'Paid';
                                            $status_class = 'status-paid';
                                        } else if ($member['due_amount'] < $member['plan_price']) {
                                            $status = 'Partial';
                                            $status_class = 'status-partial';
                                        } else {
                                            $status = 'Due';
                                            $status_class = 'status-due';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($member['plan_name']); ?></td>
                                            <td><?php echo number_format($member['plan_price'], 2); ?> Tk</td>
                                            <td><?php echo number_format($member['due_amount'], 2); ?> Tk</td>
                                            <td>
                                                <span class="<?php echo $status_class; ?>">
                                                    <?php if ($status === 'Paid'): ?>
                                                        <i class="fas fa-check-circle me-1"></i>
                                                    <?php elseif ($status === 'Partial'): ?>
                                                        <i class="fas fa-clock me-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                    <?php endif; ?>
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($status !== 'Paid'): ?>
                                                        <button class="btn btn-sm btn-outline-primary me-1 process-payment" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#processPaymentModal"
                                                                data-id="<?php echo $member['user_id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($member['fullname']); ?>"
                                                                data-due="<?php echo $member['due_amount']; ?>"
                                                                data-plan="<?php echo htmlspecialchars($member['plan_name']); ?>">
                                                            <i class="fas fa-credit-card"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-info view-history" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#paymentHistoryModal"
                                                            data-id="<?php echo $member['user_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($member['fullname']); ?>">
                                                        <i class="fas fa-history"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No active members found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Process Payment Modal -->
    <div class="modal fade" id="processPaymentModal" tabindex="-1" aria-labelledby="processPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="processPaymentModalLabel">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="paymentForm" action="../actions/payments/process_payment.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="payment_user_id" name="user_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Member Name</label>
                            <div class="form-control bg-light" id="payment_member_name" readonly></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Membership Plan</label>
                            <div class="form-control bg-light" id="payment_plan_name" readonly></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Due Amount</label>
                            <div class="form-control bg-light" id="payment_due_amount" readonly></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_amount" class="form-label required-field">Payment Amount</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="payment_amount" name="amount" min="1" step="1" required>
                                <span class="input-group-text">Tk</span>
                            </div>
                            <small class="text-muted">Enter the amount being paid by the member</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_method" class="form-label required-field">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Mobile Banking">Mobile Banking</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Process Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment History Modal -->
    <div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentHistoryModalLabel">Payment History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="payment_history_member_info" class="mb-3"></div>
                    
                    <div id="payment_history_container" class="payment-history">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading payment history...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Process Payment
            const processPaymentButtons = document.querySelectorAll('.process-payment');
            
            processPaymentButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const memberName = this.getAttribute('data-name');
                    const dueAmount = this.getAttribute('data-due');
                    const planName = this.getAttribute('data-plan');
                    
                    document.getElementById('payment_user_id').value = userId;
                    document.getElementById('payment_member_name').textContent = memberName;
                    document.getElementById('payment_plan_name').textContent = planName;
                    document.getElementById('payment_due_amount').textContent = parseFloat(dueAmount).toFixed(2) + ' Tk';
                    
                    // Set default payment amount to due amount
                    document.getElementById('payment_amount').value = dueAmount;
                    document.getElementById('payment_amount').max = dueAmount;
                });
            });
            
            // View Payment History
            const viewHistoryButtons = document.querySelectorAll('.view-history');
            
            viewHistoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const memberName = this.getAttribute('data-name');
                    
                    document.getElementById('payment_history_member_info').innerHTML = 
                        `<h6 class="mb-2">Member: ${memberName}</h6>`;
                    
                    // Reset container
                    document.getElementById('payment_history_container').innerHTML = `
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading payment history...</p>
                        </div>
                    `;
                    
                    // Fetch payment history
                    fetch(`../actions/payments/get_payment_history.php?user_id=${userId}`)
                        .then(response => response.json())
                        .then(data => {
                            const container = document.getElementById('payment_history_container');
                            
                            if (data.status === 'success' && data.payments.length > 0) {
                                let html = `
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Status</th>
                                                    <th>Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                `;
                                
                                data.payments.forEach(payment => {
                                    let statusClass = '';
                                    let statusIcon = '';
                                    
                                    if (payment.status === 'Success') {
                                        statusClass = 'status-paid';
                                        statusIcon = '<i class="fas fa-check-circle me-1"></i>';
                                    } else if (payment.status === 'Pending') {
                                        statusClass = 'status-partial';
                                        statusIcon = '<i class="fas fa-clock me-1"></i>';
                                    } else {
                                        statusClass = 'status-due';
                                        statusIcon = '<i class="fas fa-times-circle me-1"></i>';
                                    }
                                    
                                    const paymentType = payment.order_id ? 'Product Purchase' : 'Membership Fee';
                                    
                                    html += `
                                        <tr>
                                            <td>${new Date(payment.payment_date).toLocaleString()}</td>
                                            <td>${parseFloat(payment.amount).toFixed(2)} Tk</td>
                                            <td>${payment.payment_method}</td>
                                            <td><span class="${statusClass}">${statusIcon}${payment.status}</span></td>
                                            <td>${paymentType}</td>
                                        </tr>
                                    `;
                                });
                                
                                html += '</tbody></table></div>';
                                container.innerHTML = html;
                            } else {
                                container.innerHTML = `
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> No payment history found for this member.
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            const container = document.getElementById('payment_history_container');
                            container.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i> An error occurred while loading payment history.
                                </div>
                            `;
                        });
                });
            });
        });
    </script>
</body>
</html> 
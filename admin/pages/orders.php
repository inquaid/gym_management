<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM orders";
$count_result = $conn->query($count_query);
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Get orders data with member details
$query = "
    SELECT o.*, m.fullname, m.contact, 
           COUNT(oi.item_id) as item_count
    FROM orders o
    JOIN members m ON o.user_id = m.user_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
    LIMIT $offset, $limit
";
$result = $conn->query($query);

// Check for success or error messages in session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear the session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Friends Gym</title>
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
        .status-pending {
            color: #fd7e14;
        }
        .status-completed {
            color: #198754;
        }
        .status-cancelled {
            color: #dc3545;
        }
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .order-item:last-child {
            border-bottom: none;
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
                    <h2>Orders Management</h2>
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

                <!-- Orders List -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Orders List</h5>
                        <div>
                            <span class="badge bg-secondary">Total: <?php echo $total; ?></span>
                        </div>
                    </div>
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Member</th>
                                        <th>Contact</th>
                                        <th>Order Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($order['contact']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $order['item_count']; ?> items</span>
                                            </td>
                                            <td><?php echo number_format($order['total_amount'], 2); ?> Tk</td>
                                            <td>
                                                <?php if ($order['status'] === 'Pending'): ?>
                                                    <span class="status-pending">
                                                        <i class="fas fa-clock me-1"></i> Pending
                                                    </span>
                                                <?php elseif ($order['status'] === 'Completed'): ?>
                                                    <span class="status-completed">
                                                        <i class="fas fa-check-circle me-1"></i> Completed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-cancelled">
                                                        <i class="fas fa-times-circle me-1"></i> Cancelled
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary me-1 view-order" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewOrderModal"
                                                            data-id="<?php echo $order['order_id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($order['status'] === 'Pending'): ?>
                                                        <button class="btn btn-sm btn-outline-success me-1 complete-order" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#completeOrderModal"
                                                                data-id="<?php echo $order['order_id']; ?>">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger cancel-order" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#cancelOrderModal"
                                                                data-id="<?php echo $order['order_id']; ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No orders found</p>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOrderModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Order ID:</strong> <span id="view_order_id"></span></p>
                            <p><strong>Order Date:</strong> <span id="view_order_date"></span></p>
                            <p><strong>Status:</strong> <span id="view_order_status"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Member:</strong> <span id="view_member_name"></span></p>
                            <p><strong>Contact:</strong> <span id="view_member_contact"></span></p>
                            <p><strong>Total Amount:</strong> <span id="view_order_total"></span></p>
                        </div>
                    </div>
                    <hr>
                    <h6>Order Items</h6>
                    <div id="order_items_container">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading order items...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <div id="action_buttons">
                        <!-- Action buttons will be added dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete Order Modal -->
    <div class="modal fade" id="completeOrderModal" tabindex="-1" aria-labelledby="completeOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="completeOrderModalLabel">Complete Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark order #<strong id="complete_order_id"></strong> as completed?</p>
                    <p>Please confirm that payment has been received for this order.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmCompleteBtn">Complete Order</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel order #<strong id="cancel_order_id"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Order</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">Yes, Cancel Order</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View Order Details
            const viewButtons = document.querySelectorAll('.view-order');
            let currentOrderId = null;
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-id');
                    currentOrderId = orderId;
                    
                    // Clear previous data
                    document.getElementById('order_items_container').innerHTML = `
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading order items...</p>
                        </div>
                    `;
                    
                    // Fetch order details
                    fetch('../actions/orders/get_order_details.php?id=' + orderId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const order = data.order;
                                const items = data.items;
                                
                                // Update order details
                                document.getElementById('view_order_id').textContent = order.order_id;
                                document.getElementById('view_order_date').textContent = new Date(order.order_date).toLocaleString();
                                document.getElementById('view_member_name').textContent = order.fullname;
                                document.getElementById('view_member_contact').textContent = order.contact;
                                document.getElementById('view_order_total').textContent = parseFloat(order.total_amount).toFixed(2) + ' Tk';
                                document.getElementById('view_order_status').textContent = order.status;
                                
                                // Update status class
                                const statusElement = document.getElementById('view_order_status');
                                statusElement.innerHTML = '';
                                
                                if (order.status === 'Pending') {
                                    statusElement.innerHTML = '<span class="status-pending"><i class="fas fa-clock me-1"></i> Pending</span>';
                                } else if (order.status === 'Completed') {
                                    statusElement.innerHTML = '<span class="status-completed"><i class="fas fa-check-circle me-1"></i> Completed</span>';
                                } else {
                                    statusElement.innerHTML = '<span class="status-cancelled"><i class="fas fa-times-circle me-1"></i> Cancelled</span>';
                                }
                                
                                // Update action buttons
                                const actionButtons = document.getElementById('action_buttons');
                                actionButtons.innerHTML = '';
                                
                                if (order.status === 'Pending') {
                                    const completeBtn = document.createElement('button');
                                    completeBtn.className = 'btn btn-success me-2';
                                    completeBtn.innerHTML = '<i class="fas fa-check me-2"></i>Complete Order';
                                    completeBtn.onclick = function() {
                                        const modal = bootstrap.Modal.getInstance(document.getElementById('viewOrderModal'));
                                        modal.hide();
                                        document.getElementById('complete_order_id').textContent = order.order_id;
                                        const completeModal = new bootstrap.Modal(document.getElementById('completeOrderModal'));
                                        completeModal.show();
                                    };
                                    
                                    const cancelBtn = document.createElement('button');
                                    cancelBtn.className = 'btn btn-danger';
                                    cancelBtn.innerHTML = '<i class="fas fa-times me-2"></i>Cancel Order';
                                    cancelBtn.onclick = function() {
                                        const modal = bootstrap.Modal.getInstance(document.getElementById('viewOrderModal'));
                                        modal.hide();
                                        document.getElementById('cancel_order_id').textContent = order.order_id;
                                        const cancelModal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
                                        cancelModal.show();
                                    };
                                    
                                    actionButtons.appendChild(completeBtn);
                                    actionButtons.appendChild(cancelBtn);
                                }
                                
                                // Render order items
                                const orderItemsContainer = document.getElementById('order_items_container');
                                if (items.length > 0) {
                                    let itemsHtml = '<div class="table-responsive mt-3">';
                                    itemsHtml += '<table class="table table-bordered table-striped">';
                                    itemsHtml += '<thead><tr><th>#</th><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr></thead>';
                                    itemsHtml += '<tbody>';
                                    
                                    items.forEach((item, index) => {
                                        itemsHtml += `<tr>
                                            <td>${index + 1}</td>
                                            <td>${item.name}</td>
                                            <td>${parseFloat(item.price).toFixed(2)} Tk</td>
                                            <td>${item.quantity}</td>
                                            <td>${(parseFloat(item.price) * item.quantity).toFixed(2)} Tk</td>
                                        </tr>`;
                                    });
                                    
                                    itemsHtml += '</tbody></table></div>';
                                    orderItemsContainer.innerHTML = itemsHtml;
                                } else {
                                    orderItemsContainer.innerHTML = '<p class="text-muted">No items found for this order</p>';
                                }
                            } else {
                                document.getElementById('order_items_container').innerHTML = `
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle me-2"></i> ${data.message}
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('order_items_container').innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i> An error occurred while loading order details
                                </div>
                            `;
                        });
                });
            });
            
            // Complete Order
            const completeButtons = document.querySelectorAll('.complete-order');
            const confirmCompleteBtn = document.getElementById('confirmCompleteBtn');
            let completeOrderId = null;
            
            completeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    completeOrderId = this.getAttribute('data-id');
                    document.getElementById('complete_order_id').textContent = completeOrderId;
                });
            });
            
            confirmCompleteBtn.addEventListener('click', function() {
                if (completeOrderId) {
                    const formData = new FormData();
                    formData.append('id', completeOrderId);
                    formData.append('status', 'Completed');
                    
                    fetch('../actions/orders/update_order_status.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Close modal and reload page
                            const modal = bootstrap.Modal.getInstance(document.getElementById('completeOrderModal'));
                            modal.hide();
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the order status.');
                    });
                }
            });
            
            // Cancel Order
            const cancelButtons = document.querySelectorAll('.cancel-order');
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');
            let cancelOrderId = null;
            
            cancelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    cancelOrderId = this.getAttribute('data-id');
                    document.getElementById('cancel_order_id').textContent = cancelOrderId;
                });
            });
            
            confirmCancelBtn.addEventListener('click', function() {
                if (cancelOrderId) {
                    const formData = new FormData();
                    formData.append('id', cancelOrderId);
                    formData.append('status', 'Cancelled');
                    
                    fetch('../actions/orders/update_order_status.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Close modal and reload page
                            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal'));
                            modal.hide();
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the order status.');
                    });
                }
            });
        });
    </script>
</body>
</html> 
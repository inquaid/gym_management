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

// Get available products
$stmt = $conn->prepare("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY name ASC");
$stmt->execute();
$products = $stmt->get_result();

// Get recent orders
$stmt = $conn->prepare("
    SELECT o.*, 
           p.name as product_name,
           oi.quantity,
           oi.price as item_price
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$recent_orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Friends Gym</title>
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
        .shop-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1rem;
        }
        .product-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            transition: all 0.3s;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,.1);
        }
        .product-image {
            height: 200px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-image i {
            font-size: 4rem;
            color: #6c757d;
        }
        .product-details {
            padding: 1rem;
        }
        .badge-stock {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .status-badge {
            font-size: 0.8rem;
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
                            <a class="nav-link active" href="shop.php">
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
                    <h2>Shop</h2>
                    <div class="d-flex align-items-center">
                        <a href="profile.php" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Products Section -->
                <div class="shop-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Available Products</h4>
                        <a href="#orderHistory" class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i>View Order History
                        </a>
                    </div>
                    <div class="row">
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="product-card position-relative">
                                        <span class="badge bg-success badge-stock"><?php echo $product['stock_quantity']; ?> in stock</span>
                                        <div class="product-image">
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid">
                                            <?php else: ?>
                                                <i class="fas fa-dumbbell"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-details">
                                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <p class="text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">৳<?php echo number_format($product['price'], 2); ?></span>
                                                <button class="btn btn-primary btn-sm buy-product" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#purchaseModal" 
                                                        data-id="<?php echo $product['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        data-price="<?php echo $product['price']; ?>"
                                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                                    <i class="fas fa-shopping-cart me-1"></i> Buy
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    No products are currently available. Please check back later.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order History -->
                <div class="shop-card" id="orderHistory">
                    <h4 class="mb-4">Recent Orders</h4>
                    <div id="orderHistoryContent">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Total Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_orders->num_rows > 0): ?>
                                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                <td><?php echo $order['quantity']; ?></td>
                                                <td>৳<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($order['status']) {
                                                        case 'Pending':
                                                            $status_class = 'bg-warning';
                                                            break;
                                                        case 'Completed':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        case 'Cancelled':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <?php echo $order['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No orders found</td>
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

    <!-- Purchase Modal -->
    <div class="modal fade" id="purchaseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Purchase Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="purchaseForm" action="../actions/shop/process_order.php" method="POST">
                    <div class="modal-body">
                        <p>You are about to purchase:</p>
                        <h5 id="productName"></h5>
                        <p>Price: ৳<span id="productPrice"></span></p>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1">
                            <small class="text-muted">Maximum available: <span id="maxQuantity"></span></small>
                        </div>
                        
                        <input type="hidden" id="productId" name="product_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Purchase modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            var purchaseModal = document.getElementById('purchaseModal');
            if (purchaseModal) {
                purchaseModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var productId = button.getAttribute('data-id');
                    var productName = button.getAttribute('data-name');
                    var productPrice = button.getAttribute('data-price');
                    var productStock = button.getAttribute('data-stock');
                    
                    document.getElementById('productId').value = productId;
                    document.getElementById('productName').textContent = productName;
                    document.getElementById('productPrice').textContent = productPrice;
                    document.getElementById('maxQuantity').textContent = productStock;
                    
                    var quantityInput = document.getElementById('quantity');
                    quantityInput.max = productStock;
                });
            }
        });

        // Handle form submission with AJAX
        $('#purchaseForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response) {
                    // Close the modal
                    $('#purchaseModal').modal('hide');
                    
                    // Show success message
                    var alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                  'Order placed successfully. Your order is pending approval.' +
                                  '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                  '</div>';
                    $('.main-content').prepend(alertHtml);
                    
                    // Update order history
                    $.ajax({
                        url: 'get_order_history.php',
                        type: 'GET',
                        success: function(historyHtml) {
                            $('#orderHistoryContent').html(historyHtml);
                        }
                    });
                },
                error: function() {
                    // Show error message
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                  'Error processing order. Please try again.' +
                                  '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                  '</div>';
                    $('.main-content').prepend(alertHtml);
                }
            });
        });
    </script>
</body>
</html> 
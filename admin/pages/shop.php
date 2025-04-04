<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get quick stats
$products_count_query = "SELECT COUNT(*) as total FROM products";
$products_result = $conn->query($products_count_query);
$products_count = $products_result->fetch_assoc()['total'];

$orders_count_query = "SELECT COUNT(*) as total FROM orders";
$orders_result = $conn->query($orders_count_query);
$orders_count = $orders_result->fetch_assoc()['total'];

$pending_orders_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'Pending'";
$pending_result = $conn->query($pending_orders_query);
$pending_orders = $pending_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Management - Friends Gym</title>
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
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1.5rem;
        }
        .shop-card {
            transition: all 0.3s;
            border-radius: 10px;
            border: none;
        }
        .shop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,.1);
        }
        .shop-card .card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
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
                    <h2>Shop Management</h2>
                    <div class="text-muted">
                        <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                    </div>
                </div>

                <!-- Shop Management Options -->
                <div class="row mt-4">
                    <div class="col-md-6 mb-4">
                        <div class="card shop-card h-100">
                            <div class="card-body text-center p-5">
                                <div class="card-icon text-primary">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <h4 class="mb-3">Products Management</h4>
                                <p class="text-muted mb-4">Manage products, stock levels, and pricing information</p>
                                <div class="d-flex justify-content-center mb-4">
                                    <span class="badge bg-primary p-2 me-2">
                                        <i class="fas fa-box me-1"></i> <?php echo $products_count; ?> Products
                                    </span>
                                </div>
                                <a href="products.php" class="btn btn-primary px-4">
                                    <i class="fas fa-arrow-right me-2"></i>Go to Products
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card shop-card h-100">
                            <div class="card-body text-center p-5">
                                <div class="card-icon text-success">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h4 class="mb-3">Orders Management</h4>
                                <p class="text-muted mb-4">View and process customer orders, update order status</p>
                                <div class="d-flex justify-content-center mb-4">
                                    <span class="badge bg-success p-2 me-2">
                                        <i class="fas fa-shopping-cart me-1"></i> <?php echo $orders_count; ?> Total Orders
                                    </span>
                                    <?php if ($pending_orders > 0): ?>
                                    <span class="badge bg-warning p-2">
                                        <i class="fas fa-clock me-1"></i> <?php echo $pending_orders; ?> Pending
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <a href="orders.php" class="btn btn-success px-4">
                                    <i class="fas fa-arrow-right me-2"></i>Go to Orders
                                </a>
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
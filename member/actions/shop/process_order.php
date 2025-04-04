<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is member
if (!isLoggedIn() || $_SESSION['role'] !== 'member') {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $order_date = date('Y-m-d H:i:s');
    $status = 'Pending';

    // Validate input
    if (empty($product_id) || empty($quantity)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../../pages/shop.php");
        exit;
    }

    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: ../../pages/shop.php");
        exit;
    }

    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        $_SESSION['error'] = "Insufficient stock available.";
        header("Location: ../../pages/shop.php");
        exit;
    }

    // Calculate total amount
    $total_amount = $product['price'] * $quantity;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, order_date, total_amount, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $user_id, $order_date, $total_amount, $status);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Add order item
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $product['price']);
        $stmt->execute();

        // Update product stock
        $new_stock = $product['stock_quantity'] - $quantity;
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $product_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Order placed successfully. Your order is pending approval.";

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error processing order. Please try again.";
    }

    $stmt->close();
}

header("Location: ../../pages/shop.php");
exit; 
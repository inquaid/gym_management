<?php
require_once '../../../session.php';
require_once '../../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate inputs
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    
    // Basic validation
    $errors = [];
    
    if ($id <= 0) {
        $errors[] = 'Invalid product ID';
    }
    
    if (empty($name)) {
        $errors[] = 'Product name is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero';
    }
    
    if ($stock_quantity < 0) {
        $errors[] = 'Stock quantity cannot be negative';
    }
    
    // If there are errors, redirect back with error message
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode(', ', $errors);
        header('Location: ../../pages/products.php');
        exit;
    }
    
    // Check if product exists
    $check_query = "SELECT id FROM products WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = 'Product not found';
        header('Location: ../../pages/products.php');
        exit;
    }
    
    // Update product in database
    $query = "UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssdii', $name, $description, $price, $stock_quantity, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Product updated successfully';
    } else {
        $_SESSION['error_message'] = 'Failed to update product: ' . $conn->error;
    }
    
    header('Location: ../../pages/products.php');
    exit;
} else {
    // If not a POST request, redirect to products page
    header('Location: ../../pages/products.php');
    exit;
}
?> 
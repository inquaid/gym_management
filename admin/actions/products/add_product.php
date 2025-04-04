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
    // Validate inputs
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    
    // Basic validation
    $errors = [];
    
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
    
    // Insert product into database
    $query = "INSERT INTO products (name, description, price, stock_quantity) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssdi', $name, $description, $price, $stock_quantity);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Product added successfully';
    } else {
        $_SESSION['error_message'] = 'Failed to add product: ' . $conn->error;
    }
    
    header('Location: ../../pages/products.php');
    exit;
} else {
    // If not a POST request, redirect to products page
    header('Location: ../../pages/products.php');
    exit;
}
?> 
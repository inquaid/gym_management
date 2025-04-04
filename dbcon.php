<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gym_db');

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to redirect based on user role
function redirectBasedOnRole() {
    if (!headers_sent()) {
        $role = getUserRole();
        switch ($role) {
            case 'admin':
                header("Location: admin/pages/dashboard.php");
                break;
            case 'staff':
                header("Location: staff/pages/dashboard.php");
                break;
            case 'member':
                header("Location: member/pages/dashboard.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();
    } else {
        // If headers are already sent, use JavaScript for redirection
        echo "<script>window.location.href = 'index.php';</script>";
        exit();
    }
}
?> 
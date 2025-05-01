<?php
// Start session and include required files
require_once 'session.php';
require_once 'dbcon.php';

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
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
            logout();
    }
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Check admin login with hardcoded credentials
    if ($username === 'admin' && $password === 'admin123') {
        setUserSession(1, 'admin', 'admin');
        if (!headers_sent()) {
            header("Location: admin/pages/dashboard.php");
        } else {
            echo "<script>window.location.href = 'admin/pages/dashboard.php';</script>";
        }
        exit();
    }
    
    // Check staff login
    $staff_query = "SELECT * FROM staffs WHERE username = '$username' AND password = '$password'";
    $staff_result = $conn->query($staff_query);
    
    if ($staff_result->num_rows > 0) {
        $staff = $staff_result->fetch_assoc();
        setUserSession($staff['user_id'], 'staff', $staff['username']);
        if (!headers_sent()) {
            header("Location: staff/pages/dashboard.php");
        } else {
            echo "<script>window.location.href = 'staff/pages/dashboard.php';</script>";
        }
        exit();
    }
    
    // Check member login
    $member_query = "SELECT * FROM members WHERE username = '$username' AND password = '$password'";
    $member_result = $conn->query($member_query);
    
    if ($member_result->num_rows > 0) {
        $member = $member_result->fetch_assoc();
        setUserSession($member['user_id'], 'member', $member['username']);
        if (!headers_sent()) {
            header("Location: member/pages/dashboard.php");
        } else {
            echo "<script>window.location.href = 'member/pages/dashboard.php';</script>";
        }
        exit();
    }
    
    $error = "Invalid username or password";
}

// Handle messages
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'logged_out':
            $success = "You have been successfully logged out.";
            break;
        case 'timeout':
            $error = "Your session has expired. Please login again.";
            break;
        case 'unauthorized':
            $error = "You are not authorized to access that page.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Friends Gym</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .welcome-container {
            text-align: center;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            max-width: 1200px;
            width: 95%;
            margin: 0 auto;
        }
        .welcome-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: white;
            font-weight: 700;
        }
        .login-options {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 2rem auto;
            flex-wrap: nowrap;
            max-width: 1000px;
        }
        .login-option {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 1.8rem;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 250px;
            max-width: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 200px;
            position: relative;
            overflow: hidden;
        }
        .login-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .login-option:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .login-option:hover::before {
            opacity: 1;
        }
        .login-option i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
            transition: transform 0.3s ease;
        }
        .login-option:hover i {
            transform: scale(1.1);
        }
        .login-option h3 {
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        .login-option p {
            margin-bottom: 0;
            opacity: 0.9;
            font-size: 0.95rem;
            line-height: 1.4;
            position: relative;
            z-index: 1;
        }
        .register-link {
            margin-top: 2.5rem;
            color: white;
            text-decoration: none;
            display: inline-block;
            padding: 1rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.15);
            font-size: 1.1rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        .register-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .register-link:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            transform: translateY(-2px);
        }
        .register-link:hover::before {
            opacity: 1;
        }
        @media (max-width: 1200px) {
            .welcome-container {
                padding: 2rem;
            }
        }
        @media (max-width: 992px) {
            .login-options {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            .login-option {
                width: 100%;
                max-width: 400px;
                height: auto;
                padding: 1.5rem;
            }
            .welcome-container {
                padding: 1.5rem;
            }
        }
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2rem;
            }
            .welcome-container {
                padding: 1.2rem;
                width: 98%;
            }
            .login-option {
                padding: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-container">
            <h1 class="welcome-title">Welcome to Friends Gym</h1>
            <p class="lead mb-4">Please select your login option below</p>
            
            <div class="login-options">
                <a href="admin/login.php" class="login-option">
                    <i class="fas fa-user-shield"></i>
                    <h3>Admin Login</h3>
                    <p>Access administrative features and manage the gym system</p>
                </a>
                
                <a href="staff/login.php" class="login-option">
                    <i class="fas fa-user-tie"></i>
                    <h3>Staff Login</h3>
                    <p>Manage daily operations and assist members</p>
                </a>
                
                <a href="member/login.php" class="login-option">
                    <i class="fas fa-user"></i>
                    <h3>Member Login</h3>
                    <p>Access your membership features and track your progress</p>
                </a>
            </div>
            
            <a href="member/register.php" class="register-link">
                <i class="fas fa-user-plus me-2"></i>New Member Registration
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
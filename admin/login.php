<?php
require_once '../session.php';
require_once '../dbcon.php';

// If user is already logged in as admin, redirect to admin dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: pages/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $password = trim($_POST['password'] ?? ''); // Remove any whitespace from password

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // First, check if admin table exists and has any records
            $check_table = $conn->query("SHOW TABLES LIKE 'admin'");
            if ($check_table->num_rows === 0) {
                // Create admin table if it doesn't exist
                $conn->query("CREATE TABLE IF NOT EXISTS admin (
                    user_id INT(11) NOT NULL AUTO_INCREMENT,
                    username VARCHAR(50) NOT NULL,
                    password VARCHAR(50) NOT NULL,
                    name VARCHAR(50) NOT NULL,
                    PRIMARY KEY (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
            }

            // Check if there are any admin accounts
            $check_admin = $conn->query("SELECT COUNT(*) as count FROM admin");
            $admin_count = $check_admin->fetch_assoc()['count'];

            if ($admin_count === 0) {
                // Create default admin account if none exists
                $default_username = 'admin';
                $default_password = 'admin123';
                $default_name = 'System Administrator';
                
                $stmt = $conn->prepare("INSERT INTO admin (username, password, name) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $default_username, $default_password, $default_name);
                $stmt->execute();
            }

            // Now proceed with login
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($password === $user['password']) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'admin';

                    // Redirect to admin dashboard
                    header("Location: pages/dashboard.php");
                    exit;
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Friends Gym</title>
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
            padding: 1rem;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            height: 48px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 10px;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: none;
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .btn-login {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.8rem;
            width: 100%;
            border-radius: 10px;
            transition: all 0.3s ease;
            height: 48px;
            font-weight: 500;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-login:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }
        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-top: 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: white;
            opacity: 0.8;
            transform: translateX(-5px);
        }
        .alert {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        h2 {
            font-weight: 600;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.95);
        }
        @media (max-width: 576px) {
            .login-container {
                padding: 2rem;
            }
            body {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-container">
                    <h2 class="text-center mb-4">Admin Login</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="../index.php" class="back-link">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login Options
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
<?php
require_once '../session.php';
require_once '../dbcon.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit;
}

// Fetch membership plans
$plans_query = "SELECT * FROM membership_plan ORDER BY price ASC";
$plans_result = $conn->query($plans_query);
$plans = $plans_result->fetch_all(MYSQLI_ASSOC);

// Set today's date as default
$today = date('Y-m-d');

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullname = sanitize($_POST['fullname']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Store as plain text
    $gender = sanitize($_POST['gender']);
    $reg_date = $today;
    $plan_id = (int)$_POST['plan_id'];
    $due_amount = (int)$_POST['due_amount'];
    $address = sanitize($_POST['address']);
    $contact = sanitize($_POST['contact']);
    $initial_weight = (float)$_POST['initial_weight'];
    $current_weight = (float)$_POST['current_weight'];
    $progress_date = $today;
    $status = 'Inactive';
    $attendance_count = 0;
    $reminder = 0;

    // Check if username already exists
    $check_query = "SELECT * FROM members WHERE username = '$username'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        $error = "Username already exists";
    } else {
        // Insert new member
        $query = "INSERT INTO members (
            fullname, username, password, gender, reg_date, plan_id, 
            due_amount, address, contact, status, attendance_count, 
            ini_weight, curr_weight, progress_date, reminder
        ) VALUES (
            '$fullname', '$username', '$password', '$gender', '$reg_date', 
            $plan_id, $due_amount, '$address', '$contact', '$status', 
            $attendance_count, $initial_weight, $current_weight, '$progress_date', $reminder
        )";

        if ($conn->query($query)) {
            $success = "Registration successful! Please login with your credentials.";
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Registration - Friends Gym</title>
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
        .register-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 2rem;
            max-width: 800px;
            width: 95%;
            margin: 2rem auto;
        }
        .form-label {
            color: white;
            font-weight: 500;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .back-to-login {
            color: white;
            text-decoration: none;
            margin-top: 1rem;
            display: inline-block;
        }
        .back-to-login:hover {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="text-center mb-4">
                <h2>Member Registration</h2>
                <p class="text-white-50">Join Friends Gym today!</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="fullname" class="form-label required-field">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label required-field">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label required-field">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label required-field">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="plan_id" class="form-label required-field">Membership Plan</label>
                        <select class="form-select" id="plan_id" name="plan_id" required>
                            <option value="">Select Plan</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>" data-price="<?php echo $plan['price']; ?>">
                                    <?php echo htmlspecialchars($plan['name']); ?> - ৳<?php echo $plan['price']; ?>/month
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="due_amount" class="form-label">Due Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" class="form-control" id="due_amount" name="due_amount" readonly>
                        </div>
                        <small class="text-white-50">Will be automatically set based on the selected plan</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="contact" class="form-label required-field">Contact Number</label>
                        <input type="text" class="form-control" id="contact" name="contact" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="initial_weight" class="form-label required-field">Initial Weight (kg)</label>
                        <input type="number" class="form-control" id="initial_weight" name="initial_weight" step="0.1" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 mb-3">
                        <label for="address" class="form-label required-field">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </button>
                    <a href="../index.php" class="back-to-login">
                        <i class="fas fa-arrow-left me-2"></i>Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update due amount when plan is selected
            document.getElementById('plan_id').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                document.getElementById('due_amount').value = price || 0;
            });

            // Set current weight equal to initial weight (hidden field)
            document.getElementById('initial_weight').addEventListener('input', function() {
                // Create hidden input for current weight if it doesn't exist
                let currentWeightInput = document.getElementById('current_weight');
                if (!currentWeightInput) {
                    currentWeightInput = document.createElement('input');
                    currentWeightInput.type = 'hidden';
                    currentWeightInput.id = 'current_weight';
                    currentWeightInput.name = 'current_weight';
                    document.querySelector('form').appendChild(currentWeightInput);
                }
                currentWeightInput.value = this.value;
            });
        });
    </script>
</body>
</html> 
<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch membership plans
$plans_query = "SELECT * FROM membership_plan ORDER BY price ASC";
$plans_result = $conn->query($plans_query);
$plans = $plans_result->fetch_all(MYSQLI_ASSOC);

// Set today's date as default
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Member - Friends Gym</title>
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
                    <h2>Add New Member</h2>
                    <div class="d-flex align-items-center">
                        <a href="members.php" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Members
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Add Member Form -->
                <div class="content-card">
                    <div id="alertContainer"></div>
                    
                    <form id="addMemberForm">
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
                                <label for="reg_date" class="form-label">Registration Date</label>
                                <input type="date" class="form-control" id="reg_date" name="reg_date" value="<?php echo $today; ?>" readonly>
                            </div>
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
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="due_amount" class="form-label">Due Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="due_amount" name="due_amount" readonly>
                                </div>
                                <small class="text-muted">Will be automatically set based on the selected plan</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact" class="form-label required-field">Contact Number</label>
                                <input type="text" class="form-control" id="contact" name="contact" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12 mb-3">
                                <label for="address" class="form-label required-field">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="initial_weight" class="form-label required-field">Initial Weight (kg)</label>
                                <input type="number" class="form-control" id="initial_weight" name="initial_weight" step="0.1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="progress_date" class="form-label">Progress Date</label>
                                <input type="date" class="form-control" id="progress_date" name="progress_date" value="<?php echo $today; ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="Inactive" selected>Inactive</option>
                                    <option value="Active">Active</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex mt-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-save me-2"></i>Add Member
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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

            // Add hidden fields for attendance count and reminder
            const form = document.querySelector('form');
            const hiddenFields = [
                { name: 'attendance_count', value: '0' },
                { name: 'reminder', value: '0' }
            ];

            hiddenFields.forEach(field => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = field.name;
                input.value = field.value;
                form.appendChild(input);
            });

            // Handle form submission
            document.getElementById('addMemberForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('../actions/members/add_member.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showAlert('success', data.message);
                        setTimeout(() => {
                            window.location.href = 'members.php';
                        }, 1500);
                    } else {
                        showAlert('danger', data.message);
                    }
                })
                .catch(error => {
                    showAlert('danger', 'An error occurred while processing your request.');
                    console.error('Error:', error);
                });
            });

            // Show alert message
            function showAlert(type, message) {
                const alertContainer = document.getElementById('alertContainer');
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show`;
                alert.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                alertContainer.innerHTML = '';
                alertContainer.appendChild(alert);
                
                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            }
        });
    </script>
</body>
</html> 
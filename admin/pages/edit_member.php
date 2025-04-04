<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get member ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch member details
$query = "SELECT m.*, mp.name as plan_name, mp.price as plan_price 
          FROM members m 
          LEFT JOIN membership_plan mp ON m.plan_id = mp.id 
          WHERE m.user_id = $id";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    $_SESSION['error_message'] = "Member not found.";
    header('Location: members.php');
    exit();
}

$member = $result->fetch_assoc();

// Fetch all membership plans
$plans_query = "SELECT * FROM membership_plan ORDER BY price ASC";
$plans_result = $conn->query($plans_query);
$plans = $plans_result->fetch_all(MYSQLI_ASSOC);

// Check for success or error messages in session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear the session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member - Friends Gym</title>
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
                    <h2>Edit Member: <?php echo htmlspecialchars($member['fullname']); ?></h2>
                    <div class="d-flex align-items-center">
                        <a href="members.php" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Members
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Edit Member Form -->
                <div class="content-card">
                    <div id="alertContainer"></div>
                    
                    <form id="editMemberForm">
                        <input type="hidden" name="id" value="<?php echo $member['user_id']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="fullname" class="form-label required-field">Full Name</label>
                                    <input type="text" class="form-control" id="fullname" name="fullname" 
                                       value="<?php echo htmlspecialchars($member['fullname']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label required-field">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($member['username']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password (Leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label required-field">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo $member['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $member['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="reg_date" class="form-label required-field">Registration Date</label>
                                <input type="date" class="form-control" id="reg_date" name="reg_date" 
                                       value="<?php echo $member['reg_date']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="plan_id" class="form-label required-field">Membership Plan</label>
                                <select class="form-select" id="plan_id" name="plan_id" required>
                                    <option value="">Select Plan</option>
                                    <?php foreach ($plans as $plan): ?>
                                        <option value="<?php echo $plan['id']; ?>" 
                                                data-price="<?php echo $plan['price']; ?>"
                                                <?php echo $member['plan_id'] == $plan['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($plan['name']); ?> - ৳<?php echo $plan['price']; ?>/month
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="due_amount" class="form-label required-field">Due Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" class="form-control" id="due_amount" name="due_amount" 
                                           value="<?php echo $member['due_amount']; ?>" required>
                                </div>
                                <small class="text-muted">Will update automatically when plan is changed</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label required-field">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Active" <?php echo $member['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo $member['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12 mb-3">
                                <label for="address" class="form-label required-field">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required><?php echo htmlspecialchars($member['address']); ?></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="contact" class="form-label required-field">Contact Number</label>
                                    <input type="text" class="form-control" id="contact" name="contact" 
                                       value="<?php echo htmlspecialchars($member['contact']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="initial_weight" class="form-label required-field">Initial Weight (kg)</label>
                                    <input type="number" class="form-control" id="initial_weight" name="initial_weight" 
                                       value="<?php echo $member['ini_weight']; ?>" step="0.1" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="curr_weight" class="form-label required-field">Current Weight (kg)</label>
                                <input type="number" class="form-control" id="curr_weight" name="curr_weight" 
                                       value="<?php echo $member['curr_weight']; ?>" step="0.1" required>
                                </div>
                            <div class="col-md-6 mb-3">
                                <label for="progress_date" class="form-label required-field">Progress Date</label>
                                <input type="date" class="form-control" id="progress_date" name="progress_date" 
                                       value="<?php echo $member['progress_date']; ?>" required>
                            </div>
                        </div>

                        <div class="d-flex mt-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-save me-2"></i>Update Member
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

    // Handle form submission
            document.getElementById('editMemberForm').addEventListener('submit', function(e) {
        e.preventDefault();

                const formData = new FormData(this);
                
                fetch('../actions/members/edit_member.php', {
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
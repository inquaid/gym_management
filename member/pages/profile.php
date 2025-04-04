<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is member
if (!isLoggedIn() || $_SESSION['role'] !== 'member') {
    header("Location: ../login.php");
    exit;
}

// Get member information
$member_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Sanitize inputs using htmlspecialchars instead of deprecated FILTER_SANITIZE_STRING
    $fullname = htmlspecialchars($_POST['fullname'], ENT_QUOTES, 'UTF-8');
    $gender = htmlspecialchars($_POST['gender'], ENT_QUOTES, 'UTF-8');
    $contact = htmlspecialchars($_POST['contact'], ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8');

    $query = "UPDATE members SET fullname = ?, gender = ?, contact = ?, address = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $fullname, $gender, $contact, $address, $member_id);
    
    if ($stmt->execute()) {
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}

// Get member information with plan details
$stmt = $conn->prepare("SELECT m.*, mp.name as plan_name, mp.price as monthly_fee 
                       FROM members m 
                       JOIN membership_plan mp ON m.plan_id = mp.id 
                       WHERE m.user_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Friends Gym</title>
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
        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1rem;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #2c3e50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-right: 1.5rem;
        }
        .profile-info h3 {
            margin-bottom: 0.5rem;
        }
        .profile-info .text-muted {
            font-size: 0.9rem;
        }
        .nav-tabs {
            margin-bottom: 1.5rem;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            color: #2c3e50;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4>Friends Gym</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-calendar-check me-2"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="progress.php">
                                <i class="fas fa-chart-line me-2"></i>Progress
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="shop.php">
                                <i class="fas fa-shopping-cart me-2"></i>Shop
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reminders.php">
                                <i class="fas fa-bell me-2"></i>Reminders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payments.php">
                                <i class="fas fa-credit-card me-2"></i>Payments
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="../../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Profile</h2>
                    <div class="d-flex align-items-center">
                        <a href="profile.php" class="btn btn-primary btn-sm rounded-pill me-3">
                            <i class="fas fa-user me-1"></i> My Profile
                        </a>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Profile Section -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-info">
                            <h3><?php echo htmlspecialchars($member['fullname']); ?></h3>
                            <div class="text-muted">
                                <i class="fas fa-envelope me-2"></i><?php echo isset($member['email']) ? htmlspecialchars($member['email']) : 'Not Available'; ?>
                            </div>
                            <div class="text-muted">
                                <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($member['contact']); ?>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-<?php echo $member['status'] === 'Active' ? 'success' : 'danger'; ?>">
                                    <?php echo $member['status']; ?> Member
                                </span>
                                <span class="badge bg-info ms-2"><?php echo $member['plan_name']; ?> Plan</span>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details" role="tab">
                                Personal Details
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="password-tab" data-bs-toggle="tab" href="#password" role="tab">
                                Change Password
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="profileTabsContent">
                        <!-- Personal Details Tab -->
                        <div class="tab-pane fade show active" id="details" role="tabpanel">
                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>

                            <form method="POST" action="/gym_mngmnt/member/actions/profile/update_profile.php">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fullname" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($member['fullname']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($member['username']); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="Male" <?php echo $member['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $member['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo $member['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contact" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($member['contact']); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2" required><?php echo htmlspecialchars($member['address']); ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Membership Plan</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($member['plan_name']); ?> - ৳<?php echo number_format($member['monthly_fee']); ?>" readonly>
                                            <span class="input-group-text">
                                                <i class="fas fa-info-circle" title="Membership plan can only be changed by the administrator"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="ini_weight" class="form-label">Initial Weight (kg)</label>
                                        <input type="number" class="form-control" id="ini_weight" name="ini_weight" value="<?php echo htmlspecialchars($member['ini_weight']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="curr_weight" class="form-label">Current Weight (kg)</label>
                                        <input type="number" class="form-control" id="curr_weight" name="curr_weight" value="<?php echo htmlspecialchars($member['curr_weight']); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="progress_date" class="form-label">Progress Date</label>
                                        <input type="date" class="form-control" id="progress_date" name="progress_date" value="<?php echo htmlspecialchars($member['progress_date']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Account Status</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($member['status']); ?>" readonly>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <?php if (isset($_SESSION['password_success'])): ?>
                                <div class="alert alert-success">Password updated successfully!</div>
                                <?php unset($_SESSION['password_success']); ?>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['password_error'])): ?>
                                <div class="alert alert-danger"><?php echo $_SESSION['password_error']; ?></div>
                                <?php unset($_SESSION['password_error']); ?>
                            <?php endif; ?>

                            <form method="POST" action="/gym_mngmnt/member/actions/profile/update_password.php">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="current_password">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="new_password">New Password</label>
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="confirm_password">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </form>
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
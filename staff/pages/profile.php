<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is staff
if (!isLoggedIn() || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

// Get staff information
$staff_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM staffs WHERE user_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

// Process update messages
$update_success = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : false;
$update_error = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : '';

// Clear session messages
unset($_SESSION['update_success']);
unset($_SESSION['update_error']);

// Process password change messages
$password_success = isset($_SESSION['password_success']) ? $_SESSION['password_success'] : false;
$password_error = isset($_SESSION['password_error']) ? $_SESSION['password_error'] : '';

// Clear session messages
unset($_SESSION['password_success']);
unset($_SESSION['password_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile - Friends Gym</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
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
            margin-bottom: 1.5rem;
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
            background: #343a40;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-right: 1.5rem;
        }
        .nav-tabs {
            margin-bottom: 1.5rem;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            color: #343a40;
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
                    <small class="text-muted">Staff Panel</small>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="members.php">
                                <i class="fas fa-users me-2"></i>Members
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-calendar-check me-2"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="equipment.php">
                                <i class="fas fa-dumbbell me-2"></i>Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
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
                        <div>
                            <h3><?php echo htmlspecialchars($staff['fullname']); ?></h3>
                            <div class="text-muted mb-2">
                                <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($staff['email']); ?>
                            </div>
                            <div class="text-muted">
                                <i class="fas fa-id-badge me-2"></i><?php echo htmlspecialchars($staff['designation']); ?>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details" role="tab">
                                Personal Details
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="password-tab" data-bs-toggle="tab" href="#password" role="tab">
                                Change Password
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Personal Details Tab -->
                        <div class="tab-pane fade show active" id="details" role="tabpanel">
                            <?php if ($update_success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>Profile updated successfully!
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($update_error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $update_error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="../actions/profile/update_profile.php" method="POST">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="fullname" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($staff['fullname']); ?>" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($staff['username']); ?>" readonly>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="contact" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($staff['contact']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="designation" class="form-label">Designation</label>
                                        <input type="text" class="form-control" id="designation" name="designation" value="<?php echo htmlspecialchars($staff['designation']); ?>" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo $staff['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $staff['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo $staff['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" required><?php echo htmlspecialchars($staff['address']); ?></textarea>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <?php if ($password_success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>Password changed successfully!
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($password_error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $password_error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="../actions/profile/change_password.php" method="POST">
                                <div class="form-group">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
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
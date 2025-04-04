<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid reminder ID';
    header("Location: reminders.php");
    exit;
}

$reminder_id = intval($_GET['id']);

// Get reminder details
$query = "
    SELECT r.*, m.fullname
    FROM reminder r
    LEFT JOIN members m ON r.user_id = m.user_id
    WHERE r.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $reminder_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Reminder not found';
    header("Location: reminders.php");
    exit;
}

$reminder = $result->fetch_assoc();

// Get all members for dropdown
$members_query = "SELECT user_id, fullname FROM members ORDER BY fullname";
$members_result = $conn->query($members_query);

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
    <title>Edit Reminder - Friends Gym</title>
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
                    <h2>Edit Reminder</h2>
                    <div class="text-muted">
                        <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
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

                <!-- Edit Reminder Form -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Edit Reminder</h5>
                        <a href="reminders.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back to Reminders
                        </a>
                    </div>
                    <form action="../actions/reminders/update_reminder.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $reminder['id']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="member" class="form-label required-field">Member</label>
                                <select class="form-select" id="member" name="user_id" required>
                                    <option value="">Select Member</option>
                                    <?php while ($member = $members_result->fetch_assoc()): ?>
                                        <option value="<?php echo $member['user_id']; ?>" <?php echo ($member['user_id'] == $reminder['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($member['fullname']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="reminderDate" class="form-label required-field">Reminder Date</label>
                                <input type="datetime-local" class="form-control" id="reminderDate" name="date" value="<?php echo date('Y-m-d\TH:i', strtotime($reminder['date'])); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label required-field">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required><?php echo htmlspecialchars($reminder['message']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label required-field">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Pending" <?php echo ($reminder['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Completed" <?php echo ($reminder['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        
                        <div class="text-end">
                            <a href="reminders.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Reminder</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
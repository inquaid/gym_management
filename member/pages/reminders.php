<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in as member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../login.php");
    exit();
}

// Get member's reminders
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM reminder WHERE user_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reminders - Gym Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reminder-card {
            transition: all 0.3s ease;
        }
        .reminder-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .completed {
            opacity: 0.7;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include('../includes/sidebar.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Reminders</h1>
                </div>

                <div class="row">
                    <?php while ($reminder = $result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card reminder-card <?php echo $reminder['status'] === 'Completed' ? 'completed' : ''; ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($reminder['name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($reminder['message']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($reminder['date'])); ?>
                                        </small>
                                        <?php if ($reminder['status'] === 'Pending'): ?>
                                            <button class="btn btn-sm btn-success mark-complete" 
                                                    data-reminder-id="<?php echo $reminder['id']; ?>">
                                                <i class="fas fa-check me-1"></i>Mark as Complete
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Completed
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle marking reminders as complete
            $('.mark-complete').on('click', function() {
                const button = $(this);
                const reminderId = button.data('reminder-id');
                const card = button.closest('.card');

                // Show loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');

                // Send AJAX request
                $.ajax({
                    url: '../actions/reminders/mark_complete.php',
                    type: 'POST',
                    data: { reminder_id: reminderId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update UI
                            card.addClass('completed');
                            button.replaceWith(`
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Completed
                                </span>
                            `);
                        } else {
                            alert(response.message);
                            button.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Mark as Complete');
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                        button.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Mark as Complete');
                    }
                });
            });
        });
    </script>
</body>
</html> 
<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM members";
$count_result = $conn->query($count_query);
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Get members data with plan information
$query = "SELECT m.*, mp.name as plan_name, mp.price as plan_price 
          FROM members m 
          LEFT JOIN membership_plan mp ON m.plan_id = mp.id 
          ORDER BY m.user_id DESC 
          LIMIT $offset, $limit";
$result = $conn->query($query);

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
    <title>Members Management - Friends Gym</title>
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
        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .dot-active {
            background-color: #28a745;
        }
        .dot-inactive {
            background-color: #dc3545;
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
                    <h2>Members Management</h2>
                    <div class="d-flex align-items-center">
                        <a href="add_member.php" class="btn btn-primary me-3">
                            <i class="fas fa-user-plus me-2"></i>Add New Member
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

                <!-- Members List -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Members List</h5>
                        <div>
                            <span class="badge bg-secondary">Total: <?php echo $total; ?></span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Gender</th>
                                    <th>Plan</th>
                                    <th>Due Amount</th>
                                    <th>Join Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($member = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $member['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($member['username']); ?></td>
                                            <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($member['plan_name']); ?>
                                                <small class="text-muted d-block">(৳<?php echo $member['plan_price']; ?>/month)</small>
                                            </td>
                                            <td>
                                                ৳<?php echo number_format($member['due_amount'], 2); ?>
                                                <?php if ($member['due_amount'] > 0): ?>
                                                    <span class="badge bg-danger ms-1">Overdue</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($member['reg_date'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $member['status'] == 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo htmlspecialchars($member['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit_member.php?id=<?php echo $member['user_id']; ?>" 
                                                       class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" 
                                                       data-bs-title="Edit Member">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger delete-member" 
                                                            data-user-id="<?php echo $member['user_id']; ?>" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteMemberModal"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-title="Delete Member">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No members found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-labelledby="deleteMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteMemberModalLabel">Delete Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this member? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set up delete confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-member');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            let currentMemberId = null;

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    currentMemberId = this.getAttribute('data-user-id');
                });
            });

            confirmDeleteBtn.addEventListener('click', function() {
                if (currentMemberId) {
                    // Show loading state
                    confirmDeleteBtn.disabled = true;
                    confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';

                    // Send AJAX request
                    fetch('../actions/members/delete_member.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + currentMemberId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Show success message
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-success alert-dismissible fade show';
                            alertDiv.innerHTML = `
                                <i class="fas fa-check-circle me-2"></i> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            document.querySelector('.main-content').insertBefore(alertDiv, document.querySelector('.content-card'));

                            // Remove the deleted row
                            const row = document.querySelector(`[data-user-id="${currentMemberId}"]`).closest('tr');
                            row.remove();

                            // Update total count
                            const totalBadge = document.querySelector('.badge.bg-secondary');
                            const currentTotal = parseInt(totalBadge.textContent.split(': ')[1]);
                            totalBadge.textContent = `Total: ${currentTotal - 1}`;
                        } else {
                            // Show error message
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                            alertDiv.innerHTML = `
                                <i class="fas fa-exclamation-circle me-2"></i> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            document.querySelector('.main-content').insertBefore(alertDiv, document.querySelector('.content-card'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Show error message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            <i class="fas fa-exclamation-circle me-2"></i> An error occurred while deleting the member.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.querySelector('.main-content').insertBefore(alertDiv, document.querySelector('.content-card'));
                    })
                    .finally(() => {
                        // Reset button state and close modal
                        confirmDeleteBtn.disabled = false;
                        confirmDeleteBtn.innerHTML = 'Delete';
                        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteMemberModal'));
                        modal.hide();
                    });
                }
            });
        });
    </script>
</body>
</html> 
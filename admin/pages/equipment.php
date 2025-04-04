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
$count_query = "SELECT COUNT(*) as total FROM equipment";
$count_result = $conn->query($count_query);
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Get equipment data
$query = "SELECT * FROM equipment ORDER BY id DESC LIMIT $offset, $limit";
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
    <title>Equipment Management - Friends Gym</title>
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
                    <h2>Equipment Management</h2>
                    <div class="d-flex align-items-center">
                        <a href="add_equipment.php" class="btn btn-primary me-3">
                            <i class="fas fa-plus me-2"></i>Add New Equipment
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

                <!-- Equipment List -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Equipment List</h5>
                        <div>
                            <span class="badge bg-secondary">Total: <?php echo $total; ?></span>
                            </div>
                        </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Vendor</th>
                                    <th>Purchase Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($equipment = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $equipment['id']; ?></td>
                                            <td><?php echo htmlspecialchars($equipment['name']); ?></td>
                                            <td><?php echo $equipment['quantity']; ?></td>
                                            <td><?php echo number_format($equipment['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($equipment['vendor']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($equipment['purchase_date'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit_equipment.php?id=<?php echo $equipment['id']; ?>" 
                                                       class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" 
                                                       data-bs-title="Edit Equipment">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                    <button class="btn btn-sm btn-danger delete-equipment" 
                                                            data-equip-id="<?php echo $equipment['id']; ?>" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteEquipmentModal"
                                                            data-bs-title="Delete Equipment">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No equipment found</td>
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
    <div class="modal fade" id="deleteEquipmentModal" tabindex="-1" aria-labelledby="deleteEquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="deleteEquipmentModalLabel">Delete Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    Are you sure you want to delete this equipment? This action cannot be undone.
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
        document.addEventListener('DOMContentLoaded', function() {
// Set up delete confirmation
            const deleteButtons = document.querySelectorAll('.delete-equipment');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            let equipmentId = null;
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    equipmentId = this.getAttribute('data-equip-id');
                });
            });
            
            confirmDeleteBtn.addEventListener('click', function() {
                if (equipmentId) {
                    // Create form data
                    const formData = new FormData();
                    formData.append('id', equipmentId);
                    
                    // Send delete request
                    fetch('../actions/equipment/delete_equipment.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Hide modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteEquipmentModal'));
                            modal.hide();
                            
                            // Reload page to show changes
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the equipment.');
                    });
                }
            });
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
</body>
</html> 
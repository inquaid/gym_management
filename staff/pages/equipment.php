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

// Initialize pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build SQL query for equipment listing
$sql = "SELECT * FROM equipment ORDER BY name LIMIT ?, ?";
$params = [$offset, $records_per_page];
$types = "ii";

// Prepare and execute count query
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM equipment");
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Helper function to make array values referenced
function makeValuesReferenced($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Prepare and execute main query
$stmt = $conn->prepare($sql);
$bind_params = array_merge([$types], $params);
call_user_func_array([$stmt, 'bind_param'], makeValuesReferenced($bind_params));
$stmt->execute();
$result = $stmt->get_result();

// Get equipment categories for filter dropdown (using a default category since the column doesn't exist)
$category_query = "SELECT 'General' AS category";
$category_result = $conn->query($category_query);

// Process success or error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages
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
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1.5rem;
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
        }
        .equipment-card {
            transition: all 0.3s;
        }
        .equipment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 20px rgba(0,0,0,.1);
        }
        .equipment-img {
            height: 180px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .equipment-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
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
                            <a class="nav-link active" href="equipment.php">
                                <i class="fas fa-dumbbell me-2"></i>Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
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
                    <h2>Equipment Management</h2>
                    <div class="d-flex align-items-center">
                        <div class="text-muted me-3">
                            <i class="fas fa-clock me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Equipment Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Equipment List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Quantity</th>
                                        <th>Amount</th>
                                        <th>Vendor</th>
                                        <th>Purchase Date</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php 
                                        $counter = 1;
                                        while ($item = $result->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                <td>৳<?php echo htmlspecialchars($item['amount']); ?></td>
                                                <td><?php echo htmlspecialchars($item['vendor']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($item['purchase_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($item['description']); ?></td>
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
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Equipment Modal -->
    <div class="modal fade" id="addEquipmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="../actions/equipment/add_equipment.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Equipment Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="vendor" class="form-label">Vendor</label>
                            <input type="text" class="form-control" id="vendor" name="vendor" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact" class="form-label">Contact</label>
                            <input type="text" class="form-control" id="contact" name="contact" required>
                        </div>
                        <div class="mb-3">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Equipment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Equipment Modal -->
    <div class="modal fade" id="viewEquipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Equipment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="equipmentDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Equipment Modal -->
    <div class="modal fade" id="editEquipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="../actions/equipment/update_equipment.php" method="POST" enctype="multipart/form-data" id="editEquipmentForm">
                    <div class="modal-body" id="editEquipmentBody">
                        <!-- Content will be loaded dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View Equipment Details
            document.querySelectorAll('.view-equipment').forEach(button => {
                button.addEventListener('click', function() {
                    const equipmentId = this.getAttribute('data-id');
                    fetch(`../actions/equipment/get_equipment.php?id=${equipmentId}`)
                        .then(response => response.json())
                        .then(data => {
                            let statusBadgeClass = '';
                            if (data.status === 'Operational') statusBadgeClass = 'success';
                            else if (data.status === 'Under Maintenance') statusBadgeClass = 'warning';
                            else statusBadgeClass = 'danger';
                            
                            let imageHtml = '';
                            if (data.image) {
                                imageHtml = `<div class="text-center mb-4">
                                    <img src="${data.image}" alt="${data.name}" class="img-fluid rounded" style="max-height: 300px;">
                                </div>`;
                            }
                            
                            const html = `
                                ${imageHtml}
                                <div class="d-flex justify-content-between mb-3">
                                    <h3>${data.name}</h3>
                                    <span class="badge bg-${statusBadgeClass} fs-6">${data.status}</span>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p><strong>Category:</strong> ${data.category}</p>
                                        <p><strong>Purchase Date:</strong> ${data.purchase_date ? new Date(data.purchase_date).toLocaleDateString() : 'Not specified'}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Cost:</strong> ${data.cost ? '$' + parseFloat(data.cost).toFixed(2) : 'Not specified'}</p>
                                        <p><strong>Last Maintained:</strong> ${data.last_maintained ? new Date(data.last_maintained).toLocaleDateString() : 'Not recorded'}</p>
                                    </div>
                                </div>
                                
                                <h5>Description</h5>
                                <div class="card mb-4">
                                    <div class="card-body">
                                        ${data.description ? data.description.replace(/\n/g, '<br>') : 'No description available.'}
                                    </div>
                                </div>
                                
                                <h5>Maintenance Notes</h5>
                                <div class="card">
                                    <div class="card-body">
                                        ${data.maintenance_notes ? data.maintenance_notes.replace(/\n/g, '<br>') : 'No maintenance notes available.'}
                                    </div>
                                </div>
                            `;
                            
                            document.getElementById('equipmentDetails').innerHTML = html;
                            const modal = new bootstrap.Modal(document.getElementById('viewEquipmentModal'));
                            modal.show();
                        })
                        .catch(error => console.error('Error fetching equipment details:', error));
                });
            });
            
            // Edit Equipment
            document.querySelectorAll('.edit-equipment').forEach(button => {
                button.addEventListener('click', function() {
                    const equipmentId = this.getAttribute('data-id');
                    fetch(`../actions/equipment/get_equipment.php?id=${equipmentId}`)
                        .then(response => response.json())
                        .then(data => {
                            const html = `
                                <input type="hidden" name="id" value="${data.id}">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_name" class="form-label">Equipment Name</label>
                                        <input type="text" class="form-control" id="edit_name" name="name" value="${data.name}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_category" class="form-label">Category</label>
                                        <input type="text" class="form-control" id="edit_category" name="category" value="${data.category}" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_purchase_date" class="form-label">Purchase Date</label>
                                        <input type="date" class="form-control" id="edit_purchase_date" name="purchase_date" value="${data.purchase_date ? data.purchase_date : ''}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_cost" class="form-label">Cost</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="edit_cost" name="cost" step="0.01" value="${data.cost ? data.cost : ''}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_image" class="form-label">Equipment Image</label>
                                    ${data.image ? `<div class="mb-2"><img src="${data.image}" class="img-thumbnail" style="height: 100px;"></div>` : ''}
                                    <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                    <div class="form-text">Leave empty to keep current image.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-select" id="edit_status" name="status" required>
                                        <option value="Operational" ${data.status === 'Operational' ? 'selected' : ''}>Operational</option>
                                        <option value="Under Maintenance" ${data.status === 'Under Maintenance' ? 'selected' : ''}>Under Maintenance</option>
                                        <option value="Out of Order" ${data.status === 'Out of Order' ? 'selected' : ''}>Out of Order</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="4">${data.description ? data.description : ''}</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_maintenance_notes" class="form-label">Maintenance Notes</label>
                                    <textarea class="form-control" id="edit_maintenance_notes" name="maintenance_notes" rows="3">${data.maintenance_notes ? data.maintenance_notes : ''}</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_last_maintained" class="form-label">Last Maintained Date</label>
                                    <input type="date" class="form-control" id="edit_last_maintained" name="last_maintained" value="${data.last_maintained ? data.last_maintained : ''}">
                                </div>
                            `;
                            
                            document.getElementById('editEquipmentBody').innerHTML = html;
                            const modal = new bootstrap.Modal(document.getElementById('editEquipmentModal'));
                            modal.show();
                        })
                        .catch(error => console.error('Error fetching equipment details for edit:', error));
                });
            });
        });
    </script>
</body>
</html> 
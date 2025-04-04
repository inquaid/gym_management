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

// Helper function to make array values referenced
function makeValuesReferenced($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Build SQL query for announcements listing
$sql = "SELECT * FROM announcements ORDER BY date DESC LIMIT ?, ?";
$params = [$offset, $records_per_page];
$types = "ii";

// Prepare and execute count query
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM announcements");
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Prepare and execute main query
$stmt = $conn->prepare($sql);
$bind_params = array_merge([$types], $params);
call_user_func_array([$stmt, 'bind_param'], makeValuesReferenced($bind_params));
$stmt->execute();
$result = $stmt->get_result();

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
    <title>Announcements - Friends Gym</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Summernote CSS for rich text editor -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
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
        .announcement-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 20px rgba(0,0,0,.1);
        }
        .badge-status {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
        }
        .modal-lg {
            max-width: 800px;
        }
        .announcement-content {
            max-height: 300px;
            overflow-y: auto;
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
                            <a class="nav-link active" href="announcements.php">
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
                    <h2>Announcements Management</h2>
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
                
                <!-- Announcements Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Announcements List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php 
                                        $counter = 1;
                                        while ($announcement = $result->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><?php echo htmlspecialchars($announcement['message']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($announcement['date'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No announcements found</td>
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

    <!-- Add Announcement Modal -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="../actions/announcements/add_announcement.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Announcement Modal -->
    <div class="modal fade" id="viewAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAnnouncementTitle">Announcement Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="viewAnnouncementContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editAnnouncementBtn">Edit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Announcement Modal -->
    <div class="modal fade" id="editAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="../actions/announcements/update_announcement.php" method="POST" id="editAnnouncementForm">
                    <div class="modal-body" id="editAnnouncementBody">
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

    <!-- Delete Announcement Confirmation Modal -->
    <div class="modal fade" id="deleteAnnouncementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this announcement? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="../actions/announcements/delete_announcement.php" method="POST">
                        <input type="hidden" name="announcement_id" id="deleteAnnouncementId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Summernote JS for rich text editor -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize rich text editor
            $('.summernote').summernote({
                height: 200,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']]
                ]
            });
        });
        
        // View Announcement
        function viewAnnouncement(id) {
            fetch(`../actions/announcements/get_announcement.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('viewAnnouncementTitle').textContent = data.title;
                    
                    let statusBadge = data.status === 'Active' ? 
                        '<span class="badge bg-success badge-status">Active</span>' : 
                        '<span class="badge bg-secondary badge-status">Inactive</span>';
                    
                    let staffInfo = data.staff_name ? 
                        `<p><strong>Posted by:</strong> ${data.staff_name}</p>` : '';
                    
                    let html = `
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="text-muted">
                                    <i class="fas fa-calendar me-1"></i> Posted: ${new Date(data.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                                </p>
                                ${staffInfo}
                            </div>
                            <div>
                                ${statusBadge}
                            </div>
                        </div>
                        <div class="announcement-content mb-3">
                            ${data.content}
                        </div>
                    `;
                    
                    document.getElementById('viewAnnouncementContent').innerHTML = html;
                    document.getElementById('editAnnouncementBtn').onclick = function() {
                        $('#viewAnnouncementModal').modal('hide');
                        editAnnouncement(id);
                    };
                    
                    const modal = new bootstrap.Modal(document.getElementById('viewAnnouncementModal'));
                    modal.show();
                })
                .catch(error => console.error('Error fetching announcement details:', error));
        }
        
        // Edit Announcement
        function editAnnouncement(id) {
            fetch(`../actions/announcements/get_announcement.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    const html = `
                        <input type="hidden" name="announcement_id" value="${data.id}">
                        
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" value="${data.title}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_content" class="form-label">Content</label>
                            <textarea class="form-control edit-summernote" id="edit_content" name="content" rows="6">${data.content}</textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Active" ${data.status === 'Active' ? 'selected' : ''}>Active</option>
                                <option value="Inactive" ${data.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-danger me-2" onclick="confirmDelete(${data.id})">
                                <i class="fas fa-trash-alt me-1"></i> Delete Announcement
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('editAnnouncementBody').innerHTML = html;
                    
                    // Initialize rich text editor for edit form
                    $('.edit-summernote').summernote({
                        height: 200,
                        toolbar: [
                            ['style', ['bold', 'italic', 'underline', 'clear']],
                            ['font', ['strikethrough']],
                            ['para', ['ul', 'ol', 'paragraph']],
                            ['height', ['height']]
                        ]
                    });
                    
                    const modal = new bootstrap.Modal(document.getElementById('editAnnouncementModal'));
                    modal.show();
                })
                .catch(error => console.error('Error fetching announcement details for edit:', error));
        }
        
        // Confirm Delete
        function confirmDelete(id) {
            document.getElementById('deleteAnnouncementId').value = id;
            $('#editAnnouncementModal').modal('hide');
            const modal = new bootstrap.Modal(document.getElementById('deleteAnnouncementModal'));
            modal.show();
        }
    </script>
</body>
</html> 
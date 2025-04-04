<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// Get date from request, default to current date
$current_date = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;

// Limit date selection to current date or earlier
if ($selected_date > $current_date) {
    $selected_date = $current_date;
}

// Get all members with their attendance status for the selected date
$stmt = $conn->prepare("
    SELECT m.user_id, m.fullname, m.username, m.contact, 
           a.id as attendance_id, a.check_in_time, a.check_out_time, a.present
    FROM members m
    LEFT JOIN attendance a ON m.user_id = a.user_id AND DATE(a.check_in_time) = ?
    WHERE m.status = 'Active'
    ORDER BY m.fullname ASC
");
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$members = $stmt->get_result();
?>

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Member</th>
                <th>Username</th>
                <th>Contact</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Status</th>
                <?php if ($selected_date == $current_date): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($members->num_rows > 0): ?>
                <?php while ($member = $members->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($member['username']); ?></td>
                        <td><?php echo htmlspecialchars($member['contact']); ?></td>
                        <td>
                            <?php if (!empty($member['check_in_time'])): ?>
                                <?php echo date('h:i A', strtotime($member['check_in_time'])); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($member['check_out_time'])): ?>
                                <?php echo date('h:i A', strtotime($member['check_out_time'])); ?>
                            <?php elseif (!empty($member['check_in_time'])): ?>
                                <span class="text-warning">Missing</span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($member['check_in_time'])): ?>
                                <span class="badge badge-present">Present</span>
                            <?php else: ?>
                                <span class="badge badge-absent">Absent</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($selected_date == $current_date): ?>
                            <td>
                                <?php if (empty($member['check_in_time'])): ?>
                                    <button class="btn btn-sm btn-success check-in-btn" 
                                            data-user-id="<?php echo $member['user_id']; ?>">
                                        <i class="fas fa-sign-in-alt me-1"></i> Check In
                                    </button>
                                <?php elseif (empty($member['check_out_time'])): ?>
                                    <button class="btn btn-sm btn-warning check-out-btn" 
                                            data-user-id="<?php echo $member['user_id']; ?>"
                                            data-attendance-id="<?php echo $member['attendance_id']; ?>">
                                        <i class="fas fa-sign-out-alt me-1"></i> Check Out
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        <i class="fas fa-check me-1"></i> Completed
                                    </button>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo ($selected_date == $current_date) ? '7' : '6'; ?>" class="text-center">
                        No members found
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div> 
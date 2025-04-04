<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="col-md-3 col-lg-2 px-0 sidebar">
    <div class="p-3">
        <h4>Gym Management</h4>
        <hr>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>" href="attendance.php">
                    <i class="fas fa-calendar-check me-2"></i>Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'progress.php' ? 'active' : ''; ?>" href="progress.php">
                    <i class="fas fa-chart-line me-2"></i>Progress
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'shop.php' ? 'active' : ''; ?>" href="shop.php">
                    <i class="fas fa-shopping-cart me-2"></i>Shop
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                    <i class="fas fa-bullhorn me-2"></i>Announcements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'reminders.php' ? 'active' : ''; ?>" href="reminders.php">
                    <i class="fas fa-bell me-2"></i>Reminders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>" href="payments.php">
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

<style>
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
</style> 
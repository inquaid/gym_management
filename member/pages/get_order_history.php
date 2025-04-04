<?php
require_once '../../session.php';
require_once '../../dbcon.php';

// Check if user is logged in and is member
if (!isLoggedIn() || $_SESSION['role'] !== 'member') {
    http_response_code(403);
    exit('Unauthorized');
}

// Get member information
$member_id = $_SESSION['user_id'];

// Get recent orders
$stmt = $conn->prepare("
    SELECT o.*, 
           p.name as product_name,
           oi.quantity,
           oi.price as item_price
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$recent_orders = $stmt->get_result();
?>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Total Amount</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($recent_orders->num_rows > 0): ?>
                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $order['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>৳<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            switch ($order['status']) {
                                case 'Pending':
                                    $status_class = 'bg-warning';
                                    break;
                                case 'Completed':
                                    $status_class = 'bg-success';
                                    break;
                                case 'Cancelled':
                                    $status_class = 'bg-danger';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?> status-badge">
                                <?php echo $order['status']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No orders found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div> 
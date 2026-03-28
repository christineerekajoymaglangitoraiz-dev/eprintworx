<?php
$active = 'dashboard';
$title = 'Dashboard';
require_once '../includes/header.php';

$role = $_SESSION['role'];
$staff_id = $_SESSION['staff_id'];

if ($role === 'staff') {
$my_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE staff_id = $staff_id")->fetch_row()[0];
$my_pending = $conn->query("SELECT COUNT(*) FROM orders WHERE staff_id = $staff_id AND status = 'Pending'")->fetch_row()[0];
$my_today_tx = $conn->query("SELECT COUNT(*) FROM transaction_record tr JOIN payment p ON p.payment_id = tr.payment_id JOIN orders o ON o.order_id = p.order_id WHERE o.staff_id = $staff_id AND tr.transaction_date = CURDATE()")->fetch_row()[0];
$my_today_total = $conn->query("SELECT COALESCE(SUM(tr.total_amount), 0) FROM transaction_record tr JOIN payment p ON p.payment_id = tr.payment_id JOIN orders o ON o.order_id = p.order_id WHERE o.staff_id = $staff_id AND tr.transaction_date = CURDATE()")->fetch_row()[0];
$recent_orders = $conn->query("SELECT o.*, c.customer_name FROM orders o JOIN customer c ON c.customer_id = o.customer_id WHERE o.staff_id = $staff_id ORDER BY o.created_at DESC LIMIT 6");
?>

    <div class="page-header">

        <div>
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['staff_name']); ?>!</h1>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-value"><?php echo $my_orders; ?></div>
                <div class="stat-label">Orders</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?php echo $my_pending; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?php echo $my_today_tx; ?></div>
                <div class="stat-label">Today's Transaction</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value">₱<?php echo number_format($my_today_total, 2); ?></div>
                <div class="stat-label">Today's Total</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Orders</h3>
            <a href="orders.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th> <th>Customer</th> <th>Date</th> <th>Amount</th> <th>Status</th> <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $status_badges = ['Pending','Processing', 'Completed','Cancelled'];
                    $has_orders = false;
                    while ($row = $recent_orders->fetch_assoc()): 
                        $has_orders = true;
                    ?>
                    <tr>
                        <td class="text-muted">#<?php echo str_pad($row['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td class="text-muted"><?php echo $row['order_date']; ?></td>
                        <td class="text-accent fw-bold">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><span class="badge <?php echo $status_badges[$row['status']] ?? ''; ?>"><?php echo $row['status']; ?></span></td>
                        <td><a href="view_order.php?id=<?php echo $row['order_id']; ?>" class="btn btn-blue btn-sm">View</a></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_orders): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:32px; color:var(--muted);">
                            No orders yet. <a href="new_order.php" style="color:var(--accent);">Create an order</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php

} else { 
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$total_staff = $conn->query("SELECT COUNT(*) FROM staff")->fetch_row()[0];
$month_total = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM transaction_record WHERE MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetch_row()[0];
$today_total = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM transaction_record WHERE transaction_date = CURDATE()")->fetch_row()[0];
$total_tx = $conn->query("SELECT COUNT(*) FROM transaction_record")->fetch_row()[0];
$recent_tx = $conn->query("SELECT tr.*, c.customer_name, s.staff_name, p.payment_method FROM transaction_record tr JOIN payment p ON p.payment_id = tr.payment_id JOIN orders o ON o.order_id = p.order_id JOIN customer c ON c.customer_id = o.customer_id JOIN staff s ON s.staff_id = o.staff_id ORDER BY tr.transaction_date DESC LIMIT 8");
?>
    
    <div class="page-header"></div>

    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-value">₱<?php echo number_format($today_total, 2); ?></div>
                <div class="stat-label">Today's Total</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value">₱<?php echo number_format($month_total, 2); ?></div>
                <div class="stat-label">Monthly Total</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?php echo $total_tx; ?></div>
                <div class="stat-label">Transactions</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?php echo $total_staff; ?></div>
                <div class="stat-label">Accounts</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Transactions</h3>
            <a href="transactions_admin.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Transactions #</th> <th>Customer</th> <th>Staff</th> <th>Payment</th> <th>Date</th> <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $has_tx = false;
                    while ($row = $recent_tx->fetch_assoc()): 
                        $has_tx = true;
                    ?>
                    <tr>
                        <td class="text-muted">#<?php echo str_pad($row['transaction_id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td class="text-muted"><?php echo htmlspecialchars($row['staff_name']); ?></td>
                        <td><span class="badge b-info"><?php echo $row['payment_method']; ?></span></td>
                        <td class="text-muted"><?php echo $row['transaction_date']; ?></td>
                        <td class="text-accent fw-bold">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_tx): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:32px; color:var(--muted);">
                            No transactions yet.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="services.php" class="btn btn-secondary">Manage Services</a>
                    <a href="staff.php" class="btn btn-secondary">Manage Staff</a>
                </div>
            </div>
        </div>
    </div>
    
<?php } ?>

<?php require_once '../includes/footer.php'; ?>
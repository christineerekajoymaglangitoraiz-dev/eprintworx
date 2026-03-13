<?php
$active = 'orders';
$title = 'Orders';

require_once '../includes/auth.php';
require_role('staff');
require_once '../includes/db.php';

$staff_id = $_SESSION['staff_id'];
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where = "WHERE o.staff_id = $staff_id";

if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $where .= " AND (c.customer_name LIKE '%$search_escaped%' OR o.order_id = '$search_escaped')";
}

if ($status) {
    $status_escaped = $conn->real_escape_string($status);
    $where .= " AND o.status = '$status_escaped'";
}

$orders = $conn->query("
    SELECT o.*, c.customer_name FROM orders o JOIN customer c ON c.customer_id = o.customer_id 
    $where 
    ORDER BY o.created_at DESC
");

require_once '../includes/header.php';
?>

<div class="page-header">
    <a href="new_order.php" class="btn btn-primary">+ New Order</a>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Order List</span>
        
        <form method="GET" style="display: flex; gap: 9px; flex-wrap: wrap;">
            <input type="text" 
                   name="search" 
                   class="form-control" 
                   placeholder=" customer or order #" 
                   value="<?= htmlspecialchars($search) ?>" 
                   style="width: 200px;">
            
            <select name="status" class="form-control" style="width: 140px;">
                <option value="">All Status</option>
                <?php 
                $statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
                foreach ($statuses as $status_option): 
                ?>
                    <option <?= $status === $status_option ? 'selected' : '' ?>>
                        <?= $status_option ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button class="btn btn-secondary">Filter</button>
            
            <?php if ($search || $status): ?>
                <a href="orders.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order #</th> <th>Customer</th> <th>Date</th> <th>Amount</th> <th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $status_badges = ['Pending', 'Processing', 'Completed', 'Cancelled'];
                $row_count = 0;
                
                while ($row = $orders->fetch_assoc()):
                    $row_count++;
                ?>
                    <tr>
                        <td class="text-muted">#<?= str_pad($row['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td class="text-muted"><?= $row['order_date'] ?></td>
                        <td class="text-accent fw-bold">₱<?= number_format($row['total_amount'], 2) ?></td>
                        <td>
                            <span class="badge <?= $status_badges[$row['status']] ?? '' ?>"><?= $row['status'] ?></span>
                        </td>
                        <td>
                            <a href="view_order.php?id=<?= $row['order_id'] ?>" class="btn btn-blue btn-sm">View</a>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($row_count === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 32px; color: var(--muted);">
                            No orders found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
$active = 'tx_admin';
$title = 'Transaction Summary';

require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$search = trim($_GET['search'] ?? '');
$from_date = $_GET['from'] ?? '';
$to_date = $_GET['to'] ?? '';

$where = "WHERE 1=1";

if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $where .= " AND (c.customer_name LIKE '%$search_escaped%' OR s.staff_name LIKE '%$search_escaped%')";
}

if ($from_date) {
    $formatted_from = date('Y-m-d', strtotime($from_date));
    $where .= " AND tr.transaction_date >= '$formatted_from'";
}

if ($to_date) {
    $formatted_to = date('Y-m-d', strtotime($to_date));
    $where .= " AND tr.transaction_date <= '$formatted_to'";
}

$transactions = $conn->query("SELECT tr.*, c.customer_name, s.staff_name, p.payment_method, o.status FROM transaction_record tr JOIN orders o ON o.order_id = tr.order_id JOIN customer c ON c.customer_id = o.customer_id JOIN staff s ON s.staff_id = o.staff_id JOIN payment p ON p.payment_id = tr.payment_id $where ORDER BY tr.transaction_date DESC, tr.transaction_id DESC");
$totals = $conn->query("SELECT COUNT(*), COALESCE(SUM(tr.total_amount), 0) FROM transaction_record tr JOIN orders o ON o.order_id = tr.order_id JOIN customer c ON c.customer_id = o.customer_id JOIN staff s ON s.staff_id = o.staff_id JOIN payment p ON p.payment_id = tr.payment_id $where")->fetch_row();
$transaction_count = $totals[0];
$total_amount = $totals[1];

require_once '../includes/header.php';

$status_badges = ['Pending','Processing','Completed','Cancelled'];
?>

<div class="page-header"></div>

<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div>
            <div class="stat-value"><?= $transaction_count ?></div>
            <div class="stat-label">Showing</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div>
            <div class="stat-value">₱<?= number_format($total_amount, 2) ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Transactions</span>
        
        <form method="GET" style="display: flex; gap: 9px; flex-wrap: wrap;">
            <input type="text" name="search" class="form-control" placeholder="customer/staff name" value="<?= htmlspecialchars($search) ?>" style="width: 190px;">
            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from_date) ?>" style="width: 145px;">
            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to_date) ?>" style="width: 145px;">
            
            <button class="btn btn-secondary">Filter</button>
            
            <?php if ($search || $from_date || $to_date): ?>
                <a href="transactions_admin.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Transaction #</th> <th>Customer</th> <th>Staff</th> <th>Payment</th> <th>Date</th> <th>Amount</th> <th>Order Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $row_count = 0;
                
                while ($row = $transactions->fetch_assoc()): 
                    $row_count++;
                ?>
                    <tr>
                        <td class="text-muted">#<?= str_pad($row['transaction_id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($row['staff_name']) ?></td>
                        <td>
                            <span class="badge"><?= $row['payment_method'] ?></span>
                        </td>
                        <td class="text-muted"><?= $row['transaction_date'] ?></td>
                        <td class="text-accent fw-bold">₱<?= number_format($row['total_amount'], 2) ?></td>
                        <td>
                            <span class="badge <?= $status_badges[$row['status']] ?? '' ?>"><?= $row['status'] ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($row_count === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: var(--muted);">No transactions found matching your criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
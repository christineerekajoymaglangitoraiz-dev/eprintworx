<?php
$active = 'reports';
$title = 'Billing Records';

require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$period = $_GET['period'] ?? 'month';
$where = '';

if ($period === 'today') {
    $where = 'WHERE tr.transaction_date = CURDATE()';
} elseif ($period === 'week') {
    $where = 'WHERE YEARWEEK(tr.transaction_date) = YEARWEEK(CURDATE())';
} elseif ($period === 'month') {
    $where = 'WHERE MONTH(tr.transaction_date) = MONTH(CURDATE()) AND YEAR(tr.transaction_date) = YEAR(CURDATE())';
}

$total_amount = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM transaction_record tr $where")->fetch_row()[0];
$total_transactions = $conn->query("SELECT COUNT(*) FROM transaction_record tr $where")->fetch_row()[0];

$cash_total = $conn->query("
    SELECT COALESCE(SUM(tr.total_amount), 0) 
    FROM transaction_record tr 
    JOIN payment p ON p.payment_id = tr.payment_id 
    " . ($where ? $where . " AND p.payment_method = 'Cash'" : "WHERE p.payment_method = 'Cash'")
)->fetch_row()[0];

$gcash_total = $conn->query("
    SELECT COALESCE(SUM(tr.total_amount), 0) 
    FROM transaction_record tr 
    JOIN payment p ON p.payment_id = tr.payment_id 
    " . ($where ? $where . " AND p.payment_method = 'GCash'" : "WHERE p.payment_method = 'GCash'")
)->fetch_row()[0];

$top_services = $conn->query("
    SELECT sv.service_name, SUM(os.quantity) AS quantity, SUM(os.subtotal) AS amount 
    FROM order_service os 
    JOIN service sv ON sv.service_id = os.service_id 
    JOIN orders o ON o.order_id = os.order_id 
    GROUP BY os.service_id 
    ORDER BY amount DESC 
    LIMIT 8
");

$collections_by_staff = $conn->query("
    SELECT s.staff_name, COUNT(tr.transaction_id) AS transaction_count, COALESCE(SUM(tr.total_amount), 0) AS amount 
    FROM transaction_record tr 
    JOIN orders o ON o.order_id = tr.order_id 
    JOIN staff s ON s.staff_id = o.staff_id 
    " . ($where ? $where : "") . " 
    GROUP BY o.staff_id 
    ORDER BY amount DESC
");

require_once '../includes/header.php';
?>

<div class="page-header">
    
    <div class="flex gap-10 flex-wrap">
        <?php 
        $period_options = [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'all' => 'All Time'
        ];
        
        foreach ($period_options as $period_key => $period_label): 
        ?>
            <a href="?period=<?= $period_key ?>" 
               class="btn <?= $period === $period_key ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <?= $period_label ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-value">₱<?= number_format($total_amount, 2) ?></div>
            <div class="stat-label">Total Amount</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div>
            <div class="stat-value"><?= $total_transactions ?></div>
            <div class="stat-label">Transactions</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div>
            <div class="stat-value">₱<?= number_format($cash_total, 2) ?></div>
            <div class="stat-label">Cash</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div>
            <div class="stat-value">₱<?= number_format($gcash_total, 2) ?></div>
            <div class="stat-label">GCash</div>
        </div>
    </div>
</div>

<div class="g2">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Top Services</span>
        </div>
        
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Service</th> <th>Quantity</th> <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $row_count = 0;
                    while ($row = $top_services->fetch_assoc()): 
                        $row_count++;
                    ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['service_name']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td class="text-accent fw-bold">₱<?= number_format($row['amount'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($row_count === 0): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 28px; color: var(--muted);">No service data available for this period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <span class="card-title">Collections by Staff</span>
        </div>
        
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Staff</th> <th>Transactions</th> <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $row_count = 0;
                    while ($row = $collections_by_staff->fetch_assoc()): 
                        $row_count++;
                    ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['staff_name']) ?></td>
                            <td><?= $row['transaction_count'] ?></td>
                            <td class="text-accent fw-bold">₱<?= number_format($row['amount'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($row_count === 0): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 28px; color: var(--muted);">No staff data available for this period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
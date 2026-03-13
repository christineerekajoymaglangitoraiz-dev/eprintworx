<?php
$active = 'transactions';
$title = 'Transactions';

require_once '../includes/auth.php';
require_role('staff');
require_once '../includes/db.php';

$staff_id = $_SESSION['staff_id'];

$transactions = $conn->query("SELECT tr.*, c.customer_name, p.payment_method FROM transaction_record tr JOIN orders o ON o.order_id = tr.order_id JOIN customer c ON c.customer_id = o.customer_id JOIN payment p ON p.payment_id = tr.payment_id WHERE o.staff_id = $staff_id ORDER BY tr.transaction_date DESC, tr.transaction_id DESC");

require_once '../includes/header.php';
?>

<div class="page-header"></div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Transaction Records</span>
    </div>
    
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Transaction #</th> <th>Customer</th> <th>Order #</th> <th>Payment</th> <th>Date</th> <th>Total</th> <th></th>
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
                        <td class="text-muted">#<?= str_pad($row['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <span class="badge"><?= $row['payment_method'] ?></span>
                        </td>
                        <td class="text-muted"><?= $row['transaction_date'] ?></td>
                        <td class="text-accent fw-bold">₱<?= number_format($row['total_amount'], 2) ?></td>
                        <td>
                            <a href="view_order.php?id=<?= $row['order_id'] ?>" class="btn btn-blue btn-sm">View</a>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($row_count === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: var(--muted);">No transactions yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
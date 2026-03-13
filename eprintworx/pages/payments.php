<?php
$active = 'payments';
$title = 'Payments';

require_once '../includes/auth.php';
require_role('staff');
require_once '../includes/db.php';

$staff_id = $_SESSION['staff_id'];

$payments = $conn->query("
    SELECT p.*, c.customer_name FROM payment p JOIN orders o ON o.order_id = p.order_id JOIN customer c ON c.customer_id = o.customer_id WHERE o.staff_id = $staff_id ORDER BY p.payment_date DESC, p.payment_id DESC
");

require_once '../includes/header.php';
?>

<div class="page-header"></div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Payment Records</span>
    </div>
    
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Payment #</th> <th>Customer</th> <th>Method</th> <th>Date</th> <th>Amount</th> <th>Status</th> <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $row_count = 0;
                
                while ($row = $payments->fetch_assoc()):
                    $row_count++;
                ?>
                    <tr>
                        <td class="text-muted">#<?= str_pad($row['payment_id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td>
                            <span class="badge"><?= $row['payment_method'] ?></span>
                        </td>
                        <td class="text-muted"><?= $row['payment_date'] ?></td>
                        <td class="text-accent fw-bold">₱<?= number_format($row['amount_paid'], 2) ?></td>
                        <td>
                            <span class="badge"><?= $row['payment_status'] ?></span>
                        </td>
                        <td>
                            <a href="view_order.php?id=<?= $row['order_id'] ?>" class="btn btn-blue btn-sm">View</a>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($row_count === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: var(--muted);">
                            No payments yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
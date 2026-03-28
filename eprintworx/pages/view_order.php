<?php
$active = 'orders';
$title = 'View Order';
require_once '../includes/auth.php';
require_role('staff');
require_once '../includes/db.php';

$id = intval($_GET['id'] ?? 0);
$new = isset($_GET['new']);

$order = $conn->query("SELECT o.*,c.customer_name,c.contact_number,s.staff_name FROM orders o JOIN customer c ON c.customer_id=o.customer_id JOIN staff s ON s.staff_id=o.staff_id WHERE o.order_id=$id")->fetch_assoc();

if (!$order) {
    echo 'Order not found.';
    exit;
}

if ($_SESSION['role'] === 'staff' && $order['staff_id'] != $_SESSION['staff_id']) {
    header('Location: orders.php');
    exit;
}

$items_result = $conn->query("SELECT os.*,sv.service_name FROM order_service os JOIN service sv ON sv.service_id=os.service_id WHERE os.order_id=$id");
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

$payment = $conn->query("SELECT * FROM payment WHERE order_id=$id LIMIT 1")->fetch_assoc();

$allowed_statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $st = $_POST['status'];
    if (in_array($st, $allowed_statuses, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE order_id=?");
        $stmt->bind_param('si', $st, $id);
        $stmt->execute();
    }
    header("Location: view_order.php?id=$id");
    exit;
}

require_once '../includes/header.php';
?>

<?php if ($new): ?>
    <div class="alert alert-success">Order #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?> created successfully!</div>
<?php endif; ?>

<div class="page-header no-print">
    <div>
        <h1>Order #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></h1>
        <p><?= $order['order_date'] ?> &middot; <?= htmlspecialchars($order['staff_name']) ?></p>
    </div>
    <div class="flex gap-10">
        <a href="orders.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="g2 no-print" style="margin-bottom: 20px;">
    <div class="card">
        <div class="card-body">
            <div class="card-title" style="margin-bottom: 14px;">Order Details</div>
            <div class="info-row">
                <span class="info-key">Customer</span>
                <span class="info-val"><?= htmlspecialchars($order['customer_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Contact</span>
                <span class="info-val"><?= htmlspecialchars($order['contact_number'] ?: '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Date</span>
                <span class="info-val"><?= $order['order_date'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Payment</span>
                <span class="info-val">
                    <span class="badge"><?= $payment['payment_method'] ?? 'Cash' ?></span>
                </span>
            </div>
            <?php if ($order['notes']): ?>
                <div class="info-row">
                    <span class="info-key">Notes</span>
                    <span class="info-val"><?= htmlspecialchars($order['notes']) ?></span>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-key">Status</span>
                <span class="info-val">
                    <span class="badge"><?= $order['status'] ?></span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-key">Total</span>
                <span class="info-val text-accent fw-black" style="font-size: 15px;">₱<?= number_format($order['total_amount'], 2) ?></span>
            </div>
            <div class="divider"></div>
            <div style="font-size: 13px; font-weight: 700; color: var(--muted); margin-top: 15px; margin-bottom: 10px;">UPDATE STATUS</div>
            <form method="POST" style="display: flex; gap: 9px;">
                <select name="status" class="form-control">
                    <?php foreach ($allowed_statuses as $st): ?>
                        <option <?= $order['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Services Ordered</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Service</th> <th>Unit Price</th> <th>Quantity</th> <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['service_name']) ?></td>
                            <td class="text-muted">₱<?= number_format($item['unit_price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td class="fw-bold text-accent">₱<?= number_format($item['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: rgba(162, 121, 89, 0.05);">
                        <td colspan="3" style="text-align: right; font-weight: 800; padding: 12px 15px;">TOTAL</td>
                        <td class="fw-black text-accent" style="font-size: 16px;">₱<?= number_format($order['total_amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
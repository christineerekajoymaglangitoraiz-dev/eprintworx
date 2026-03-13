<?php
$active = 'new_order';
$title = 'New Order';

require_once '../includes/auth.php';
require_role('staff');
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $notes = trim($_POST['notes'] ?? '');
    $service_ids = $_POST['service_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (!$customer_name || empty(array_filter($service_ids))) {
        $error = 'Customer name and at least one service required.';
    } else {
        $stmt = $conn->prepare("SELECT customer_id FROM customer WHERE customer_name = ? AND contact_number = ? LIMIT 1");
        $stmt->bind_param('ss', $customer_name, $contact_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_customer = $result->fetch_assoc();
        $customer_id = $existing_customer ? $existing_customer['customer_id'] : null;

        if (!$customer_id) {
            $insert_customer = $conn->prepare("INSERT INTO customer (customer_name, contact_number) VALUES (?, ?)");
            $insert_customer->bind_param('ss', $customer_name, $contact_number);
            $insert_customer->execute();
            $customer_id = $conn->insert_id;
        }

        $total = 0;
        $items = [];

        foreach ($service_ids as $index => $service_id) {
            if (!$service_id) continue;

            $quantity = max(1, intval($quantities[$index] ?? 1));
            $price_query = $conn->query("SELECT price FROM service WHERE service_id = " . intval($service_id));
            $price_data = $price_query->fetch_assoc();

            if ($price_data) {
                $subtotal = $price_data['price'] * $quantity;
                $total += $subtotal;

                $items[] = [
                    'service_id' => intval($service_id),
                    'quantity' => $quantity,
                    'price' => $price_data['price'],
                    'subtotal' => $subtotal
                ];
            }
        }

        $staff_id = $_SESSION['staff_id'];
        $today = date('Y-m-d');

        $insert_order = $conn->prepare("INSERT INTO orders (customer_id, staff_id, order_date, status, total_amount, notes) VALUES (?, ?, ?, 'Pending', ?, ?)");
        $insert_order->bind_param('iisds', $customer_id, $staff_id, $today, $total, $notes);
        $insert_order->execute();
        $order_id = $conn->insert_id;

        foreach ($items as $item) {
            $insert_service = $conn->prepare("INSERT INTO order_service (order_id, service_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $insert_service->bind_param('iiidd', $order_id, $item['service_id'], $item['quantity'], $item['price'], $item['subtotal']);
            $insert_service->execute();
        }

        $insert_payment = $conn->prepare("INSERT INTO payment (order_id, payment_method, payment_date, amount_paid, payment_status) VALUES (?, ?, ?, ?, 'Paid')");
        $insert_payment->bind_param('issd', $order_id, $payment_method, $today, $total);
        $insert_payment->execute();
        $payment_id = $conn->insert_id;

        $insert_transaction = $conn->prepare("INSERT INTO transaction_record (order_id, payment_id, transaction_date, total_amount) VALUES (?, ?, ?, ?)");
        $insert_transaction->bind_param('iisd', $order_id, $payment_id, $today, $total);
        $insert_transaction->execute();

        header("Location: view_order.php?id=$order_id&new=1");
        exit;
    }
}

$services = $conn->query("SELECT * FROM service ORDER BY service_name");
$service_list = [];
while ($service = $services->fetch_assoc()) {
    $service_list[] = $service;
}

require_once '../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="page-header">
    <div></div>
    <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
</div>

<form method="POST">
    <div class="g2" style="margin-bottom: 18px;">
        <div class="card">
            <div class="card-body">
                <div class="card-title" style="margin-bottom: 16px;">Customer Info</div>
                
                <div class="form-group">
                    <label>customer name</label>
                    <input type="text" name="customer_name" class="form-control" required>
                </div><br>
                
                <div class="form-group">
                    <label>contact number</label>
                    <input type="text" name="contact_number" class="form-control" maxlength="11">
                </div><br>
                
                <div class="form-group">
                    <label>payment method</label>
                    <select name="payment_method" class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                    </select>
                </div><br>
                
                <div class="form-group">
                    <label>notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="card-title" style="margin-bottom: 16px;">Services</div>
                
                <div id="svc-rows">
                    <div class="svc-row">
                        <select name="service_id[]" class="form-control" onchange="calcRow(this)">
                            <option value="">Select service</option>
                            <?php foreach ($service_list as $service): ?>
                                <option value="<?= $service['service_id'] ?>" data-price="<?= $service['price'] ?>">
                                    <?= htmlspecialchars($service['service_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="number" name="quantity[]" class="form-control" value="1" min="1" oninput="calcRow(this)">
                        
                        <input type="text" class="form-control price-disp" readonly placeholder=" price" 
                               style="background: rgba(255,255,255,0.03);">
                        
                        <input type="text" class="form-control sub-disp" readonly placeholder=" subtotal" 
                               style="background: rgba(255,255,255,0.03);">
                        
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">x</button>
                    </div>
                </div>

                <button type="button" onclick="addRow()" class="btn btn-secondary btn-sm mt-8">
                    + Add Service
                </button>

                <div class="total-bar mt-14">
                    <span class="total-label">Total Amount</span>
                    <span class="total-value" id="grandTotal">₱0.00</span>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: right;">
        <button type="submit" class="btn btn-primary btn-lg">Confirm Order</button>
    </div>
</form>

<script>
function calcRow(element) {
    const row = element.closest('.svc-row');
    const select = row.querySelector('select');
    const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
    
    row.querySelector('.price-disp').value = price ? '₱' + price.toFixed(2) : '';
    row.querySelector('.sub-disp').value = price ? '₱' + (price * quantity).toFixed(2) : '';
    
    updateTotal();
}

function updateTotal() {
    let total = 0;
    
    document.querySelectorAll('.svc-row').forEach(row => {
        const select = row.querySelector('select');
        const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
        const selectedOption = select.options[select.selectedIndex];
        total += (selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0) * quantity;
    });
    
    document.getElementById('grandTotal').textContent = '₱' + total.toFixed(2);
}

function addRow() {
    const template = document.querySelector('.svc-row').cloneNode(true);
    
    template.querySelector('select').value = '';
    template.querySelector('input[name="quantity[]"]').value = 1;
    template.querySelector('.price-disp').value = '';
    template.querySelector('.sub-disp').value = '';
    
    template.querySelectorAll('select, input').forEach(el => {
        el.addEventListener('input', () => calcRow(el));
    });
    
    document.getElementById('svc-rows').appendChild(template);
}

function removeRow(button) {
    if (document.querySelectorAll('.svc-row').length > 1) {
        button.closest('.svc-row').remove();
        updateTotal();
    }
}

document.querySelectorAll('.svc-row select, .svc-row input').forEach(el => {
    el.addEventListener('input', () => calcRow(el));
});
</script>

<?php require_once '../includes/footer.php'; ?>
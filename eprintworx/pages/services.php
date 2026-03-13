<?php
$active = 'services';
$title = 'Services & Pricing';

require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['add'])) {
        $service_name = $conn->real_escape_string(trim($_POST['service_name']));
        $description = $conn->real_escape_string(trim($_POST['description']));
        $price = floatval($_POST['price']);
        
        if ($service_name && $price > 0) {
            $conn->query("INSERT INTO service (service_name, description, price) VALUES ('$service_name', '$description', $price)");
            $message = 'success:Service added successfully.';
        } else {
            $message = 'danger:Service name and price are required.';
        }
    }
    
    elseif (isset($_POST['update'])) {
        $service_id = intval($_POST['service_id']);
        $service_name = $conn->real_escape_string(trim($_POST['service_name']));
        $description = $conn->real_escape_string(trim($_POST['description']));
        $price = floatval($_POST['price']);

        $conn->query("UPDATE service SET service_name = '$service_name', description = '$description', price = $price WHERE service_id = $service_id");
        
        $message = 'success:Service updated successfully.';
    }
    
    elseif (isset($_POST['delete'])) {
        $service_id = intval($_POST['service_id']);
        
        try {
            $check_service = $conn->query("SELECT service_name FROM service WHERE service_id = $service_id");
            if ($check_service->num_rows == 0) {
                $message = 'danger:Service not found.';
            } else {
                $service_data = $check_service->fetch_assoc();
                
                $check_orders = $conn->query("SELECT COUNT(*) as order_count FROM order_service WHERE service_id = $service_id");
                $order_data = $check_orders->fetch_assoc();
                
                if ($order_data['order_count'] > 0) {
                    $message = 'danger:Service "' . htmlspecialchars($service_data['service_name']) . '" cannot be deleted because it is used in ' . $order_data['order_count'] . ' order(s).';
                } else {
                    if ($conn->query("DELETE FROM service WHERE service_id = $service_id")) {
                        $message = 'success:Service deleted successfully.';
                    } else {
                        throw new Exception($conn->error);
                    }
                }
            }
        } catch (Exception $e) {
            $message = 'danger:Error deleting service: ' . $e->getMessage();
        }
    }
}

$services = $conn->query("SELECT * FROM service ORDER BY service_name");
$service_list = [];

while ($service = $services->fetch_assoc()) {
    $service_list[] = $service;
}

require_once '../includes/header.php';

if ($message) {
    list($message_type, $message_text) = explode(':', $message, 2);
}
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
        <?= htmlspecialchars($message_text) ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <button onclick="openModal('addModal')" class="btn btn-primary">+ Add Service</button>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Service Catalog (<?= count($service_list) ?> services)</span>
    </div>
    
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Service #</th> <th>Service Name</th> <th>Description</th> <th>Price</th> <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($service_list as $row): ?>
                    <tr>
                        <td class="text-muted"><?= $row['service_id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['service_name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                        <td class="text-accent fw-bold">₱<?= number_format($row['price'], 2) ?></td>
                        <td style="display: flex; gap: 7px;">
                            <button onclick="editService(<?= htmlspecialchars(json_encode($row)) ?>)" class="btn btn-warning btn-sm">Edit</button>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this service?')">
                                <input type="hidden" name="service_id" value="<?= $row['service_id'] ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($service_list)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 32px; color: var(--muted);">No services found. Click "Add Service" to create one.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Add New Service</span>
            <button class="modal-close" onclick="closeModal('addModal')">x</button>
        </div>
        
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>service name</label>
                    <input type="text" name="service_name" class="form-control" required>
                </div><br>
                
                <div class="form-group">
                    <label>description</label>
                    <input type="text" name="description" class="form-control">
                </div><br>
                
                <div class="form-group">
                    <label>price</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0.01" required>
                </div><br>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="add" class="btn btn-primary">Add Service</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Edit Service</span>
            <button class="modal-close" onclick="closeModal('editModal')">x</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="service_id" id="edit_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>service name</label>
                    <input type="text" name="service_name" id="edit_name" class="form-control" required>
                </div><br>
                
                <div class="form-group">
                    <label>description</label>
                    <input type="text" name="description" id="edit_description" class="form-control">
                </div><br>
                
                <div class="form-group">
                    <label>price</label>
                    <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0.01" required>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="update" class="btn btn-warning">Update Service</button>
            </div>
        </form>
    </div>
</div>

<script>
function editService(service) {
    document.getElementById('edit_id').value = service.service_id;
    document.getElementById('edit_name').value = service.service_name;
    document.getElementById('edit_description').value = service.description || '';
    document.getElementById('edit_price').value = service.price;
    openModal('editModal');
}
</script>

<?php require_once '../includes/footer.php'; ?>
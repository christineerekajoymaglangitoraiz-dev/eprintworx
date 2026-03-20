<?php
$active = 'staff';
$title = 'Accounts';

require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['add'])) {
        $staff_name = $conn->real_escape_string(trim($_POST['staff_name']));
        $username = $conn->real_escape_string(trim($_POST['username']));
        $password = md5(trim($_POST['password']));
        $role = 'staff';
        
        $check = $conn->query("SELECT staff_id FROM staff WHERE username = '$username'")->fetch_assoc();
        
        if ($check) {
            $message = 'danger:Username already exists.';
        } else {
            $conn->query("INSERT INTO staff (staff_name, username, password, role) VALUES ('$staff_name', '$username', '$password', '$role')");
            $message = 'success:Staff account added successfully.';
        }
    }
    
    elseif (isset($_POST['delete'])) {
        $staff_id = intval($_POST['staff_id']);
        
        $check_admin = $conn->query("SELECT role FROM staff WHERE staff_id = $staff_id")->fetch_assoc();
        
        if ($check_admin && $check_admin['role'] === 'admin') {
            $message = 'danger:Cannot delete admin account.';
        }
        elseif ($staff_id == $_SESSION['staff_id']) {
            $message = 'danger:You cannot delete your own account.';
        } else {
            $check_orders = $conn->query("SELECT COUNT(*) as order_count FROM orders WHERE staff_id = $staff_id")->fetch_assoc();
            
            if ($check_orders['order_count'] > 0) {
                $message = 'danger:Cannot delete staff with existing orders. Transfer orders first.';
            } else {
                $conn->query("DELETE FROM staff WHERE staff_id = $staff_id");
                $message = 'success:Staff account deleted successfully.';
            }
        }
    }
}

$staff = $conn->query("SELECT * FROM staff WHERE role = 'staff' ORDER BY staff_name");

$admin = $conn->query("SELECT * FROM staff WHERE role = 'admin' LIMIT 1")->fetch_assoc();

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
    <button onclick="openModal('addModal')" class="btn btn-primary">+ Add Staff</button>
</div>

<?php if ($admin): ?>
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <span class="card-title"> Admin Account</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th> <th>Name</th> <th>Username</th> <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-muted"><?= $admin['staff_id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($admin['staff_name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($admin['username']) ?></td>
                    <td><span class="badge">ADMIN</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"> Staff Account</span>
    </div>
    
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th> <th>Name</th> <th>Username</th> <th>Role</th> <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $row_count = 0;
                while ($row = $staff->fetch_assoc()): 
                    $row_count++;
                ?>
                    <tr>
                        <td class="text-muted"><?= $row['staff_id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['staff_name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($row['username']) ?></td>
                        <td>
                            <span class="badge b-blue">STAFF</span>
                        </td>
                        <td>
                            <form method="POST" 
                                  style="display: inline;" 
                                  onsubmit="return confirm('Are you sure you want to delete this staff member?')">
                                <input type="hidden" name="staff_id" value="<?= $row['staff_id'] ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($row_count === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 32px; color: var(--muted);">No staff members found. Click "Add Staff" to create one.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Add New Staff Member</span>
            <button class="modal-close" onclick="closeModal('addModal')">x</button>
        </div>
        
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>full name</label>
                    <input type="text" name="staff_name" class="form-control" required>
                </div><br>
                
                <div class="form-group">
                    <label>username</label>
                    <input type="text" name="username" class="form-control" required>
                </div><br>
                
                <div class="form-group">
                    <label>password</label>
                    <input type="password" name="password" class="form-control" required>
                </div><br>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="add" class="btn btn-primary">Add Staff</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

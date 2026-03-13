<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

$_active = $active ?? '';
$_title = $title ?? 'EPrintworx';
$_role = $_SESSION['role'] ?? 'staff';
$_name = $_SESSION['staff_name'] ?? '';
$_init = strtoupper(substr($_name, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_title); ?> — EPrintworx</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="app-layout">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sb-brand">EPrintworx</div>
            </div>
            
            <div class="sb-nav">
                <a href="dashboard.php" class="nav-item <?php echo $_active === 'dashboard' ? 'active' : ''; ?>"> Dashboard</a>

                <?php if ($_role === 'staff'): ?>
                    <a href="new_order.php" class="nav-item <?php echo $_active === 'new_order' ? 'active' : ''; ?>"> New Order</a>
                    <a href="orders.php" class="nav-item <?php echo $_active === 'orders' ? 'active' : ''; ?>"> Orders</a>
                    <a href="payments.php" class="nav-item <?php echo $_active === 'payments' ? 'active' : ''; ?>"> Payments</a>
                    <a href="transactions.php" class="nav-item <?php echo $_active === 'transactions' ? 'active' : ''; ?>"> Transactions</a>
                <?php endif; ?>

                <?php if ($_role === 'admin'): ?>
                    <a href="services.php" class="nav-item <?php echo $_active === 'services' ? 'active' : ''; ?>"> Services &amp; Pricing</a>
                    <a href="staff.php" class="nav-item <?php echo $_active === 'staff' ? 'active' : ''; ?>"> Accounts</a>
                    <a href="reports.php" class="nav-item <?php echo $_active === 'reports' ? 'active' : ''; ?>"> Billing Records</a>
                    <a href="transactions_admin.php" class="nav-item <?php echo $_active === 'tx_admin' ? 'active' : ''; ?>"> Transaction Summary</a>
                <?php endif; ?>
            </div>
            
            <div class="sb-footer">
                <a href="../logout.php" class="nav-item" style="color: var(--accent);"> Logout</a>
            </div>
        </nav>

        <div class="main-content">
            <div class="topbar">
                <div class="topbar-title"><?php echo htmlspecialchars($_title); ?></div>
            </div>
            
            <div class="page-body">
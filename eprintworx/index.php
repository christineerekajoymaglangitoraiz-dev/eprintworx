<?php
session_start();

if (isset($_SESSION['staff_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $password_ok = false;
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $password_ok = true;
            } elseif ($user['password'] === md5($password)) {
                $password_ok = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE staff SET password = ? WHERE staff_id = ?");
                $upd->bind_param('si', $new_hash, $user['staff_id']);
                $upd->execute();
            }
        }

        if ($password_ok) {
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['staff_name'] = $user['staff_name'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: pages/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPrintworx — Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-logo">
                <div class="brand">EPrintworx</div>
                <div class="brand-sub">Billing System</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                   <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">USERNAME</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">PASSWORD</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                
                <div class="mt-14">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
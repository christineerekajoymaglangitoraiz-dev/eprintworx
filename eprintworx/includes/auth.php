<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['staff_id'])) {
        header('Location: ../index.php');
        exit;
    }
}

function require_role($role) {
    require_login();
    
    if ($_SESSION['role'] !== $role) {
        header('Location: ../pages/dashboard.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function get_session_user() {
    return [
        'id' => $_SESSION['staff_id'] ?? null,
        'name' => $_SESSION['staff_name'] ?? '',
        'role' => $_SESSION['role'] ?? ''
    ];
}
?>
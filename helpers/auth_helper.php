<?php
function requireLogin() {
    if (!isset($_SESSION['id_user'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        $redirect = ($_SESSION['role'] === 'admin') ? '/admin/index.php' : '/user/index.php';
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

function generateKodeWarga($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'warga'");
    $data = mysqli_fetch_assoc($res);
    $nextNum = (int)$data['total'] + 1;
    return 'WRG-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}
?>

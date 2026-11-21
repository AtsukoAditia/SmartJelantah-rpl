<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: ' . BASE_URL . '/admin/index.php');
} else {
    header('Location: ' . BASE_URL . '/user/index.php');
}
exit;
?>


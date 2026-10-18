<?php
require __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    unset($_SESSION['user'], $_SESSION['cart']);
    session_regenerate_id(true);
    set_flash('Bạn đã đăng xuất.');
    header('Location: login.php');
    exit;
}
header('Location: shop.php');

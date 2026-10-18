<?php
$u = current_user();
$cartCount = ($u && $u['role'] === 'buyer') ? array_sum($_SESSION['cart'] ?? []) : 0;
$roleLabel = ['seller' => 'Người bán', 'buyer' => 'Người mua'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Cửa hàng gia dụng & điện tử') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <nav class="navbar navbar-expand-md navbar-dark bg-dark rounded mb-4 px-3">
    <a class="navbar-brand" href="shop.php">🛒 Điện máy Demo</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="shop.php">Cửa hàng</a></li>
        <?php if ($u && $u['role'] === 'seller'): ?>
          <li class="nav-item"><a class="nav-link" href="index.php">Quản lý sản phẩm</a></li>
          <li class="nav-item"><a class="nav-link" href="orders.php">Đơn hàng</a></li>
        <?php elseif ($u): ?>
          <li class="nav-item"><a class="nav-link" href="orders.php">Đơn hàng của tôi</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if (!$u): ?>
          <a href="login.php" class="btn btn-outline-light btn-sm">Đăng nhập</a>
          <a href="register.php" class="btn btn-warning btn-sm">Đăng ký</a>
        <?php else: ?>
          <?php if ($u['role'] === 'buyer'): ?>
            <a href="cart.php" class="btn btn-warning btn-sm">Giỏ hàng <span class="badge text-bg-dark cart-count"><?= (int)$cartCount ?></span></a>
          <?php endif; ?>
          <span class="text-light small">Xin chào, <?= e($u['fullname']) ?>
            <span class="badge text-bg-secondary"><?= e($roleLabel[$u['role']] ?? $u['role']) ?></span></span>
          <form method="post" action="logout.php" class="m-0">
            <?= csrf_field() ?>
            <button class="btn btn-outline-light btn-sm">Đăng xuất</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </nav>

<?php $cartCount = array_sum($_SESSION['cart'] ?? []); ?>
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
  <nav class="navbar navbar-expand navbar-dark bg-dark rounded mb-4 px-3">
    <a class="navbar-brand" href="shop.php">🛒 Điện máy Demo</a>
    <ul class="navbar-nav me-auto">
      <li class="nav-item"><a class="nav-link" href="shop.php">Cửa hàng</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php">Quản lý sản phẩm</a></li>
    </ul>
    <a href="cart.php" class="btn btn-warning btn-sm">Giỏ hàng <span class="badge text-bg-dark"><?= (int)$cartCount ?></span></a>
  </nav>

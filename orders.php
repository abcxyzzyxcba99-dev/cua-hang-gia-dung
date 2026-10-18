<?php
require __DIR__ . '/includes/db.php';
$user = require_login();     // người mua: đơn của mình; người bán: tất cả đơn

if ($user['role'] === 'seller') {
    $stmt = $pdo->query('SELECT o.*, u.username FROM tblOrders o JOIN tblUsers u ON u.id = o.user_id ORDER BY o.id DESC');
} else {
    $stmt = $pdo->prepare('SELECT o.*, u.username FROM tblOrders o JOIN tblUsers u ON u.id = o.user_id WHERE o.user_id = :uid ORDER BY o.id DESC');
    $stmt->execute([':uid' => $user['id']]);
}
$orders = $stmt->fetchAll();

$flash = get_flash();
$pageTitle = $user['role'] === 'seller' ? 'Quản lý đơn hàng' : 'Đơn hàng của tôi';
require __DIR__ . '/includes/header.php';
?>
<h4 class="mb-3"><?= e($pageTitle) ?></h4>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>

<div class="table-responsive shadow-sm">
  <table class="table table-striped table-hover align-middle mb-0 small">
    <thead class="table-dark">
      <tr>
        <th>Mã đơn</th>
        <?php if ($user['role'] === 'seller'): ?><th>Khách hàng</th><?php endif; ?>
        <th>Ngày đặt</th><th class="text-end">Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$orders): ?>
      <tr><td colspan="7" class="text-center text-muted py-3">Chưa có đơn hàng nào.</td></tr>
    <?php endif; ?>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int)$o['id'] ?></td>
        <?php if ($user['role'] === 'seller'): ?><td><?= e($o['fullname']) ?> <span class="text-muted">(<?= e($o['username']) ?>)</span></td><?php endif; ?>
        <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
        <td class="text-end"><?= money($o['total']) ?></td>
        <td><?= e(PAY_METHODS[$o['payment_method']] ?? $o['payment_method']) ?><br><?= pay_badge($o['payment_status']) ?></td>
        <td><?= order_badge($o['order_status']) ?></td>
        <td><a href="order.php?id=<?= (int)$o['id'] ?>" class="btn btn-outline-primary btn-sm py-0">Xem</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require __DIR__ . '/includes/db.php';
$user = require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT o.*, u.username FROM tblOrders o JOIN tblUsers u ON u.id = o.user_id WHERE o.id = :id');
$stmt->execute([':id' => $id]);
$order = $stmt->fetch();

// Người mua chỉ xem được đơn của chính mình
if (!$order || ($user['role'] === 'buyer' && (int)$order['user_id'] !== (int)$user['id'])) {
    set_flash('Không tìm thấy đơn hàng.', 'danger');
    header('Location: orders.php'); exit;
}

// Hủy đơn: hoàn trả tồn kho
function cancel_order(PDO $pdo, int $orderId): bool {
    $pdo->beginTransaction();
    $s = $pdo->prepare('SELECT order_status FROM tblOrders WHERE id = :id FOR UPDATE');
    $s->execute([':id' => $orderId]);
    if ($s->fetchColumn() === 'cancelled') { $pdo->rollBack(); return false; }

    $it = $pdo->prepare('SELECT product_id, qty FROM tblOrderItems WHERE order_id = :id');
    $it->execute([':id' => $orderId]);
    $upd = $pdo->prepare('UPDATE tblProducts SET quantity = quantity + :q WHERE id = :pid');
    foreach ($it->fetchAll() as $row) {
        if ($row['product_id']) $upd->execute([':q' => $row['qty'], ':pid' => $row['product_id']]);
    }
    $pdo->prepare("UPDATE tblOrders SET order_status = 'cancelled' WHERE id = :id")->execute([':id' => $orderId]);
    $pdo->commit();
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // Người mua tự hủy đơn khi còn "Mới đặt" và chưa thanh toán
    if ($action === 'cancel' && $user['role'] === 'buyer') {
        if ($order['order_status'] === 'new' && $order['payment_status'] === 'unpaid') {
            cancel_order($pdo, $id);
            set_flash('Đã hủy đơn hàng #' . $id . '.');
        } else {
            set_flash('Đơn hàng này không thể tự hủy.', 'danger');
        }
        header('Location: order.php?id=' . $id); exit;
    }

    // Người bán cập nhật trạng thái đơn / thanh toán
    if ($action === 'update' && $user['role'] === 'seller') {
        $st = (string)($_POST['order_status'] ?? '');
        $ps = (string)($_POST['payment_status'] ?? '');
        if (!array_key_exists($st, ORDER_STATUS) || !in_array($ps, ['paid', 'unpaid'], true)) {
            set_flash('Dữ liệu không hợp lệ.', 'danger');
        } elseif ($order['order_status'] === 'cancelled') {
            set_flash('Đơn đã hủy không thể thay đổi.', 'danger');
        } else {
            if ($st === 'cancelled') {
                cancel_order($pdo, $id);
            } else {
                $pdo->prepare('UPDATE tblOrders SET order_status = :st WHERE id = :id')->execute([':st' => $st, ':id' => $id]);
            }
            $pdo->prepare('UPDATE tblOrders SET payment_status = :ps WHERE id = :id')->execute([':ps' => $ps, ':id' => $id]);
            set_flash('Cập nhật đơn hàng thành công.');
        }
        header('Location: order.php?id=' . $id); exit;
    }
}

$it = $pdo->prepare('SELECT * FROM tblOrderItems WHERE order_id = :id');
$it->execute([':id' => $id]);
$items = $it->fetchAll();

$flash = get_flash();
$pageTitle = 'Đơn hàng #' . $id;
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Đơn hàng #<?= (int)$order['id'] ?></h4>
  <a href="orders.php" class="btn btn-outline-secondary btn-sm">← Danh sách đơn</a>
</div>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>

<?php if ($order['payment_method'] === 'bank' && $order['payment_status'] === 'unpaid' && $order['order_status'] !== 'cancelled' && $user['role'] === 'buyer'): ?>
  <div class="alert alert-info">
    <strong>Vui lòng chuyển khoản <?= money($order['total']) ?></strong><br>
    Ngân hàng: Demo Bank · Số tài khoản: <code>0123456789</code> · Chủ tài khoản: CUA HANG DIEN MAY DEMO<br>
    Nội dung chuyển khoản: <code>DH<?= (int)$order['id'] ?></code><br>
    <small class="text-muted">(Thông tin mang tính minh họa cho bản demo.)</small>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header fw-bold">Sản phẩm</div>
      <table class="table mb-0 align-middle">
        <thead><tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead>
        <tbody>
        <?php foreach ($items as $row): ?>
          <tr>
            <td><?= e($row['product_name']) ?></td>
            <td class="text-end"><?= money($row['price']) ?></td>
            <td class="text-center"><?= (int)$row['qty'] ?></td>
            <td class="text-end"><?= money($row['price'] * $row['qty']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Tổng cộng</td><td class="text-end text-danger"><?= money($order['total']) ?></td></tr></tfoot>
      </table>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card shadow-sm mb-3">
      <div class="card-header fw-bold">Thông tin đơn hàng</div>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item">Ngày đặt: <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></li>
        <li class="list-group-item">Người nhận: <?= e($order['fullname']) ?> · <?= e($order['phone']) ?></li>
        <li class="list-group-item">Địa chỉ: <?= e($order['address']) ?></li>
        <?php if ($user['role'] === 'seller'): ?>
          <li class="list-group-item">Tài khoản đặt: <?= e($order['username']) ?></li>
        <?php endif; ?>
        <li class="list-group-item">Thanh toán: <?= e(PAY_METHODS[$order['payment_method']] ?? '') ?> <?= pay_badge($order['payment_status']) ?></li>
        <li class="list-group-item">Trạng thái: <?= order_badge($order['order_status']) ?></li>
      </ul>
    </div>

    <?php if ($user['role'] === 'seller' && $order['order_status'] !== 'cancelled'): ?>
      <form method="post" class="card shadow-sm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
        <div class="card-header fw-bold">Xử lý đơn hàng</div>
        <div class="card-body row g-2">
          <div class="col-6">
            <label class="form-label small">Trạng thái đơn</label>
            <select name="order_status" class="form-select form-select-sm">
              <?php foreach (ORDER_STATUS as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= $order['order_status'] === $k ? 'selected' : '' ?>><?= e($v[0]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label small">Thanh toán</label>
            <select name="payment_status" class="form-select form-select-sm">
              <option value="unpaid" <?= $order['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
              <option value="paid"   <?= $order['payment_status'] === 'paid'   ? 'selected' : '' ?>>Đã thanh toán</option>
            </select>
          </div>
          <div class="col-12 form-text">Chọn "Đã hủy" sẽ hoàn lại số lượng vào kho.</div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-warning btn-sm">Cập nhật</button></div>
      </form>
    <?php endif; ?>

    <?php if ($user['role'] === 'buyer' && $order['order_status'] === 'new' && $order['payment_status'] === 'unpaid'): ?>
      <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
        <button class="btn btn-outline-danger btn-sm">Hủy đơn hàng</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

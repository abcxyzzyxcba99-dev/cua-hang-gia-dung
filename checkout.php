<?php
require __DIR__ . '/includes/db.php';
$user = require_login('buyer');

list($items, $total) = load_cart($pdo);
if (!$items) {
    set_flash('Giỏ hàng đang trống.', 'warning');
    header('Location: cart.php'); exit;
}

$data   = ['fullname' => $user['fullname'], 'phone' => '', 'address' => '', 'payment_method' => 'cod'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'fullname'       => trim($_POST['fullname'] ?? ''),
        'phone'          => trim($_POST['phone'] ?? ''),
        'address'        => trim($_POST['address'] ?? ''),
        'payment_method' => (string)($_POST['payment_method'] ?? ''),
    ];
    if (mb_strlen($data['fullname']) < 3 || mb_strlen($data['fullname']) > 100) $errors['fullname'] = 'Họ tên phải từ 3 đến 100 ký tự.';
    if (!preg_match('/^0\d{9}$/', $data['phone'])) $errors['phone'] = 'Số điện thoại gồm 10 số, bắt đầu bằng 0.';
    if ($data['address'] === '' || mb_strlen($data['address']) > 255) $errors['address'] = 'Địa chỉ không được để trống (tối đa 255 ký tự).';
    if (!array_key_exists($data['payment_method'], PAY_METHODS)) $errors['payment_method'] = 'Vui lòng chọn phương thức thanh toán.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Khóa và kiểm tra lại tồn kho, lấy giá từ DB (không tin dữ liệu từ trình duyệt)
            $lines = []; $sum = 0;
            foreach ($_SESSION['cart'] as $pid => $qty) {
                $s = $pdo->prepare('SELECT * FROM tblProducts WHERE id = :id AND status = 1 FOR UPDATE');
                $s->execute([':id' => $pid]);
                $p = $s->fetch();
                $qty = (int)$qty;
                if (!$p || $qty <= 0 || (int)$p['quantity'] < $qty) {
                    throw new RuntimeException('Sản phẩm "' . ($p['name'] ?? '#' . $pid) . '" không đủ số lượng, vui lòng cập nhật giỏ hàng.');
                }
                $lines[] = [$p, $qty];
                $sum += (float)$p['price'] * $qty;
            }

            $payStatus = $data['payment_method'] === 'wallet' ? 'paid' : 'unpaid';   // ví điện tử: mô phỏng thanh toán ngay
            $ins = $pdo->prepare(
                'INSERT INTO tblOrders (user_id, fullname, phone, address, payment_method, payment_status, total)
                 VALUES (:uid, :fn, :ph, :ad, :pm, :ps, :tt)'
            );
            $ins->execute([
                ':uid' => $user['id'], ':fn' => $data['fullname'], ':ph' => $data['phone'],
                ':ad' => $data['address'], ':pm' => $data['payment_method'], ':ps' => $payStatus, ':tt' => $sum,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $insItem = $pdo->prepare('INSERT INTO tblOrderItems (order_id, product_id, product_name, price, qty) VALUES (:o, :p, :n, :pr, :q)');
            $upd     = $pdo->prepare('UPDATE tblProducts SET quantity = quantity - :q WHERE id = :id');
            foreach ($lines as $ln) {
                list($p, $qty) = $ln;
                $insItem->execute([':o' => $orderId, ':p' => $p['id'], ':n' => $p['name'], ':pr' => $p['price'], ':q' => $qty]);
                $upd->execute([':q' => $qty, ':id' => $p['id']]);
            }
            $pdo->commit();

            $_SESSION['cart'] = [];
            set_flash('Đặt hàng thành công! Mã đơn hàng của bạn là #' . $orderId . '.');
            header('Location: order.php?id=' . $orderId);
            exit;
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors['cart'] = $ex->getMessage();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors['cart'] = 'Có lỗi khi tạo đơn hàng, vui lòng thử lại.';
        }
    }
}

function bad($errors, $k) { return isset($errors[$k]) ? ' is-invalid' : ''; }
function msg($errors, $k)  { return isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : ''; }

$pageTitle = 'Thanh toán';
require __DIR__ . '/includes/header.php';
?>
<h4 class="mb-3">Thanh toán</h4>
<?php if (isset($errors['cart'])): ?>
  <div class="alert alert-danger"><?= e($errors['cart']) ?> <a href="cart.php">Quay lại giỏ hàng</a></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-7">
    <form method="post" novalidate class="card shadow-sm">
      <?= csrf_field() ?>
      <div class="card-header fw-bold">Thông tin giao hàng</div>
      <div class="card-body row g-3">
        <div class="col-md-6">
          <label class="form-label">Họ và tên</label>
          <input type="text" name="fullname" class="form-control<?= bad($errors,'fullname') ?>" value="<?= e($data['fullname']) ?>">
          <?= msg($errors,'fullname') ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Số điện thoại</label>
          <input type="text" name="phone" class="form-control<?= bad($errors,'phone') ?>" value="<?= e($data['phone']) ?>">
          <?= msg($errors,'phone') ?>
        </div>
        <div class="col-12">
          <label class="form-label">Địa chỉ giao hàng</label>
          <input type="text" name="address" class="form-control<?= bad($errors,'address') ?>" value="<?= e($data['address']) ?>">
          <?= msg($errors,'address') ?>
        </div>

        <div class="col-12">
          <label class="form-label fw-bold">Phương thức thanh toán</label>
          <?php
          $desc = [
              'cod'    => 'Trả tiền mặt cho nhân viên khi nhận hàng.',
              'bank'   => 'Chuyển khoản theo thông tin hiển thị sau khi đặt hàng; người bán xác nhận khi nhận được tiền.',
              'wallet' => 'Mô phỏng thanh toán qua ví điện tử: đơn được ghi nhận là đã thanh toán ngay (không kết nối cổng thanh toán thật).',
          ];
          foreach (PAY_METHODS as $key => $label): ?>
            <div class="form-check border rounded p-2 ps-5 mb-2">
              <input class="form-check-input" type="radio" name="payment_method" id="pm-<?= e($key) ?>"
                     value="<?= e($key) ?>" <?= $data['payment_method'] === $key ? 'checked' : '' ?>>
              <label class="form-check-label" for="pm-<?= e($key) ?>">
                <strong><?= e($label) ?></strong><br><small class="text-muted"><?= e($desc[$key]) ?></small>
              </label>
            </div>
          <?php endforeach; ?>
          <?= msg($errors,'payment_method') ?>
        </div>
      </div>
      <div class="card-footer text-end">
        <a href="cart.php" class="btn btn-outline-secondary">← Giỏ hàng</a>
        <button class="btn btn-success">Xác nhận đặt hàng · <?= money($total) ?></button>
      </div>
    </form>
  </div>

  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header fw-bold">Đơn hàng của bạn</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($items as $it): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span><?= e($it['name']) ?> <span class="text-muted">× <?= (int)$it['qty'] ?></span></span>
            <span><?= money($it['subtotal']) ?></span>
          </li>
        <?php endforeach; ?>
        <li class="list-group-item d-flex justify-content-between fw-bold">
          <span>Tổng cộng</span><span class="text-danger"><?= money($total) ?></span>
        </li>
      </ul>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require __DIR__ . '/includes/db.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];   // [product_id => qty]

function fetch_product(PDO $pdo, int $id) {
    $s = $pdo->prepare('SELECT * FROM tblProducts WHERE id = :id AND status = 1');
    $s->execute([':id' => $id]);
    return $s->fetch();
}

// Trả về số lượng (int) nếu hợp lệ: số nguyên > 0, ngược lại trả về null
function parse_qty($raw) {
    $raw = trim((string)$raw);
    return (ctype_digit($raw) && (int)$raw > 0) ? (int)$raw : null;
}

$errors   = [];
$customer = ['fullname' => '', 'phone' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Thêm vào giỏ
    if ($action === 'add') {
        $id = (int)($_POST['id'] ?? 0);
        $p  = fetch_product($pdo, $id);
        if (!$p || (int)$p['quantity'] <= 0) {
            set_flash('Sản phẩm không còn để bán.', 'danger');
            header('Location: shop.php'); exit;
        }
        $qty = parse_qty($_POST['qty'] ?? '1');
        if ($qty === null) {
            set_flash('Số lượng đặt phải là số nguyên lớn hơn 0.', 'danger');
            header('Location: shop.php'); exit;
        }
        $cur   = $_SESSION['cart'][$id] ?? 0;
        $stock = (int)$p['quantity'];
        if ($cur + $qty > $stock) {
            $_SESSION['cart'][$id] = $stock;
            set_flash('Chỉ còn ' . $stock . ' sản phẩm "' . $p['name'] . '", đã thêm tối đa vào giỏ.', 'warning');
        } else {
            $_SESSION['cart'][$id] = $cur + $qty;
            set_flash('Đã thêm "' . $p['name'] . '" vào giỏ hàng.');
        }
        header('Location: shop.php'); exit;
    }

    // Cập nhật số lượng
    if ($action === 'update') {
        $msgs = [];
        foreach (($_POST['qty'] ?? []) as $id => $raw) {
            $id = (int)$id;
            $p  = fetch_product($pdo, $id);
            if (!$p) { unset($_SESSION['cart'][$id]); continue; }

            $qty = parse_qty($raw);
            if ($qty === null) {
                // số lượng <= 0, rỗng, số thập phân hoặc không phải số -> báo lỗi, giữ nguyên số lượng cũ
                $msgs[] = 'Số lượng của "' . $p['name'] . '" phải là số nguyên lớn hơn 0 (muốn bỏ sản phẩm, hãy bấm nút Xóa).';
                continue;
            }
            $_SESSION['cart'][$id] = min($qty, (int)$p['quantity']);
        }
        if ($msgs) set_flash(implode(' ', $msgs), 'danger');
        else       set_flash('Đã cập nhật giỏ hàng.');
        header('Location: cart.php'); exit;
    }

    // Xóa 1 sản phẩm khỏi giỏ
    if ($action === 'remove') {
        unset($_SESSION['cart'][(int)($_POST['id'] ?? 0)]);
        header('Location: cart.php'); exit;
    }

    // Đặt hàng (demo): kiểm tra thông tin, trừ tồn kho
    if ($action === 'checkout') {
        $customer = [
            'fullname' => trim($_POST['fullname'] ?? ''),
            'phone'    => trim($_POST['phone'] ?? ''),
            'address'  => trim($_POST['address'] ?? ''),
        ];
        if (mb_strlen($customer['fullname']) < 3) $errors['fullname'] = 'Họ tên phải có ít nhất 3 ký tự.';
        if (!preg_match('/^0\d{9}$/', $customer['phone'])) $errors['phone'] = 'Số điện thoại gồm 10 số, bắt đầu bằng 0.';
        if ($customer['address'] === '') $errors['address'] = 'Địa chỉ không được để trống.';
        if (!$_SESSION['cart']) $errors['cart'] = 'Giỏ hàng đang trống.';

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                foreach ($_SESSION['cart'] as $id => $qty) {
                    if ((int)$qty <= 0) {
                        throw new RuntimeException('Số lượng đặt phải lớn hơn 0.');
                    }
                    $s = $pdo->prepare('SELECT * FROM tblProducts WHERE id = :id AND status = 1 FOR UPDATE');
                    $s->execute([':id' => $id]);
                    $p = $s->fetch();
                    if (!$p || (int)$p['quantity'] < $qty) {
                        throw new RuntimeException('Sản phẩm "' . ($p['name'] ?? "#$id") . '" không đủ số lượng.');
                    }
                    $u = $pdo->prepare('UPDATE tblProducts SET quantity = quantity - :q WHERE id = :id');
                    $u->execute([':q' => $qty, ':id' => $id]);
                }
                $pdo->commit();
                $_SESSION['cart'] = [];
                set_flash('Đặt hàng thành công! Cảm ơn ' . $customer['fullname'] . ', chúng tôi sẽ liên hệ qua số ' . $customer['phone'] . '.');
                header('Location: shop.php'); exit;
            } catch (RuntimeException $ex) {
                $pdo->rollBack();
                $errors['cart'] = $ex->getMessage() . ' Vui lòng cập nhật lại giỏ hàng.';
            }
        }
    }
}

// Nạp thông tin sản phẩm trong giỏ
$items = []; $total = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    $p = fetch_product($pdo, (int)$id);
    if (!$p) { unset($_SESSION['cart'][$id]); continue; }
    $qty = min((int)$qty, max(1, (int)$p['quantity']));
    $_SESSION['cart'][$id] = $qty;
    $p['qty'] = $qty;
    $p['subtotal'] = $qty * (float)$p['price'];
    $total += $p['subtotal'];
    $items[] = $p;
}

$flash = get_flash();
$pageTitle = 'Giỏ hàng';
require __DIR__ . '/includes/header.php';
?>

<h4 class="mb-3">Giỏ hàng</h4>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>
<?php if (isset($errors['cart'])): ?>
  <div class="alert alert-danger"><?= e($errors['cart']) ?></div>
<?php endif; ?>

<?php if (!$items): ?>
  <div class="card card-body text-center text-muted py-5">
    Giỏ hàng trống. <a href="shop.php">Tiếp tục mua sắm</a>
  </div>
<?php else: ?>
  <form method="post" class="mb-4">
    <input type="hidden" name="action" value="update">
    <div class="table-responsive shadow-sm">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-dark">
          <tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th style="width:110px">Số lượng</th><th class="text-end">Thành tiền</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($it['image']): ?>
                  <img src="<?= e(image_src($it['image'])) ?>" alt="" class="rounded border" style="width:56px;height:42px;object-fit:cover;">
                <?php endif; ?>
                <div><?= e($it['name']) ?><br><small class="text-muted"><?= e($it['product_code']) ?> · tồn <?= (int)$it['quantity'] ?></small></div>
              </div>
            </td>
            <td class="text-end"><?= money($it['price']) ?></td>
            <td><input type="number" name="qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>" min="0" max="<?= (int)$it['quantity'] ?>" class="form-control form-control-sm"></td>
            <td class="text-end"><?= money($it['subtotal']) ?></td>
            <td class="text-end">
              <button type="submit" form="rm-<?= (int)$it['id'] ?>" class="btn btn-outline-danger btn-sm">Xóa</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="fw-bold"><td colspan="3" class="text-end">Tổng cộng:</td><td class="text-end text-danger fs-5"><?= money($total) ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>
    <div class="mt-2 d-flex justify-content-between">
      <a href="shop.php" class="btn btn-outline-secondary btn-sm">← Tiếp tục mua</a>
      <button class="btn btn-secondary btn-sm">Cập nhật giỏ hàng</button>
    </div>
  </form>

  <?php foreach ($items as $it): ?>
    <form id="rm-<?= (int)$it['id'] ?>" method="post">
      <input type="hidden" name="action" value="remove">
      <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
    </form>
  <?php endforeach; ?>

  <div class="card shadow-sm">
    <div class="card-header fw-bold">Thông tin đặt hàng (demo)</div>
    <form method="post" novalidate>
      <input type="hidden" name="action" value="checkout">
      <div class="card-body row g-3">
        <div class="col-md-6">
          <label class="form-label">Họ và tên</label>
          <input type="text" name="fullname" class="form-control<?= isset($errors['fullname']) ? ' is-invalid' : '' ?>" value="<?= e($customer['fullname']) ?>">
          <?php if (isset($errors['fullname'])): ?><div class="invalid-feedback"><?= e($errors['fullname']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Số điện thoại</label>
          <input type="text" name="phone" class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>" value="<?= e($customer['phone']) ?>">
          <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
        </div>
        <div class="col-12">
          <label class="form-label">Địa chỉ giao hàng</label>
          <input type="text" name="address" class="form-control<?= isset($errors['address']) ? ' is-invalid' : '' ?>" value="<?= e($customer['address']) ?>">
          <?php if (isset($errors['address'])): ?><div class="invalid-feedback"><?= e($errors['address']) ?></div><?php endif; ?>
        </div>
      </div>
      <div class="card-footer text-end">
        <button class="btn btn-success">Đặt hàng · <?= money($total) ?></button>
      </div>
    </form>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

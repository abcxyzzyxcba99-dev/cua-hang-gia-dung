<?php
require __DIR__ . '/includes/db.php';
$user = require_login('buyer');          // chỉ người mua
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];   // [product_id => qty]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
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

    // Đổi số lượng 1 sản phẩm (gọi bằng JavaScript, trả về JSON để cập nhật tổng tiền)
    if ($action === 'set_qty') {
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_POST['id'] ?? 0);
        $p  = fetch_product($pdo, $id);
        if (!$p || !isset($_SESSION['cart'][$id])) {
            echo json_encode(['ok' => false, 'message' => 'Sản phẩm không còn trong giỏ hàng.']);
            exit;
        }
        $qty = parse_qty($_POST['qty'] ?? '');
        if ($qty === null) {
            echo json_encode(['ok' => false, 'qty' => (int)$_SESSION['cart'][$id],
                              'message' => 'Số lượng phải là số nguyên lớn hơn 0.']);
            exit;
        }
        $stock = (int)$p['quantity'];
        $msg   = '';
        if ($qty > $stock) { $qty = $stock; $msg = 'Chỉ còn ' . $stock . ' sản phẩm trong kho.'; }
        $_SESSION['cart'][$id] = $qty;

        list($tmpItems, $tmpTotal) = load_cart($pdo);
        echo json_encode([
            'ok'       => true,
            'qty'      => $qty,
            'subtotal' => money($qty * (float)$p['price']),
            'total'    => money($tmpTotal),
            'count'    => array_sum($_SESSION['cart']),
            'message'  => $msg,
        ]);
        exit;
    }

    // Cập nhật số lượng (nút "Cập nhật giỏ hàng" - dùng khi tắt JavaScript)
    if ($action === 'update') {
        $msgs = [];
        foreach (($_POST['qty'] ?? []) as $id => $raw) {
            $id = (int)$id;
            $p  = fetch_product($pdo, $id);
            if (!$p) { unset($_SESSION['cart'][$id]); continue; }
            $qty = parse_qty($raw);
            if ($qty === null) {
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
        set_flash('Đã xóa sản phẩm khỏi giỏ hàng.');
        header('Location: cart.php'); exit;
    }
}

list($items, $total) = load_cart($pdo);

$flash = get_flash();
$pageTitle = 'Giỏ hàng';
require __DIR__ . '/includes/header.php';
?>

<h4 class="mb-3">Giỏ hàng</h4>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div id="cart-notice"></div>

<?php if (!$items): ?>
  <div class="card card-body text-center text-muted py-5">
    Giỏ hàng trống. <a href="shop.php">Tiếp tục mua sắm</a>
  </div>
<?php else: ?>
  <form method="post" class="mb-4">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update">
    <div class="table-responsive shadow-sm">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-dark">
          <tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th style="width:110px">Số lượng</th><th class="text-end">Thành tiền</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr id="row-<?= (int)$it['id'] ?>">
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($it['image']): ?>
                  <img src="<?= e(image_src($it['image'])) ?>" alt="" class="rounded border" style="width:56px;height:42px;object-fit:cover;">
                <?php endif; ?>
                <div><?= e($it['name']) ?><br><small class="text-muted"><?= e($it['product_code']) ?> · tồn <?= (int)$it['quantity'] ?></small></div>
              </div>
            </td>
            <td class="text-end"><?= money($it['price']) ?></td>
            <td><input type="number" name="qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>" min="0" max="<?= (int)$it['quantity'] ?>"
                       class="form-control form-control-sm qty-input"
                       data-id="<?= (int)$it['id'] ?>" data-price="<?= (float)$it['price'] ?>"
                       data-stock="<?= (int)$it['quantity'] ?>" data-name="<?= e($it['name']) ?>"
                       data-last="<?= (int)$it['qty'] ?>"></td>
            <td class="text-end" id="sub-<?= (int)$it['id'] ?>"><?= money($it['subtotal']) ?></td>
            <td class="text-end">
              <button type="submit" form="rm-<?= (int)$it['id'] ?>" class="btn btn-outline-danger btn-sm">Xóa</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="fw-bold"><td colspan="3" class="text-end">Tổng cộng:</td><td class="text-end text-danger fs-5" id="cart-total"><?= money($total) ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>
    <div class="mt-2 d-flex justify-content-between">
      <a href="shop.php" class="btn btn-outline-secondary btn-sm">← Tiếp tục mua</a>
      <div class="d-flex gap-2">
        <button class="btn btn-secondary btn-sm">Cập nhật giỏ hàng</button>
        <a href="checkout.php" id="go-checkout" class="btn btn-success btn-sm">Tiến hành thanh toán →</a>
      </div>
    </div>
  </form>

  <?php foreach ($items as $it): ?>
    <form id="rm-<?= (int)$it['id'] ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="remove">
      <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
    </form>
  <?php endforeach; ?>
<?php endif; ?>

<?php if ($items): ?>
<script>
(function () {
  const CSRF     = '<?= e(csrf_token()) ?>';
  const inputs   = document.querySelectorAll('.qty-input');
  const totalEl  = document.getElementById('cart-total');
  const badge    = document.querySelector('.cart-count');
  const notice   = document.getElementById('cart-notice');
  const pending  = new Set();
  let noticeTimer;

  const fmt = n => new Intl.NumberFormat('vi-VN').format(n) + ' ₫';
  const isInt = v => /^\d+$/.test(String(v).trim());

  function notify(msg, type) {
    if (!msg) return;
    notice.innerHTML = '<div class="alert alert-' + type + '"></div>';
    notice.firstChild.textContent = msg;
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => notice.innerHTML = '', 4500);
  }

  function effectiveQty(inp) {
    return isInt(inp.value) ? parseInt(inp.value, 10) : parseInt(inp.dataset.last, 10);
  }

  // Tính lại thành tiền từng dòng + tổng cộng ngay lập tức
  function recalc() {
    let total = 0;
    inputs.forEach(inp => {
      const sub = effectiveQty(inp) * parseFloat(inp.dataset.price);
      total += sub;
      document.getElementById('sub-' + inp.dataset.id).textContent = fmt(sub);
    });
    totalEl.textContent = fmt(total);
  }

  // Lưu số lượng lên server (session)
  function save(inp) {
    clearTimeout(inp._t); inp._t = null;
    const body = new URLSearchParams({ action: 'set_qty', csrf: CSRF, id: inp.dataset.id, qty: inp.value });
    const req = fetch('cart.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.ok) {
          inp.value = d.qty; inp.dataset.last = d.qty;
          document.getElementById('sub-' + inp.dataset.id).textContent = d.subtotal;
          totalEl.textContent = d.total;
          if (badge) badge.textContent = d.count;
          notify(d.message, 'warning');
        } else {
          if (d.qty) { inp.value = d.qty; inp.dataset.last = d.qty; }
          recalc();
          notify(d.message, 'danger');
        }
      })
      .catch(() => notify('Không thể cập nhật giỏ hàng, vui lòng tải lại trang.', 'danger'))
      .finally(() => pending.delete(req));
    pending.add(req);
  }

  inputs.forEach(inp => {
    inp.addEventListener('input', () => {
      if (isInt(inp.value)) {
        let v = parseInt(inp.value, 10);
        const stock = parseInt(inp.dataset.stock, 10);
        if (v > stock) { v = stock; inp.value = v; notify('Chỉ còn ' + stock + ' sản phẩm trong kho.', 'warning'); }
        if (v > 0) {
          inp.dataset.last = v;
          clearTimeout(inp._t);
          inp._t = setTimeout(() => save(inp), 300);
        }
      }
      recalc();
    });

    inp.addEventListener('change', () => {
      clearTimeout(inp._t);
      const raw = inp.value.trim();

      if (isInt(raw) && parseInt(raw, 10) === 0) {
        // Giảm về 0 -> hỏi xác nhận xóa khỏi giỏ hàng
        if (confirm('Bạn có muốn xóa "' + inp.dataset.name + '" khỏi giỏ hàng không?')) {
          document.getElementById('rm-' + inp.dataset.id).submit();
        } else {
          inp.value = inp.dataset.last;
          recalc();
        }
        return;
      }
      if (!isInt(raw)) {
        notify('Số lượng của "' + inp.dataset.name + '" phải là số nguyên lớn hơn 0.', 'danger');
        inp.value = inp.dataset.last;
        recalc();
        return;
      }
      save(inp);
    });
  });

  // Bấm "Tiến hành thanh toán": đợi các thay đổi số lượng được lưu xong rồi mới chuyển trang
  const go = document.getElementById('go-checkout');
  if (go) {
    go.addEventListener('click', async e => {
      let waiting = pending.size > 0;
      inputs.forEach(inp => { if (inp._t) { save(inp); waiting = true; } });
      if (waiting) {
        e.preventDefault();
        await Promise.allSettled([...pending]);
        location.href = go.href;
      }
    });
  }
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

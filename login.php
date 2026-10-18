<?php
require __DIR__ . '/includes/db.php';

// Lần đầu chạy (chưa có tài khoản nào): tự tạo 2 tài khoản demo
if ((int)$pdo->query('SELECT COUNT(*) FROM tblUsers')->fetchColumn() === 0) {
    $ins = $pdo->prepare('INSERT INTO tblUsers (username, password_hash, fullname, role) VALUES (:u, :p, :f, :r)');
    $ins->execute([':u' => 'seller', ':p' => password_hash('seller123', PASSWORD_DEFAULT), ':f' => 'Chủ cửa hàng', ':r' => 'seller']);
    $ins->execute([':u' => 'buyer',  ':p' => password_hash('buyer123',  PASSWORD_DEFAULT), ':f' => 'Khách hàng demo', ':r' => 'buyer']);
}

if (current_user()) { header('Location: ' . home_for(current_user())); exit; }

$allowed  = ['shop.php', 'cart.php', 'checkout.php', 'orders.php', 'index.php'];
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
$username = '';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM tblUsers WHERE username = :u');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$row['id'], 'username' => $row['username'],
                'fullname' => $row['fullname'], 'role' => $row['role'],
            ];
            $_SESSION['cart'] = [];
            set_flash('Đăng nhập thành công. Xin chào ' . $row['fullname'] . '!');

            // Chỉ cho chuyển tới các trang hợp lệ, đúng quyền
            $dest = home_for($_SESSION['user']);
            if (in_array($redirect, $allowed, true)) {
                $sellerOnly = ['index.php'];
                $buyerOnly  = ['cart.php', 'checkout.php'];
                if (!(($row['role'] === 'buyer' && in_array($redirect, $sellerOnly, true)) ||
                      ($row['role'] === 'seller' && in_array($redirect, $buyerOnly, true)))) {
                    $dest = $redirect;
                }
            }
            header('Location: ' . $dest);
            exit;
        }
    }
}

$flash = get_flash();
$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
    <div class="card shadow-sm">
      <div class="card-header fw-bold">Đăng nhập</div>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
        <div class="card-body">
          <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Tên đăng nhập</label>
            <input type="text" name="username" class="form-control" value="<?= e($username) ?>" autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control">
          </div>
          <button class="btn btn-primary w-100">Đăng nhập</button>
          <div class="text-center mt-3 small">Chưa có tài khoản? <a href="register.php">Đăng ký mua hàng</a></div>
        </div>
      </form>
      <div class="card-footer small text-muted">
        Tài khoản demo — Người bán: <code>seller</code> / <code>seller123</code> ·
        Người mua: <code>buyer</code> / <code>buyer123</code>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

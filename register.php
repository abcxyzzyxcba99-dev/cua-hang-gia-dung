<?php
require __DIR__ . '/includes/db.php';

if (current_user()) { header('Location: ' . home_for(current_user())); exit; }

$data   = ['username' => '', 'fullname' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data['username'] = trim($_POST['username'] ?? '');
    $data['fullname'] = trim($_POST['fullname'] ?? '');
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $data['username'])) {
        $errors['username'] = 'Tên đăng nhập 3-30 ký tự, chỉ gồm chữ cái, số và dấu gạch dưới.';
    }
    if (mb_strlen($data['fullname']) < 3 || mb_strlen($data['fullname']) > 100) {
        $errors['fullname'] = 'Họ tên phải từ 3 đến 100 ký tự.';
    }
    if (strlen($password) < 6 || strlen($password) > 72) {
        $errors['password'] = 'Mật khẩu phải từ 6 đến 72 ký tự.';
    }
    if ($password !== $password2) {
        $errors['password2'] = 'Mật khẩu nhập lại không khớp.';
    }
    if (!isset($errors['username'])) {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM tblUsers WHERE username = :u');
        $chk->execute([':u' => $data['username']]);
        if ((int)$chk->fetchColumn() > 0) $errors['username'] = 'Tên đăng nhập đã tồn tại.';
    }

    if (!$errors) {
        try {
            // Đăng ký công khai chỉ tạo tài khoản NGƯỜI MUA
            $ins = $pdo->prepare('INSERT INTO tblUsers (username, password_hash, fullname, role) VALUES (:u, :p, :f, \'buyer\')');
            $ins->execute([
                ':u' => $data['username'],
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':f' => $data['fullname'],
            ]);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$pdo->lastInsertId(), 'username' => $data['username'],
                'fullname' => $data['fullname'], 'role' => 'buyer',
            ];
            $_SESSION['cart'] = [];
            set_flash('Đăng ký thành công. Chào mừng ' . $data['fullname'] . '!');
            header('Location: shop.php');
            exit;
        } catch (PDOException $ex) {
            $errors['username'] = 'Không tạo được tài khoản (tên đăng nhập có thể đã tồn tại).';
        }
    }
}

function bad($errors, $k) { return isset($errors[$k]) ? ' is-invalid' : ''; }
function msg($errors, $k)  { return isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : ''; }

$pageTitle = 'Đăng ký';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header fw-bold">Đăng ký tài khoản người mua</div>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Tên đăng nhập</label>
            <input type="text" name="username" class="form-control<?= bad($errors,'username') ?>" value="<?= e($data['username']) ?>">
            <?= msg($errors,'username') ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Họ và tên</label>
            <input type="text" name="fullname" class="form-control<?= bad($errors,'fullname') ?>" value="<?= e($data['fullname']) ?>">
            <?= msg($errors,'fullname') ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control<?= bad($errors,'password') ?>">
            <?= msg($errors,'password') ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Nhập lại mật khẩu</label>
            <input type="password" name="password2" class="form-control<?= bad($errors,'password2') ?>">
            <?= msg($errors,'password2') ?>
          </div>
          <button class="btn btn-success w-100">Đăng ký</button>
          <div class="text-center mt-3 small">Đã có tài khoản? <a href="login.php">Đăng nhập</a></div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

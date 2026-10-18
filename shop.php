<?php
require __DIR__ . '/includes/db.php';

$q_name = trim($_GET['name'] ?? '');
$q_cat  = trim($_GET['category'] ?? '');

// Chỉ hiển thị sản phẩm đang bán
$where  = ['status = 1'];
$params = [];
if ($q_name !== '') { $where[] = 'name LIKE :name'; $params[':name'] = "%$q_name%"; }
if ($q_cat  !== '') { $where[] = 'category = :cat'; $params[':cat']  = $q_cat; }

$stmt = $pdo->prepare('SELECT * FROM tblProducts WHERE ' . implode(' AND ', $where) . ' ORDER BY id ASC');
$stmt->execute($params);
$products = $stmt->fetchAll();

$icons = ['Điện tử' => '📺', 'Gia dụng' => '🧺', 'Điện lạnh' => '❄️', 'Nhà bếp' => '🍳'];

$flash = get_flash();
$pageTitle = 'Cửa hàng';
require __DIR__ . '/includes/header.php';
?>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
    <?= e($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form method="get" class="row g-2 mb-4">
  <div class="col-md-5">
    <input type="text" name="name" class="form-control" placeholder="Tìm sản phẩm..." value="<?= e($q_name) ?>">
  </div>
  <div class="col-md-3">
    <select name="category" class="form-select">
      <option value="">-- Tất cả danh mục --</option>
      <?php foreach ($CATEGORIES as $c): ?>
        <option value="<?= e($c) ?>" <?= $q_cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4 d-flex gap-2">
    <button class="btn btn-success px-4">Tìm</button>
    <a href="shop.php" class="btn btn-outline-secondary">Xóa lọc</a>
  </div>
</form>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
<?php if (!$products): ?>
  <div class="col-12 text-center text-muted py-5">Không có sản phẩm phù hợp.</div>
<?php endif; ?>
<?php foreach ($products as $p): ?>
  <div class="col">
    <div class="card h-100 shadow-sm">
      <?php if ($p['image']): ?>
        <img src="<?= e(image_src($p['image'])) ?>" alt="<?= e($p['name']) ?>" class="card-img-top border-bottom" style="height:180px;object-fit:cover;">
      <?php else: ?>
        <div class="bg-white text-center py-4 border-bottom" style="font-size:64px;height:180px;">
          <?= $icons[$p['category']] ?? '📦' ?>
        </div>
      <?php endif; ?>
      <div class="card-body d-flex flex-column">
        <span class="badge text-bg-light border align-self-start mb-2"><?= e($p['category']) ?></span>
        <h6 class="card-title"><?= e($p['name']) ?></h6>
        <small class="text-muted mb-1"><?= e($p['brand']) ?></small>
        <small class="text-muted mb-2"><?= e($p['description']) ?></small>
        <div class="mt-auto">
          <div class="fw-bold text-danger fs-5"><?= money($p['price']) ?></div>
          <small class="text-muted">Còn <?= (int)$p['quantity'] ?> sản phẩm</small>

          <?php if (is_seller()): ?>
            <a href="edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-warning btn-sm w-100 mt-2">Sửa sản phẩm</a>
          <?php elseif ((int)$p['quantity'] <= 0): ?>
            <button class="btn btn-secondary btn-sm w-100 mt-2" disabled>Hết hàng</button>
          <?php elseif (is_buyer()): ?>
            <form method="post" action="cart.php" class="mt-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <div class="input-group input-group-sm">
                <input type="number" name="qty" value="1" min="1" max="<?= (int)$p['quantity'] ?>"
                       class="form-control" style="max-width:80px" aria-label="Số lượng">
                <button class="btn btn-primary">🛒 Thêm vào giỏ</button>
              </div>
            </form>
          <?php else: ?>
            <a href="login.php?redirect=shop.php" class="btn btn-outline-primary btn-sm w-100 mt-2">Đăng nhập để mua</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

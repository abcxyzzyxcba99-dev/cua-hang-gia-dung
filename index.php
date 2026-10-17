<?php
require __DIR__ . '/includes/db.php';

$q_name   = trim($_GET['name'] ?? '');
$q_code   = trim($_GET['product_code'] ?? '');
$q_cat    = trim($_GET['category'] ?? '');
$q_status = $_GET['status'] ?? '';

$where  = [];
$params = [];

if ($q_name !== '') { $where[] = 'name LIKE :name';          $params[':name'] = "%$q_name%"; }
if ($q_code !== '') { $where[] = 'product_code LIKE :code';  $params[':code']  = "%$q_code%"; }
if ($q_cat  !== '') { $where[] = 'category = :cat';          $params[':cat']   = $q_cat; }
if ($q_status === '0' || $q_status === '1') {
    $where[] = 'status = :status';
    $params[':status'] = (int)$q_status;
}

$sql = 'SELECT * FROM tblProducts';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$flash = get_flash();
$pageTitle = 'Quản lý sản phẩm';
require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h4 class="mb-0">Quản lý sản phẩm</h4>
    <small class="text-muted">Cửa hàng gia dụng &amp; điện tử - CRUD PHP + MySQL</small>
  </div>
  <a href="create.php" class="btn btn-primary btn-sm">+ Thêm sản phẩm</a>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
    <?= e($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small">Từ khóa (tên sản phẩm)</label>
      <input type="text" name="name" class="form-control form-control-sm" placeholder="VD: Tivi" value="<?= e($q_name) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">Mã sản phẩm</label>
      <input type="text" name="product_code" class="form-control form-control-sm" placeholder="VD: SP001" value="<?= e($q_code) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">Danh mục</label>
      <select name="category" class="form-select form-select-sm">
        <option value="">-- Tất cả --</option>
        <?php foreach ($CATEGORIES as $c): ?>
          <option value="<?= e($c) ?>" <?= $q_cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Trạng thái</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">-- Tất cả --</option>
        <option value="1" <?= $q_status === '1' ? 'selected' : '' ?>>Đang bán</option>
        <option value="0" <?= $q_status === '0' ? 'selected' : '' ?>>Ngừng bán</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button type="submit" class="btn btn-success btn-sm px-4">Tìm</button>
      <a href="index.php" class="btn btn-outline-secondary btn-sm px-4">Xóa</a>
    </div>
  </div>
</form>

<div class="table-responsive shadow-sm">
  <table class="table table-striped table-hover align-middle mb-0 small">
    <thead class="table-dark">
      <tr>
        <th>#</th><th>Ảnh</th><th>Mã SP</th><th>Tên sản phẩm</th><th>Danh mục</th><th>Thương hiệu</th>
        <th class="text-end">Giá bán</th><th class="text-center">SL</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="11" class="text-center text-muted py-3">Không có dữ liệu.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $i => $r): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td>
          <?php if ($r['image']): ?>
            <img src="<?= e(image_src($r['image'])) ?>" alt="" class="rounded border" style="width:64px;height:48px;object-fit:cover;">
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <td><?= e($r['product_code']) ?></td>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['category']) ?></td>
        <td><?= e($r['brand']) ?></td>
        <td class="text-end"><?= money($r['price']) ?></td>
        <td class="text-center"><?= (int)$r['quantity'] ?></td>
        <td>
          <?php if ((int)$r['status'] === 1): ?>
            <span class="badge bg-success">Đang bán</span>
          <?php else: ?>
            <span class="badge bg-secondary">Ngừng bán</span>
          <?php endif; ?>
        </td>
        <td><?= e(substr($r['created_at'], 0, 10)) ?></td>
        <td>
          <a href="edit.php?id=<?= (int)$r['id'] ?>" class="btn btn-warning btn-sm py-0">Sửa</a>
          <a href="delete.php?id=<?= (int)$r['id'] ?>" class="btn btn-danger btn-sm py-0">Xóa</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

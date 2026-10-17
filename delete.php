<?php
require __DIR__ . '/includes/db.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM tblProducts WHERE id = :id');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    set_flash('Không tìm thấy sản phẩm.', 'danger');
    header('Location: index.php');
    exit;
}

// Bấm nút "Xóa" xác nhận -> POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $del = $pdo->prepare('DELETE FROM tblProducts WHERE id = :id');
    $del->execute([':id' => $id]);
    delete_image($row['image']);
    set_flash('Xóa thành công');
    header('Location: index.php');
    exit;
}

$pageTitle = 'Xác nhận xóa sản phẩm';
require __DIR__ . '/includes/header.php';
?>
<div class="card border-danger shadow-sm">
  <div class="card-header bg-danger text-white">Xác nhận xóa sản phẩm</div>
  <div class="card-body">
    <?php if ($row['image']): ?>
      <img src="<?= e(image_src($row['image'])) ?>" alt="" class="rounded border mb-3" style="width:120px;height:90px;object-fit:cover;">
    <?php endif; ?>
    <p class="mb-1">Bạn đang chuẩn bị xóa sản phẩm:
      <strong><?= e($row['product_code']) ?> - <?= e($row['name']) ?>
      (<?= e($row['category']) ?>, <?= money($row['price']) ?>)</strong>
    </p>
    <p class="text-muted mb-0">Hành động này không thể hoàn tác.</p>
  </div>
  <form method="post" class="card-footer text-end">
    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
    <a href="index.php" class="btn btn-outline-secondary">Hủy</a>
    <button type="submit" class="btn btn-danger">Xóa</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

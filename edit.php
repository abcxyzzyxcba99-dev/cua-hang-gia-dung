<?php
require __DIR__ . '/includes/db.php';
require_login('seller');   // chỉ người bán

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM tblProducts WHERE id = :id');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    set_flash('Không tìm thấy sản phẩm.', 'danger');
    header('Location: index.php');
    exit;
}

$data   = $row;          // đổ dữ liệu theo ID vào form
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = read_post();
    $data['image'] = $row['image'];     // giữ ảnh cũ nếu không chọn ảnh mới
    $errors = validate_product($data);

    $newImage = null;
    if (!$errors) $newImage = handle_upload($errors);

    if (!$errors) {
        $imageToSave = $newImage ?? $row['image'];
        $stmt = $pdo->prepare(
            'UPDATE tblProducts SET product_code=:product_code, name=:name, category=:category,
                    brand=:brand, price=:price, quantity=:quantity, description=:description,
                    image=:image, status=:status
             WHERE id=:id'
        );
        $stmt->execute([
            ':product_code' => $data['product_code'],
            ':name'         => $data['name'],
            ':category'     => $data['category'],
            ':brand'        => $data['brand'],
            ':price'        => $data['price'],
            ':quantity'     => (int)$data['quantity'],
            ':description'  => $data['description'],
            ':image'        => $imageToSave,
            ':status'       => (int)$data['status'],
            ':id'           => $id,
        ]);
        if ($newImage !== null) delete_image($row['image']);   // xóa ảnh cũ sau khi cập nhật thành công
        set_flash('Cập nhật thành công');
        header('Location: index.php');
        exit;
    }
}

$pageTitle   = 'Sửa sản phẩm';
$submitLabel = 'Cập nhật';
$submitClass = 'btn-warning';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Sửa sản phẩm</h4>
  <a href="index.php" class="btn btn-outline-secondary btn-sm">← Quay lại</a>
</div>
<?php require __DIR__ . '/includes/form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

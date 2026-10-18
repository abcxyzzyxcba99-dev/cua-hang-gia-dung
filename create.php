<?php
require __DIR__ . '/includes/db.php';
require_login('seller');   // chỉ người bán

$data = [
    'product_code' => '', 'name' => '', 'category' => '', 'brand' => '',
    'price' => '', 'quantity' => '', 'description' => '', 'status' => '1', 'image' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = read_post() + ['image' => ''];
    $errors = validate_product($data);

    $newImage = null;
    if (!$errors) $newImage = handle_upload($errors);   // chỉ upload khi dữ liệu hợp lệ

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO tblProducts (product_code, name, category, brand, price, quantity, description, image, status)
             VALUES (:product_code, :name, :category, :brand, :price, :quantity, :description, :image, :status)'
        );
        $stmt->execute([
            ':product_code' => $data['product_code'],
            ':name'         => $data['name'],
            ':category'     => $data['category'],
            ':brand'        => $data['brand'],
            ':price'        => $data['price'],
            ':quantity'     => (int)$data['quantity'],
            ':description'  => $data['description'],
            ':image'        => $newImage,
            ':status'       => (int)$data['status'],
        ]);
        set_flash('Thêm mới thành công');
        header('Location: index.php');
        exit;
    }
}

$pageTitle   = 'Thêm sản phẩm';
$submitLabel = 'Lưu';
$submitClass = 'btn-primary';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Thêm sản phẩm</h4>
  <a href="index.php" class="btn btn-outline-secondary btn-sm">← Quay lại</a>
</div>
<?php require __DIR__ . '/includes/form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
// Form dùng chung. Cần biến: $data, $errors, $submitLabel, $submitClass, $CATEGORIES
function field_err($errors, $k) {
    return isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
}
function is_bad($errors, $k) { return isset($errors[$k]) ? ' is-invalid' : ''; }
?>
<form method="post" enctype="multipart/form-data" novalidate>
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Mã sản phẩm</label>
          <input type="text" name="product_code" class="form-control<?= is_bad($errors,'product_code') ?>"
                 placeholder="VD: SP009" value="<?= e($data['product_code']) ?>">
          <?= field_err($errors,'product_code') ?>
        </div>
        <div class="col-md-8">
          <label class="form-label">Tên sản phẩm</label>
          <input type="text" name="name" class="form-control<?= is_bad($errors,'name') ?>"
                 placeholder="VD: Tivi Samsung 43 inch 4K" value="<?= e($data['name']) ?>">
          <?= field_err($errors,'name') ?>
        </div>

        <div class="col-md-4">
          <label class="form-label">Danh mục</label>
          <select name="category" class="form-select<?= is_bad($errors,'category') ?>">
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($CATEGORIES as $c): ?>
              <option value="<?= e($c) ?>" <?= $data['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
          <?= field_err($errors,'category') ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Thương hiệu</label>
          <input type="text" name="brand" class="form-control<?= is_bad($errors,'brand') ?>"
                 placeholder="VD: Samsung" value="<?= e($data['brand']) ?>">
          <?= field_err($errors,'brand') ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Trạng thái</label>
          <select name="status" class="form-select<?= is_bad($errors,'status') ?>">
            <option value="1" <?= (string)$data['status'] === '1' ? 'selected' : '' ?>>Đang bán</option>
            <option value="0" <?= (string)$data['status'] === '0' ? 'selected' : '' ?>>Ngừng bán</option>
          </select>
          <?= field_err($errors,'status') ?>
        </div>

        <div class="col-md-6">
          <label class="form-label">Giá bán (VNĐ)</label>
          <input type="number" name="price" min="0" step="1" class="form-control<?= is_bad($errors,'price') ?>"
                 placeholder="VD: 8990000" value="<?= $data['price'] !== '' ? e((int)$data['price'] ?: $data['price']) : '' ?>">
          <?= field_err($errors,'price') ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Số lượng tồn</label>
          <input type="number" name="quantity" min="0" step="1" class="form-control<?= is_bad($errors,'quantity') ?>"
                 placeholder="VD: 15" value="<?= e($data['quantity']) ?>">
          <?= field_err($errors,'quantity') ?>
        </div>

        <div class="col-12">
          <label class="form-label">Ảnh sản phẩm</label>
          <div class="d-flex align-items-center gap-3">
            <?php if (!empty($data['image'])): ?>
              <img src="<?= e(image_src($data['image'])) ?>" alt="" class="rounded border" style="width:96px;height:72px;object-fit:cover;">
            <?php endif; ?>
            <div class="flex-grow-1">
              <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
                     class="form-control<?= is_bad($errors,'image') ?>">
              <div class="form-text">JPG, PNG, WEBP hoặc GIF, tối đa 2MB.<?= !empty($data['image']) ? ' Chọn ảnh mới để thay ảnh hiện tại.' : '' ?></div>
              <?= field_err($errors,'image') ?>
            </div>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Mô tả</label>
          <textarea name="description" rows="3" class="form-control<?= is_bad($errors,'description') ?>"
                    placeholder="VD: Bảo hành 24 tháng..."><?= e($data['description']) ?></textarea>
          <?= field_err($errors,'description') ?>
        </div>
      </div>
    </div>
    <div class="card-footer text-end">
      <a href="index.php" class="btn btn-outline-secondary">Hủy</a>
      <button type="submit" class="btn <?= e($submitClass) ?>"><?= e($submitLabel) ?></button>
    </div>
  </div>
</form>

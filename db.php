<?php
session_start();

$host = 'localhost';
$db   = 'SHOP_DB';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Lỗi kết nối CSDL: ' . htmlspecialchars($e->getMessage()));
}

// Danh mục sản phẩm
$CATEGORIES = ['Điện tử', 'Gia dụng', 'Điện lạnh', 'Nhà bếp'];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function money($n) { return number_format((float)$n, 0, ',', '.') . ' ₫'; }

function set_flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function read_post(): array {
    return [
        'product_code' => trim($_POST['product_code'] ?? ''),
        'name'         => trim($_POST['name'] ?? ''),
        'category'     => trim($_POST['category'] ?? ''),
        'brand'        => trim($_POST['brand'] ?? ''),
        'price'        => trim($_POST['price'] ?? ''),
        'quantity'     => trim($_POST['quantity'] ?? ''),
        'description'  => trim($_POST['description'] ?? ''),
        'status'       => (string)($_POST['status'] ?? ''),
    ];
}

// Validate dùng chung cho create.php và edit.php
function validate_product(array $d): array {
    global $CATEGORIES;
    $errors = [];

    if ($d['product_code'] === '') {
        $errors['product_code'] = 'Mã sản phẩm không được để trống.';
    } elseif (mb_strlen($d['product_code']) > 50) {
        $errors['product_code'] = 'Mã sản phẩm tối đa 50 ký tự.';
    }

    if ($d['name'] === '') {
        $errors['name'] = 'Tên sản phẩm không được để trống.';
    } elseif (mb_strlen($d['name']) < 3) {
        $errors['name'] = 'Tên sản phẩm phải có ít nhất 3 ký tự.';
    } elseif (mb_strlen($d['name']) > 200) {
        $errors['name'] = 'Tên sản phẩm tối đa 200 ký tự.';
    }

    if (!in_array($d['category'], $CATEGORIES, true)) {
        $errors['category'] = 'Vui lòng chọn danh mục hợp lệ.';
    }

    if ($d['price'] === '') {
        $errors['price'] = 'Giá bán không được để trống.';
    } elseif (!is_numeric($d['price']) || (float)$d['price'] <= 0) {
        $errors['price'] = 'Giá bán phải là số lớn hơn 0.';
    }

    if ($d['quantity'] === '') {
        $errors['quantity'] = 'Số lượng không được để trống.';
    } elseif (!ctype_digit($d['quantity'])) {
        $errors['quantity'] = 'Số lượng phải là số nguyên >= 0.';
    }

    if (!in_array($d['status'], ['0', '1'], true)) {
        $errors['status'] = 'Trạng thái chỉ nhận 0 hoặc 1.';
    }

    if (mb_strlen($d['brand']) > 100)       $errors['brand']       = 'Thương hiệu tối đa 100 ký tự.';
    if (mb_strlen($d['description']) > 255) $errors['description'] = 'Mô tả tối đa 255 ký tự.';

    return $errors;
}

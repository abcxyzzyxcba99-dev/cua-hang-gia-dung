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

// ================= XỬ LÝ ẢNH SẢN PHẨM =================
const UPLOAD_DIR = __DIR__ . '/../uploads/';

// Đường dẫn ảnh để hiển thị (null nếu chưa có ảnh)
function image_src($name) {
    if (!$name) return null;
    return 'uploads/' . rawurlencode($name);
}

// Xóa file ảnh trong thư mục uploads (an toàn: chỉ lấy tên file)
function delete_image($name) {
    if (!$name) return;
    $file = UPLOAD_DIR . basename($name);
    if (is_file($file)) @unlink($file);
}

// Xử lý upload. Trả về tên file mới, hoặc null nếu không chọn ảnh / có lỗi (lỗi ghi vào $errors['image'])
function handle_upload(array &$errors) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) return null;

    $f = $_FILES['image'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $errors['image'] = 'Upload ảnh thất bại (mã lỗi ' . $f['error'] . ').';
        return null;
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        $errors['image'] = 'Ảnh tối đa 2MB.';
        return null;
    }
    $info = @getimagesize($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!$info || !isset($allowed[$info['mime']])) {
        $errors['image'] = 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.';
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) {
        $errors['image'] = 'Không lưu được ảnh vào thư mục uploads/.';
        return null;
    }
    return $name;
}

// ================= TÀI KHOẢN & PHÂN QUYỀN =================
function current_user() { return $_SESSION['user'] ?? null; }
function is_seller()    { return (current_user()['role'] ?? '') === 'seller'; }
function is_buyer()     { return (current_user()['role'] ?? '') === 'buyer'; }
function home_for(array $u): string { return $u['role'] === 'seller' ? 'index.php' : 'shop.php'; }

// Yêu cầu đăng nhập (và đúng vai trò nếu truyền $role). Sai thì chuyển hướng.
function require_login(?string $role = null): array {
    $u = current_user();
    if (!$u) {
        set_flash('Vui lòng đăng nhập để tiếp tục.', 'warning');
        header('Location: login.php?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
        exit;
    }
    if ($role !== null && $u['role'] !== $role) {
        set_flash('Bạn không có quyền truy cập trang này.', 'danger');
        header('Location: ' . home_for($u));
        exit;
    }
    return $u;
}

// ================= CSRF =================
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        die('Phiên làm việc không hợp lệ (CSRF). Hãy tải lại trang và thử lại.');
    }
}

// ================= GIỎ HÀNG & ĐƠN HÀNG =================
function fetch_product(PDO $pdo, int $id) {
    $s = $pdo->prepare('SELECT * FROM tblProducts WHERE id = :id AND status = 1');
    $s->execute([':id' => $id]);
    return $s->fetch();
}

// Trả về số lượng (int) nếu hợp lệ: số nguyên > 0, ngược lại trả về null
function parse_qty($raw) {
    $raw = trim((string)$raw);
    return (ctype_digit($raw) && (int)$raw > 0) ? (int)$raw : null;
}

// Nạp giỏ hàng từ session: trả về [danh sách sản phẩm kèm qty/subtotal, tổng tiền]
function load_cart(PDO $pdo): array {
    $items = []; $total = 0;
    foreach (($_SESSION['cart'] ?? []) as $id => $qty) {
        $p = fetch_product($pdo, (int)$id);
        if (!$p || (int)$p['quantity'] <= 0) { unset($_SESSION['cart'][$id]); continue; }
        $qty = min((int)$qty, (int)$p['quantity']);
        if ($qty <= 0) { unset($_SESSION['cart'][$id]); continue; }
        $_SESSION['cart'][$id] = $qty;
        $p['qty']      = $qty;
        $p['subtotal'] = $qty * (float)$p['price'];
        $total        += $p['subtotal'];
        $items[]       = $p;
    }
    return [$items, $total];
}

const PAY_METHODS = [
    'cod'    => 'Thanh toán khi nhận hàng (COD)',
    'bank'   => 'Chuyển khoản ngân hàng',
    'wallet' => 'Ví điện tử (mô phỏng)',
];
const ORDER_STATUS = [
    'new'       => ['Mới đặt',   'primary'],
    'shipping'  => ['Đang giao', 'info'],
    'done'      => ['Hoàn thành','success'],
    'cancelled' => ['Đã hủy',    'secondary'],
];
function order_badge(string $st): string {
    $x = ORDER_STATUS[$st] ?? [$st, 'light'];
    return '<span class="badge text-bg-' . e($x[1]) . '">' . e($x[0]) . '</span>';
}
function pay_badge(string $st): string {
    return $st === 'paid'
        ? '<span class="badge text-bg-success">Đã thanh toán</span>'
        : '<span class="badge text-bg-warning">Chưa thanh toán</span>';
}

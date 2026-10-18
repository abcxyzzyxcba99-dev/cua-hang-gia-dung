-- Dành cho ai ĐÃ import DB cũ: chạy file này để thêm bảng tài khoản và đơn hàng (không mất dữ liệu)
USE SHOP_DB;

-- ===== Tài khoản (người bán / người mua) =====
CREATE TABLE IF NOT EXISTS tblUsers (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(30)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  fullname      VARCHAR(100) NOT NULL,
  role          ENUM('seller','buyer') NOT NULL DEFAULT 'buyer',
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Đơn hàng =====
CREATE TABLE IF NOT EXISTS tblOrders (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT          NOT NULL,
  fullname       VARCHAR(100) NOT NULL,
  phone          VARCHAR(20)  NOT NULL,
  address        VARCHAR(255) NOT NULL,
  payment_method ENUM('cod','bank','wallet') NOT NULL,
  payment_status ENUM('unpaid','paid')       NOT NULL DEFAULT 'unpaid',
  order_status   ENUM('new','shipping','done','cancelled') NOT NULL DEFAULT 'new',
  total          DECIMAL(14,0) NOT NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES tblUsers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Chi tiết đơn hàng (lưu lại tên và giá tại thời điểm mua) =====
CREATE TABLE IF NOT EXISTS tblOrderItems (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  order_id     INT           NOT NULL,
  product_id   INT           NULL,
  product_name VARCHAR(200)  NOT NULL,
  price        DECIMAL(12,0) NOT NULL,
  qty          INT           NOT NULL,
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES tblOrders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tài khoản demo (seller/seller123 và buyer/buyer123) sẽ tự được tạo khi mở trang login.php lần đầu.

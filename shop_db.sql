-- Tạo CSDL
CREATE DATABASE IF NOT EXISTS SHOP_DB
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE SHOP_DB;

-- Tạo bảng sản phẩm
CREATE TABLE IF NOT EXISTS tblProducts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  product_code VARCHAR(50)   NOT NULL,
  name         VARCHAR(200)  NOT NULL,
  category     VARCHAR(100)  NOT NULL,
  brand        VARCHAR(100)  NULL,
  price        DECIMAL(12,0) NOT NULL DEFAULT 0,
  quantity     INT           NOT NULL DEFAULT 0,
  description  VARCHAR(255)  NULL,
  image        VARCHAR(255)  NULL COMMENT 'Tên file ảnh trong thư mục uploads/',
  status       TINYINT(1)    NOT NULL DEFAULT 1 COMMENT '1: Đang bán - 0: Ngừng bán',
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dữ liệu mẫu (8 bản ghi)
INSERT INTO tblProducts (product_code, name, category, brand, price, quantity, description, image, status, created_at) VALUES
('SP001', 'Tivi Samsung 43 inch 4K',         'Điện tử',   'Samsung',    8990000,  15, 'Smart TV, bảo hành 24 tháng',  'sp001.svg', 1, '2025-12-20 09:00:00'),
('SP002', 'Tủ lạnh LG Inverter 335L',        'Điện lạnh', 'LG',        10490000,   8, 'Ngăn đá dưới, tiết kiệm điện', 'sp002.svg', 1, '2025-12-20 10:15:00'),
('SP003', 'Máy giặt Electrolux 9kg',         'Gia dụng',  'Electrolux', 7290000,  12, 'Cửa trước, inverter',          'sp003.svg', 1, '2025-12-21 08:30:00'),
('SP004', 'Nồi chiên không dầu Philips 4L',  'Nhà bếp',   'Philips',    2490000,  30, 'Công nghệ Rapid Air',          'sp004.svg', 1, '2025-12-21 14:00:00'),
('SP005', 'Điều hòa Daikin 1 HP',            'Điện lạnh', 'Daikin',     9190000,   0, 'Hết hàng, chờ nhập',           'sp005.svg', 0, '2025-12-22 11:00:00'),
('SP006', 'Nồi cơm điện Panasonic 1.8L',     'Nhà bếp',   'Panasonic',  1590000,  25, 'Lòng nồi chống dính',          'sp006.svg', 1, '2025-12-22 16:20:00'),
('SP007', 'Tai nghe Bluetooth Sony WH-CH520','Điện tử',   'Sony',       1190000,  40, 'Pin 50 giờ',                   'sp007.svg', 1, '2025-12-23 08:00:00'),
('SP008', 'Máy hút bụi Xiaomi G10',          'Gia dụng',  'Xiaomi',     4590000,   0, 'Ngừng kinh doanh',             'sp008.svg', 0, '2025-12-23 09:45:00');

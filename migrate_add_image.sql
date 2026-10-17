USE SHOP_DB;

ALTER TABLE tblProducts
  ADD COLUMN image VARCHAR(255) NULL COMMENT 'Tên file ảnh trong thư mục uploads/' AFTER description;

UPDATE tblProducts SET image = 'sp001.svg' WHERE product_code = 'SP001';
UPDATE tblProducts SET image = 'sp002.svg' WHERE product_code = 'SP002';
UPDATE tblProducts SET image = 'sp003.svg' WHERE product_code = 'SP003';
UPDATE tblProducts SET image = 'sp004.svg' WHERE product_code = 'SP004';
UPDATE tblProducts SET image = 'sp005.svg' WHERE product_code = 'SP005';
UPDATE tblProducts SET image = 'sp006.svg' WHERE product_code = 'SP006';
UPDATE tblProducts SET image = 'sp007.svg' WHERE product_code = 'SP007';
UPDATE tblProducts SET image = 'sp008.svg' WHERE product_code = 'SP008';

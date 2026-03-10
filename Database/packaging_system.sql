-- ===================================================
-- Unit Conversion (Packaging) System
-- Run this in phpMyAdmin on the construction_shop DB
-- ===================================================

-- 1. เพิ่ม base_unit ใน products (แสดงหน่วยหลักเป็น text เช่น ถุง, เส้น, ตัว)
ALTER TABLE products
  ADD COLUMN base_unit VARCHAR(50) DEFAULT NULL 
  COMMENT 'หน่วยหลักที่เก็บ stock เช่น ถุง เส้น ตัว แผ่น' 
  AFTER unit_id;

-- 2. สร้างตาราง product_packaging
CREATE TABLE IF NOT EXISTS product_packaging (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  product_id       INT NOT NULL,
  package_unit     VARCHAR(50) NOT NULL COMMENT 'หน่วย packaging เช่น พาเหรด มัด กล่อง ลัง',
  units_per_package DECIMAL(10,2) NOT NULL COMMENT 'จำนวนหน่วยหลักต่อ 1 package',
  INDEX idx_product_id (product_id),
  UNIQUE KEY uq_product_package (product_id, package_unit),
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== ตัวอย่างข้อมูล (optional — ใส่ตาม product_id จริงในระบบ) =====
-- INSERT INTO product_packaging (product_id, package_unit, units_per_package) VALUES
-- (1, 'พาเหรด', 40),
-- (1, 'pallet', 100),
-- (2, 'กล่อง', 100),
-- (2, 'ลัง', 2000);

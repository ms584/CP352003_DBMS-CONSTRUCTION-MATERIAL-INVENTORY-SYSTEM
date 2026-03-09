# 📋 สรุปการเปลี่ยนแปลงโปรเจค CONSTRUCTSHOP

## 🗄️ ฐานข้อมูล (Database)
| สิ่งที่ทำ | ไฟล์ |
| :--- | :--- |
| แก้ชื่อตาราง FOREIGN KEY ผิด (`tb_categories` → `categories`) | `Database.sql` |
| เพิ่ม `customer_type` column ให้ตาราง `customers` | phpMyAdmin SQL |
| สร้าง Mock Data ครบทุกตาราง (พนักงาน/สินค้า/บิล/ลูกค้า 20+ รายการ) | `mockdata.sql` |

---

## 👤 ระบบพนักงาน (Employees)
| สิ่งที่ทำ | ไฟล์ |
| :--- | :--- |
| แก้ bug role `'admin'` → `'Admin'` เพื่อให้สิทธิ์เข้าหน้า Manage Users | `manageuser.php` |
| เปลี่ยน Role field จาก input text → **Dropdown** (Admin/Manager/Cashier/Stock) | `addemployees.php` |
| แก้ไข Label ลอยของฟอร์มเพิ่มพนักงานให้แสดงผลถูกต้อง | `addemployees.php` |
| เพิ่มปุ่ม **"รีเซ็ตรหัสผ่าน"** (สีฟ้า) | `manageuser.php` |
| สร้างหน้า **รีเซ็ตรหัสผ่านพนักงาน** (UI ใหม่ทั้งหมด) | `reset_password.php` (ใหม่) |

---

## 🛍️ ระบบสินค้า (Products)
| สิ่งที่ทำ | ไฟล์ |
| :--- | :--- |
| เพิ่มฟีเจอร์ **อัปโหลดรูปภาพสินค้า** พร้อมระบบ Preview | `edit_material.php` |
| เปลี่ยนปุ่ม Input File เป็น **ปุ่มสีส้มดีไซน์สวยงาม** แสดงชื่อไฟล์และรูปตัวอย่างก่อนบันทึก | `edit_material.php` |

---

## 🏪 ระบบขาย/เบิกสินค้า (Stock Out)
| สิ่งที่ทำ | ไฟล์ |
| :--- | :--- |
| **แก้ Bug สำคัญ:** เพิ่มการตรวจสอบจำนวนสินค้าใน Stock ก่อนบันทึก | `stock_out.php` |
| หากเบิกเกินจำนวนที่มี: ระบบจะ **ยกเลิกบิลอัตโนมัติ** และแจ้งเตือนชื่อสินค้าที่ Stock ไม่พอ | `stock_out.php` |

---

## 👥 ระบบลูกค้า (Customers)
| สิ่งที่ทำ | ไฟล์ |
| :--- | :--- |
| แก้ไข Error: `Warning Undefined array key 'customer_type'` | `customers_list.php` |

---

# Role

```
INSERT INTO `employees` (`username`, `password`, `full_name`, `role`) VALUES
('admin',    'admin1234',   'ผู้ดูแลระบบ',   'Admin'),
('manager',  'manager1234', 'ผู้จัดการ',      'Manager'),
('cashier',  'cashier1234', 'พนักงานแคชเชียร์', 'Cashier'),
('stock',    'stock1234',   'พนักงานสต็อก',   'Stock');
```

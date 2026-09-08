# 📚 Bookstore - ระบบร้านหนังสือออนไลน์

ระบบจัดการร้านหนังสือออนไลน์พัฒนาด้วย PHP + MySQL

## 🛠️ Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- XAMPP / WAMP / LAMP

## 🚀 วิธีติดตั้ง

1. **Clone โปรเจกต์**
   ```bash
   git clone https://github.com/your-username/bookstore.git
   ```
   แล้ววางไว้ใน `htdocs/` ของ XAMPP

2. **ตั้งค่า Database**
   - เปิด phpMyAdmin → `http://localhost/phpmyadmin`
   - สร้าง Database ชื่อ `bookstore`
   - Import ไฟล์ `config/bookstore.sql`

3. **ตั้งค่า Config**
   ```bash
   cp config/db.example.php config/db.php
   ```
   แก้ไข `config/db.php` ใส่ข้อมูล database ของคุณ

4. **สร้าง Admin ครั้งแรก**
   - เปิด `reset_admin.php` แล้วลบ `die()` ออกชั่วคราว
   - เข้า `http://localhost/bookstore/reset_admin.php`
   - กลับมาใส่ `die()` คืนทันที!

5. **เปิดเว็บ**
   ```
   http://localhost/bookstore/
   ```

## 🔑 Default Admin

| Field | Value |
|-------|-------|
| Username | admin |
| Password | *(ดูใน reset_admin.php)* |

## 📁 โครงสร้างโปรเจกต์

```
bookstore/
├── admin/          # หน้าจัดการหลังบ้าน
├── assets/         # CSS, JS, รูปภาพ
├── config/         # ตั้งค่า database (ไม่ upload GitHub)
├── includes/       # ไฟล์ที่ใช้ร่วมกัน
├── uploads/        # รูปภาพสินค้า (ไม่ upload GitHub)
└── index.php       # หน้าแรก
```

## ⚠️ หมายเหตุความปลอดภัย

- ไฟล์ `config/db.php` และ `uploads/` จะไม่ถูก push ขึ้น GitHub (อยู่ใน .gitignore)
- ลบหรือปิด `reset_admin.php` หลังจาก setup เสร็จแล้ว

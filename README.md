# 📚 Mini Project: Online Bookstore

ระบบร้านขายหนังสือออนไลน์พัฒนาโดยใช้ PHP และ MySQL  
เป็นโปรเจคสำหรับฝึกทำ Web Application แบบครบวงจร

---

## 🔧 ฟีเจอร์หลัก

- สมัครสมาชิก / เข้าสู่ระบบ
- แสดงรายการหนังสือ
- เพิ่มสินค้าใส่ตะกร้า
- ระบบสั่งซื้อสินค้า
- ประวัติการสั่งซื้อ
- ระบบ Wishlist
- ระบบหลังบ้าน (Admin Panel)
  - จัดการหนังสือ
  - จัดการหมวดหมู่
  - จัดการคำสั่งซื้อ
  - รายงานยอดขาย

---

## 🛠 เทคโนโลยีที่ใช้

- PHP (Core PHP)
- MySQL
- HTML / CSS
- JavaScript / AJAX
- XAMPP

---

## ⚙ Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- XAMPP / WAMP / LAMP

---

## 🚀 วิธีติดตั้ง

1. **Clone โปรเจกต์**
   ```bash
   git clone https://github.com/samuraijackmj-cmd/miniproject-php-bookstore.git
   ```
   แล้ววางไว้ใน `C:\xampp\htdocs\`

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

---

## 🔑 Default Admin

| Field | Value |
|-------|-------|
| Username | admin |
| Password | *(ดูใน reset_admin.php)* |

---

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

---

## ⚠️ หมายเหตุความปลอดภัย

- ไฟล์ `config/db.php` และ `uploads/` จะไม่ถูก push ขึ้น GitHub (อยู่ใน .gitignore)
- ลบหรือปิด `reset_admin.php` หลังจาก setup เสร็จแล้ว

---

## 📸 Screenshots

### 🏠 หน้าแรก
![Home](Screenshot%202026-02-20%20001749.png)

### 🔐 หน้า Login
![Login](Screenshot%202026-02-20%20001804.png)

### 📝 หน้า Register
![Register](Screenshot%202026-02-20%20001756.png)

### 🛠️ Admin Dashboard
![Admin](Screenshot%202026-02-20%20001817.png)

---

## 🎯 จุดประสงค์ของโปรเจค

- ฝึกทำ CRUD
- ฝึกเชื่อมต่อฐานข้อมูล
- ฝึกเขียนระบบ Login / Session
- ฝึกทำระบบ E-Commerce เบื้องต้น

---

## 👨‍💻 ผู้พัฒนา

Samuraijackmj-cmd  
นักศึกษาที่กำลังฝึกพัฒนา Web Application

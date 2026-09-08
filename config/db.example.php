<?php
// ========================================
// ตัวอย่างการตั้งค่า Database Connection
// ========================================
// 1. Copy ไฟล์นี้แล้วเปลี่ยนชื่อเป็น db.php
// 2. แก้ไขค่าด้านล่างให้ตรงกับ server ของคุณ
// 3. ห้าม commit ไฟล์ db.php ขึ้น GitHub!

$host = 'localhost';       // host ของ MySQL
$dbname = 'bookstore';     // ชื่อ database
$username = 'root';        // username MySQL
$password = '';            // password MySQL (ถ้ามี)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

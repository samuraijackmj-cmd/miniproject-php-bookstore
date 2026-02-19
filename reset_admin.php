<?php
require_once 'config/db.php';

// ข้อมูลแอดมินที่จะสร้าง
$user = 'admin';
$pass = '1234'; // รหัสผ่านง่ายๆ
$role = 'admin';

// แปลงรหัสเป็น Hash
$pass_hash = password_hash($pass, PASSWORD_DEFAULT);

try {
    // 1. ลบ admin เก่าออกก่อน (ถ้ามี) จะได้ไม่ error ซ้ำ
    $conn->exec("DELETE FROM users WHERE username = 'admin'");

    // 2. เพิ่ม admin ใหม่เข้าไป
    $sql = "INSERT INTO users (username, password, email, full_name, role) 
            VALUES (:user, :pass, 'admin@shop.com', 'Super Admin', :role)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':user' => $user,
        ':pass' => $pass_hash,
        ':role' => $role
    ]);

    echo "<h1 style='color:green'>✅ สร้าง Admin สำเร็จ!</h1>";
    echo "<h3>Username: admin</h3>";
    echo "<h3>Password: 1234</h3>";
    echo "<hr>";
    echo "<a href='login.php' style='font-size:20px'>👉 คลิกเพื่อไปหน้าล็อกอิน</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<?php
/**
 * ⚠️ SECURITY: ไฟล์นี้สำหรับ setup ครั้งแรกเท่านั้น
 * ลบหรือเปลี่ยนชื่อไฟล์นี้หลังจาก setup เสร็จแล้ว!
 */

// === ความปลอดภัย: ปิดใช้งานใน production ===
// ลบ comment บรรทัดถัดไปออก เพื่อเปิดใช้งานชั่วคราว
die("<h2>🔒 ไฟล์นี้ถูกปิดใช้งานแล้ว</h2><p>หากต้องการ reset admin ให้ลบ die() ออก แล้วรันอีกครั้ง จากนั้นปิดไฟล์นี้ทันที</p>");

require_once 'config/db.php';

$user = 'admin';
$pass = 'YOUR_PASSWORD_HERE'; // ⚠️ เปลี่ยนเป็น password ที่ต้องการก่อนรัน!
$role = 'admin';

$pass_hash = password_hash($pass, PASSWORD_DEFAULT);

try {
    $conn->exec("DELETE FROM users WHERE username = 'admin'");

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
    echo "<h3>Password: (ตามที่คุณกำหนดใน \$pass)</h3>";
    echo "<p style='color:red'><strong>⚠️ กรุณาปิดไฟล์นี้ทันที!</strong></p>";
    echo "<hr>";
    echo "<a href='login.php' style='font-size:20px'>👉 คลิกเพื่อไปหน้าล็อกอิน</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
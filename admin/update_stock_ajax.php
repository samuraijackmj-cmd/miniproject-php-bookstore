<?php
// เริ่ม Buffer ทันทีที่บรรทัดแรก
ob_start();

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ⚠️ เช็ค Path ให้ถูก: ถ้าไฟล์นี้อยู่ใน folder "admin" ต้องถอยกลับ 1 ขั้น (../) เพื่อหา config
require_once '../config/db.php';

// ✅ สำคัญ: ล้าง Buffer ก่อนส่ง Header เพื่อให้แน่ใจว่าไม่มีช่องว่างหรือ Error หลุดไปใน JSON
ob_end_clean(); 
header('Content-Type: application/json; charset=utf-8');

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']); 
    exit;
}

// 2. ตรวจสอบข้อมูลที่ส่งมา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'], $_POST['action'])) {
    
    $id = (int)$_POST['book_id'];
    $action = $_POST['action'];
    $step = 0;

    // ตรวจสอบ Action
    if ($action === 'increase') {
        $step = 1;
    } elseif ($action === 'decrease') {
        $step = -1;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    try {
        // เริ่ม Transaction (สำคัญมากสำหรับการตัดสต็อก)
        $conn->beginTransaction();
        
        // 3. ดึงค่าปัจจุบัน และ Lock แถวนี้ไว้ (FOR UPDATE) 
        // เพื่อป้องกัน Admin หลายคนกดพร้อมกันแล้วเลขเพี้ยน
        $stmt = $conn->prepare("SELECT stock_quantity FROM books WHERE book_id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();

        if ($current === false) { 
            throw new Exception("Book not found"); 
        }

        // 4. คำนวณค่าใหม่ (ห้ามติดลบ)
        $new_stock = max(0, (int)$current + $step);
        
        // 5. อัปเดตลง Database
        $updateStmt = $conn->prepare("UPDATE books SET stock_quantity = ? WHERE book_id = ?");
        $updateStmt->execute([$new_stock, $id]);
        
        // 6. นับจำนวนสินค้าใกล้หมด (Low Stock) เพื่อส่งกลับไปอัปเดต Badge ที่หน้าเว็บทันที
        $low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();

        // ยืนยัน Transaction
        $conn->commit();

        // 7. ส่งค่ากลับไปให้ JavaScript
        echo json_encode([
            'success' => true, 
            'new_stock' => (int)$new_stock,
            'low_stock_count' => (int)$low_stock_count
        ]);

    } catch (Exception $e) {
        // ถ้ามีปัญหาให้ Rollback ค่าเดิมกลับมา
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}
exit;
?>
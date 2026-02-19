<?php
session_start();
header('Content-Type: application/json');

// ตรวจสอบว่าเป็น Admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

require_once '../config/db.php';

// ตรวจสอบว่ามีการส่ง order_id มาหรือไม่
if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสออเดอร์']);
    exit;
}

$order_id = intval($_POST['order_id']);

try {
    // เริ่มต้น Transaction
    $conn->beginTransaction();
    
    // ลบรายการสินค้าในออเดอร์ก่อน (ถ้ามีตาราง order_items)
    $delete_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $delete_items->execute([$order_id]);
    
    // ลบออเดอร์
    $delete_order = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $delete_order->execute([$order_id]);
    
    // ตรวจสอบว่าลบสำเร็จหรือไม่
    if ($delete_order->rowCount() > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'ลบออเดอร์สำเร็จ']);
    } else {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'ไม่พบออเดอร์ที่ต้องการลบ']);
    }
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
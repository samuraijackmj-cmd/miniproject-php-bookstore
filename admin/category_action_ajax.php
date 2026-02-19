<?php
// เริ่ม Buffer ทันที
ob_start();

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config/db.php';

// ล้าง Buffer ก่อนส่ง Header
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit;
}

// 2. รับ Action (รองรับทั้ง GET และ POST)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    
    // --- Case 1: ดึงข้อมูล (Fetch) ---
    if ($action === 'fetch') {
        $stmt = $conn->query("SELECT * FROM categories ORDER BY category_id DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    
    // --- Case 2: เพิ่มหมวดหมู่ (Add) ---
    elseif ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['category_name'] ?? '');
        
        if (empty($name)) {
            throw new Exception("กรุณากรอกชื่อหมวดหมู่");
        }

        // เช็คชื่อซ้ำ
        $check = $conn->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
        $check->execute([$name]);
        if ($check->fetchColumn() > 0) {
            throw new Exception("ชื่อหมวดหมู่นี้มีอยู่ในระบบแล้ว");
        }

        // เพิ่มข้อมูล
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->execute([$name]);
        echo json_encode(['success' => true]);
    } 
    
    // --- Case 3: ลบหมวดหมู่ (Delete) ---
    elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['category_id'] ?? 0);

        // ตรวจสอบว่ามีหนังสือใช้หมวดหมู่นี้อยู่หรือไม่
        $check = $conn->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
        $check->execute([$id]);
        
        if ($check->fetchColumn() > 0) {
            throw new Exception("ลบไม่ได้! เนื่องจากมีหนังสือที่ผูกกับหมวดหมู่นี้อยู่");
        }

        // ลบข้อมูล
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } 
    
    // --- Invalid Action ---
    else {
        throw new Exception("Invalid Action Request");
    }

} catch (Exception $e) {
    // ส่ง Error กลับไปให้ JavaScript แจ้งเตือน (Toast)
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>
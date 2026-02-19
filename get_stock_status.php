<?php
// เริ่มต้น Buffer ทันที (เพื่อดักจับข้อความขยะที่อาจหลุดมาจาก db.php)
ob_start();

// 1. ตั้งค่า Header ให้ Real-time ขั้นสุด (กัน Browser จำค่าเก่า)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // วันที่ในอดีต
header('Content-Type: application/json; charset=utf-8');

// ปิด Error Report ชั่วคราว (กัน Warning แทรกใน JSON)
error_reporting(0);
ini_set('display_errors', 0);

$response = [
    'status' => 'success',
    'maintenance' => 0, 
    'stocks' => [], 
    'debug' => ''
];

try {
    // 2. เชื่อมต่อฐานข้อมูล (ค้นหาไฟล์ให้เจอแน่นอน)
    $db_files = [
        __DIR__ . '/config/db.php',
        __DIR__ . '/../config/db.php',
        'config/db.php'
    ];

    $db_found = false;
    foreach ($db_files as $file) {
        if (file_exists($file)) {
            require_once $file;
            $db_found = true;
            break;
        }
    }

    // เช็คว่ามีตัวแปรเชื่อมต่อหรือไม่ (รองรับทั้ง $conn และ $pdo เผื่อพี่ตั้งชื่อต่างกัน)
    if (!$db_found || (!isset($conn) && !isset($pdo))) {
        throw new Exception("เชื่อมต่อฐานข้อมูลไม่ได้ (ไม่พบตัวแปร \$conn)");
    }
    
    // แปลงให้เป็น $conn เสมอ
    if (!isset($conn) && isset($pdo)) $conn = $pdo;

    // 3. เช็คโหมดปิดปรับปรุง
    $mt = $conn->query("SELECT maintenance_mode FROM settings WHERE id = 1");
    $site = $mt->fetch(PDO::FETCH_ASSOC);
    $response['maintenance'] = (int)($site['maintenance_mode'] ?? 0);

    // 4. ดึงข้อมูลสต็อก + ราคา (หัวใจสำคัญ)
    if (isset($_GET['ids']) && !empty($_GET['ids'])) {
        $ids = explode(',', $_GET['ids']);
        $clean_ids = [];
        
        foreach($ids as $id) {
            $id = trim($id);
            if(is_numeric($id)) $clean_ids[] = (int)$id;
        }

        if(!empty($clean_ids)) {
            $placeholders = str_repeat('?,', count($clean_ids) - 1) . '?';
            
            // ✅ ดึงทั้ง สต็อก, ราคา, และ ส่วนลด
            $sql = "SELECT book_id, stock_quantity, price, discount_percent FROM books WHERE book_id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($clean_ids);
            $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($raw_data as $row) {
                $response['stocks'][] = [
                    'book_id' => (int)$row['book_id'],
                    'stock_quantity' => (int)$row['stock_quantity'],
                    'price' => (float)$row['price'],                // ราคา Real-time
                    'discount_percent' => (float)$row['discount_percent'] // ส่วนลด Real-time
                ];
            }
        }
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['debug'] = $e->getMessage();
}

// ⚠️ ล้าง Buffer ขยะทั้งหมดก่อนส่ง JSON (แก้ปัญหาค่าไม่เปลี่ยน)
ob_end_clean();

// ส่ง JSON ที่สะอาดหมดจด
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
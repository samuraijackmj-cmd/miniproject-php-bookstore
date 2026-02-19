<?php
session_start();
require_once 'config/db.php';

// ถ้าไม่ได้มาจากการกดปุ่ม หรือไม่ได้ login
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

try {
    $conn->beginTransaction();

    $user_id = $_SESSION['user_id'];
    
    // รับค่าจากฟอร์ม
    $shipping_name = $_POST['shipping_name'] ?? $_POST['fullname']; 
    $phone_number = $_POST['phone_number'] ?? $_POST['phone'];
    $shipping_address = $_POST['shipping_address'] ?? $_POST['address'];
    $total_amount = $_POST['total_amount'];
    $payment_method = $_POST['payment_method'] ?? 'bank_transfer';
    
    // --- 1. สร้างเลข Order Number ---
    $order_number = 'ORD-' . date('ymdHi') . '-' . rand(10, 99);

    // --- 2. จัดการรูปสลิป (เฉพาะ Bank Transfer) ---
    $slip_image = null;
    if ($payment_method === 'bank_transfer' || $payment_method === 'transfer') {
        if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("กรุณาแนบหลักฐานการโอนเงิน");
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['slip']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("ไฟล์ต้องเป็น JPG หรือ PNG เท่านั้น");
        }
        
        if ($_FILES['slip']['size'] > 5 * 1024 * 1024) {
            throw new Exception("ขนาดไฟล์ต้องไม่เกิน 5MB");
        }
        
        $ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
        $slip_image = "slip_" . uniqid() . "." . $ext;
        
        if (!file_exists("uploads/slips")) {
            mkdir("uploads/slips", 0777, true);
        }
        
        if (!move_uploaded_file($_FILES['slip']['tmp_name'], "uploads/slips/" . $slip_image)) {
            throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์");
        }
    }

    // อัปเดตที่อยู่ลูกค้าล่าสุดในตาราง users
    $stmt_user = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?");
    $stmt_user->execute([$shipping_name, $phone_number, $shipping_address, $user_id]);

    // --- 3. บันทึกลงตาราง orders ---
    $sql = "INSERT INTO orders 
            (order_number, user_id, shipping_name, phone_number, shipping_address, total_amount, payment_method, status, slip_image, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $order_number,
        $user_id,
        $shipping_name,
        $phone_number,
        $shipping_address,
        $total_amount,
        $payment_method,
        $slip_image
    ]);
    
    $order_id = $conn->lastInsertId();

    // --- 4. บันทึกรายการสินค้า และ ตัดสต็อกสินค้า (แก้ไขจุดนี้!) ---
    // เปลี่ยนจาก $_SESSION เป็นดึงจากตาราง cart โดยตรง
    $stmt_cart = $conn->prepare("SELECT c.book_id, c.quantity, b.price, b.discount_percent, b.stock_quantity, b.title 
                                 FROM cart c 
                                 JOIN books b ON c.book_id = b.book_id 
                                 WHERE c.user_id = ?");
    $stmt_cart->execute([$user_id]);
    $cart_items = $stmt_cart->fetchAll();

    // ถ้าตะกร้าว่าง (อาจเกิดจากเปิดหลาย Tab หรือ Session หลุด)
    if (count($cart_items) == 0) {
        throw new Exception("ไม่พบสินค้าในตะกร้าของคุณ (กรุณาลองทำรายการใหม่อีกครั้ง)");
    }

    foreach ($cart_items as $item) {
        // เช็คสต็อกก่อนตัด
        if ($item['stock_quantity'] < $item['quantity']) {
            throw new Exception("ขออภัย: หนังสือ '" . $item['title'] . "' สินค้าไม่พอ (คงเหลือ " . $item['stock_quantity'] . " เล่ม)");
        }
        
        // คำนวณราคาขายจริง
        $sale_price = $item['price'] - ($item['price'] * ($item['discount_percent'] ?? 0) / 100);

        // บันทึกลง order_items
        $sql_item = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)";
        $conn->prepare($sql_item)->execute([$order_id, $item['book_id'], $item['quantity'], $sale_price]);

        // ตัดสต็อกสินค้าทันที
        $sql_stock = "UPDATE books SET stock_quantity = stock_quantity - ? WHERE book_id = ?";
        $conn->prepare($sql_stock)->execute([$item['quantity'], $item['book_id']]);
    }

    // --- 5. ล้างตะกร้า (เปลี่ยนจาก unset session เป็นลบใน DB) ---
    $conn->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
    // เผื่อไว้: ล้าง Session ด้วยก็ได้เพื่อความชัวร์ (แต่หลักๆ คือ DB)
    if(isset($_SESSION['cart'])) { unset($_SESSION['cart']); }

    $conn->commit();

    header("Location: order_success.php?order_id=" . $order_id);
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) { $conn->rollBack(); }
    if (isset($slip_image) && $slip_image && file_exists("uploads/slips/" . $slip_image)) { 
        @unlink("uploads/slips/" . $slip_image); 
    }
    
    // --- Error Page Design (Theme: Midnight Glass Pro) ---
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>เกิดข้อผิดพลาด | BookStore</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600&display=swap');
            
            body {
                font-family: 'Kanit', sans-serif;
                background: #0f111a;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 2rem;
                background-image: 
                    radial-gradient(at 0% 0%, rgba(239, 68, 68, 0.15) 0px, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.1) 0px, transparent 50%);
            }
            
            .error-box {
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(239, 68, 68, 0.3);
                border-radius: 24px;
                padding: 3rem;
                max-width: 550px;
                width: 100%;
                backdrop-filter: blur(20px);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
                text-align: center;
                position: relative;
                overflow: hidden;
            }

            .error-box::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0; height: 4px;
                background: linear-gradient(90deg, #ef4444, #f87171);
            }
            
            .icon-wrapper {
                width: 80px; height: 80px;
                background: rgba(239, 68, 68, 0.1);
                border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 1.5rem;
                border: 1px solid rgba(239, 68, 68, 0.2);
                box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
            }
            
            .icon-wrapper i {
                font-size: 2.5rem;
                color: #ef4444;
            }
            
            h3 {
                font-weight: 700;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            
            .error-message {
                background: rgba(239, 68, 68, 0.1);
                border: 1px dashed rgba(239, 68, 68, 0.3);
                border-radius: 12px;
                padding: 1.5rem;
                margin: 1.5rem 0;
                color: #fca5a5;
                font-size: 1rem;
                line-height: 1.6;
            }
            
            .btn-action {
                background: #ef4444;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 50px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s;
                display: inline-flex; align-items: center; gap: 8px;
                box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
            }
            
            .btn-action:hover {
                background: #dc2626;
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5);
                color: white;
            }

            .btn-outline {
                background: transparent;
                border: 1px solid rgba(255,255,255,0.2);
                color: rgba(255,255,255,0.7);
                padding: 12px 30px;
                border-radius: 50px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s;
            }

            .btn-outline:hover {
                border-color: #fff;
                color: #fff;
                background: rgba(255,255,255,0.05);
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="icon-wrapper">
                <i class="bi bi-x-lg"></i>
            </div>
            <h3>เกิดข้อผิดพลาดในการสั่งซื้อ</h3>
            <p class="text-white-50">ระบบไม่สามารถดำเนินการคำสั่งซื้อของคุณได้ในขณะนี้</p>
            
            <div class="error-message">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($e->getMessage()); ?>
            </div>
            
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="checkout.php" class="btn-action">
                    <i class="bi bi-arrow-left"></i> กลับไปแก้ไข
                </a>
                <a href="index.php" class="btn-outline">
                    หน้าหลัก
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
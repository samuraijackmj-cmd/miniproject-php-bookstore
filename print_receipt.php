<?php
session_start();
require_once 'config/db.php'; 

// 1. ตรวจสอบ Login และ ID
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: order_history.php");
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 2. ดึงข้อมูลออเดอร์
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("ไม่พบข้อมูลออเดอร์");
}

// 3. ดึงรายการสินค้า
$stmt_items = $conn->prepare("
    SELECT oi.*, b.title 
    FROM order_items oi 
    LEFT JOIN books b ON oi.book_id = b.book_id 
    WHERE oi.order_id = ?
");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

// Mapping ข้อมูลให้เหมือนหน้า Order Success
$payment_map = ['bank_transfer' => 'โอนเงิน / QR Code', 'cod' => 'เก็บเงินปลายทาง'];
$payment_text = $payment_map[$order['payment_method']] ?? strtoupper($order['payment_method']);
$order_date = date('d/m/Y H:i', strtotime($order['created_at']));

// ตั้ง Timezone
date_default_timezone_set('Asia/Bangkok');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ #<?php echo $order['order_number']; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        /* --- Screen Styles (หน้าจอปกติ) --- */
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #0f172a; /* พื้นหลัง Midnight */
            color: #1e293b; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 100px; /* เว้นที่ด้านล่างให้ปุ่ม */
        }

        /* 🌌 Background Effects */
        .ambient-light {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%);
            pointer-events: none;
        }

        /* 📄 Paper Styles (ตัวใบเสร็จ) */
        .receipt-paper {
            background: white;
            width: 100%;
            max-width: 800px;
            padding: 50px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            font-family: 'Sarabun', sans-serif; /* ใช้ฟอนต์สารบัญสำหรับใบเสร็จ */
            position: relative;
            z-index: 1;
        }

        /* Design Elements ตามหน้า Order Success */
        .receipt-header { text-align: center; margin-bottom: 30px; }
        .receipt-title { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .store-name { font-size: 18px; font-weight: bold; }
        
        .divider-thick { border-bottom: 2px solid #000; margin: 15px 0; }
        
        .receipt-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 16px; line-height: 1.6; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 16px; }
        th { border-bottom: 2px solid #000; border-top: 2px solid #000; padding: 10px 5px; text-align: left; background: #fff !important; }
        td { border-bottom: 1px solid #ddd; padding: 10px 5px; vertical-align: top; }
        
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        
        .receipt-total-row { 
            display: flex; justify-content: flex-end; 
            font-size: 20px; font-weight: bold; margin-top: 20px; 
            border-top: 2px solid #000; padding-top: 10px; 
        }
        
        .receipt-footer { text-align: center; margin-top: 50px; font-size: 14px; color: #666; }

        /* 🔘 Bottom Bar */
        .no-print-bar { 
            position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
            padding: 15px; box-shadow: 0 -4px 20px rgba(0,0,0,0.2); 
            display: flex; justify-content: center; gap: 15px; z-index: 100;
        }

        /* 🖨️ Print Styles */
        @media print { 
            body { background: #fff; padding: 0; margin: 0; }
            .receipt-paper { box-shadow: none; padding: 20px; max-width: 100%; width: 100%; }
            .no-print-bar, .ambient-light { display: none !important; } 
        }
    </style>
</head>
<body>

<div class="ambient-light"></div>

<div class="receipt-paper animate__animated animate__fadeInUp">
    <div class="receipt-header">
        <div class="receipt-title">ใบเสร็จรับเงิน / Receipt</div>
        <div class="store-name">BookStore Premium</div>
        <div style="font-size: 14px;">123 ถนนหนังสือ เขตวรรณกรรม กรุงเทพฯ 10000</div>
        <div style="font-size: 14px;">โทร: 02-123-4567 | www.bookstore-premium.com</div>
    </div>

    <div class="divider-thick"></div>

    <div class="receipt-info">
        <div>
            <strong>ลูกค้า:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?><br>
            <strong>ที่อยู่:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?><br>
            <strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($order['phone_number']); ?>
        </div>
        <div class="text-end">
            <strong>เลขที่ออเดอร์:</strong> <?php echo $order['order_number']; ?><br>
            <strong>วันที่:</strong> <?php echo $order_date; ?><br>
            <strong>ชำระโดย:</strong> <?php echo $payment_text; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">รายการสินค้า</th>
                <th class="text-center" style="width: 15%;">จำนวน</th>
                <th class="text-end" style="width: 15%;">ราคา/หน่วย</th>
                <th class="text-end" style="width: 20%;">รวม</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-end"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-center text-danger">ไม่พบรายการสินค้า</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="receipt-total-row">
        ยอดสุทธิ: ฿<?php echo number_format($order['total_amount'], 2); ?>
    </div>

    <div class="receipt-footer">
        <p>ขอบคุณที่อุดหนุนสินค้าของเรา</p>
        <p>เอกสารนี้ออกโดยระบบอัตโนมัติ (BookStore Premium System)</p>
    </div>
</div>

<div class="no-print-bar">
    <a href="order_history.php" class="btn btn-light border shadow-sm">
        <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
    </a>
    <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">
        <i class="bi bi-printer-fill me-2"></i>พิมพ์ใบเสร็จ
    </button>
</div>

</body>
</html>
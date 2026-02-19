<?php
session_start();
require_once 'config/db.php';

// ตั้งค่า Timezone ให้เป็นไทย
date_default_timezone_set('Asia/Bangkok');

if (!isset($_GET['order_id'])) { 
    header("Location: index.php"); 
    exit; 
}

$order_id = $_GET['order_id'];
$current_user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'user';

// 1. ดึงข้อมูลออเดอร์
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php"); 
    exit; 
}

// 🔒 SECURITY CHECK
if ($order['user_id'] != $current_user_id && $user_role != 'admin') {
    header("Location: index.php");
    exit;
}

// 2. ดึงรายการสินค้า
$stmt_items = $conn->prepare("
    SELECT oi.*, b.title 
    FROM order_items oi 
    LEFT JOIN books b ON oi.book_id = b.book_id 
    WHERE oi.order_id = ?
");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

// แปลงชื่อวิธีชำระเงิน
$payment_map = [
    'bank_transfer' => 'โอนเงิน / QR Code',
    'cod' => 'เก็บเงินปลายทาง'
];
$payment_text = $payment_map[$order['payment_method']] ?? strtoupper($order['payment_method']);

// จัดรูปแบบวันที่
$order_date = date('d/m/Y H:i', strtotime($order['created_at']));
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งซื้อสำเร็จ #<?php echo $order['order_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        /* --- Theme Variables (Midnight Space) --- */
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --accent: #a855f7;
            --bg-dark: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.6);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-white: #ffffff;
            --text-gray: #cbd5e1;
        }

        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: var(--bg-dark); 
            color: var(--text-white);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* --- 🌌 Background Effects --- */
        .ambient-light {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: 
                radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%);
            pointer-events: none;
        }

        #starfield {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            pointer-events: none;
        }

        /* --- 📦 Success Card --- */
        .success-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 80px); /* ลบความสูง Navbar */
            padding: 2rem 0;
        }

        .success-card { 
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px; 
            padding: 3rem 2rem; 
            max-width: 500px; 
            width: 100%; 
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .check-icon {
            font-size: 5rem;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 15px rgba(16, 185, 129, 0.4));
            margin-bottom: 1.5rem;
            display: inline-block;
        }

        .order-number-badge {
            background: rgba(99, 102, 241, 0.15);
            color: var(--primary-light);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-family: monospace;
            font-size: 1.1rem;
            border: 1px solid rgba(99, 102, 241, 0.3);
            display: inline-block;
            margin: 1.5rem 0;
        }

        /* Buttons */
        .btn-custom {
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            margin-bottom: 0.75rem;
        }

        .btn-primary-glow {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }
        .btn-primary-glow:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(99, 102, 241, 0.5); color: white; }

        .btn-outline-glass {
            background: transparent;
            border: 1px solid var(--glass-border);
            color: var(--text-gray);
        }
        .btn-outline-glass:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-color: white;
        }

        /* 🧾 Hidden Receipt Section (for printing) */
        #printable-receipt { display: none; }

        /* 🖨️ Print Styles */
        @media print {
            @page { margin: 0; size: auto; }
            body {
                background: white !important;
                color: black !important;
                font-family: 'Sarabun', sans-serif !important;
                height: auto !important;
                overflow: visible !important;
            }
            
            /* ซ่อนส่วนที่ไม่ต้องการ */
            nav.navbar, .success-wrapper, .ambient-light, #starfield { display: none !important; }

            /* แสดงใบเสร็จ */
            #printable-receipt {
                display: block !important;
                padding: 40px 50px;
                width: 100%;
                max-width: 800px;
                margin: 0 auto;
            }

            .receipt-header { text-align: center; margin-bottom: 30px; }
            .receipt-title { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
            .store-name { font-size: 18px; color: #555; }
            
            .divider-thick { border-bottom: 2px solid #000; margin: 15px 0; }
            
            .receipt-info { 
                display: flex; justify-content: space-between; margin-bottom: 25px; 
                line-height: 1.5; font-size: 14px;
            }
            
            table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px; }
            th { border-bottom: 1px solid #000; border-top: 1px solid #000; padding: 10px 5px; text-align: left; }
            td { border-bottom: 1px solid #eee; padding: 10px 5px; vertical-align: top; }
            
            .text-end { text-align: right; }
            .text-center { text-align: center; }
            
            .receipt-total-row { 
                text-align: right; font-size: 20px; font-weight: bold; margin-top: 20px; 
            }

            .receipt-footer { text-align: center; margin-top: 60px; font-size: 12px; color: #888; }
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>

    <div class="container success-wrapper">
        <div class="success-card animate__animated animate__zoomIn">
            <i class="bi bi-check-circle-fill check-icon"></i>
            <h2 class="fw-bold mb-2">ขอบคุณที่ใช้บริการ!</h2>
            <p class="text-gray mb-0">เราได้รับคำสั่งซื้อของคุณเรียบร้อยแล้ว</p>
            
            <div class="order-number-badge">
                #<?php echo htmlspecialchars($order['order_number']); ?>
            </div>
            
            <div class="d-grid gap-2">
                <button onclick="window.print()" class="btn btn-custom btn-primary-glow">
                    <i class="bi bi-printer-fill"></i> พิมพ์ใบเสร็จ
                </button>
                <a href="order_history.php" class="btn btn-custom btn-outline-glass">
                    <i class="bi bi-clock-history"></i> ตรวจสอบสถานะ
                </a>
                <a href="index.php" class="btn btn-custom btn-outline-glass border-0 text-muted" style="font-size: 0.9rem;">
                    กลับหน้าหลัก
                </a>
            </div>
        </div>
    </div>

    <div id="printable-receipt">
        <div class="receipt-header">
            <div class="receipt-title">ใบเสร็จรับเงิน / Receipt</div>
            <div class="store-name">BookStore Premium</div>
        </div>

        <div class="divider-thick"></div>

        <div class="receipt-info">
            <div>
                <strong>ลูกค้า:</strong> <?php echo htmlspecialchars($order['shipping_name'] ?? $order['customer_name']); ?><br>
                <strong>ที่อยู่:</strong> <?php echo htmlspecialchars($order['shipping_address'] ?? 'ไม่ได้ระบุ'); ?><br>
                <strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($order['phone_number'] ?? '-'); ?>
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
                        <td><?php echo htmlspecialchars($item['title'] ?? 'สินค้าถูกลบ'); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end"><?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px; color: red;">
                            *** ไม่พบรายการสินค้า (โปรดติดต่อเจ้าหน้าที่) ***
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="divider-thick"></div>

        <div class="receipt-total-row">
            ยอดสุทธิ: ฿<?php echo number_format($order['total_amount'], 2); ?>
        </div>

        <div class="receipt-footer">
            <p>ขอบคุณที่อุดหนุนสินค้าของเรา</p>
            <p>เอกสารนี้ออกโดยระบบอัตโนมัติ (BookStore Premium System)</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Starfield Animation
        const canvas = document.getElementById('starfield');
        const ctx = canvas.getContext('2d');
        let stars = [], width, height;
        function resize() { width = canvas.width = window.innerWidth; height = canvas.height = window.innerHeight; }
        class Star {
            constructor() { this.x = Math.random() * width; this.y = Math.random() * height; this.size = Math.random() * 2; this.speedY = Math.random() * 0.5 + 0.1; }
            update() { this.y -= this.speedY; if(this.y < 0) this.y = height; }
            draw() { ctx.fillStyle = 'rgba(255,255,255,0.5)'; ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI*2); ctx.fill(); }
        }
        function init() { stars = []; for(let i=0; i<60; i++) stars.push(new Star()); }
        function animate() { ctx.clearRect(0,0,width,height); stars.forEach(s => { s.update(); s.draw(); }); requestAnimationFrame(animate); }
        window.addEventListener('resize', () => { resize(); init(); });
        resize(); init(); animate();
    </script>
</body>
</html>
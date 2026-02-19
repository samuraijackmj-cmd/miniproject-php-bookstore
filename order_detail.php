<?php
session_start();
require_once 'config/db.php';

// 1. ตรวจสอบ Login และ ID ของออเดอร์
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
    die("<div style='color:white; background:#0f172a; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'>❌ ไม่พบข้อมูลออเดอร์</div>");
}

// 🔍 ค้นหาชื่อไฟล์สลิปอัตโนมัติ
$slip_img = "";
foreach (['payment_slip', 'slip', 'slip_image', 'payment_image'] as $col) {
    if (isset($order[$col]) && !empty($order[$col])) {
        $slip_img = $order[$col];
        break;
    }
}

// 3. ดึงรายการสินค้า
$stmt_items = $conn->prepare("
    SELECT oi.*, b.title, b.image 
    FROM order_items oi 
    JOIN books b ON oi.book_id = b.book_id 
    WHERE oi.order_id = ?
");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

// 4. คำนวณยอด
$subtotal = 0;
foreach($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping_fee = $order['shipping_fee'] ?? 0;
$payment_method = $order['payment_method'] ?? 'bank_transfer';
$st_check = trim(strtolower($order['status']));

function checkSt($status, $keywords) {
    foreach($keywords as $k) {
        if (strpos($status, $k) !== false) return true;
    }
    return false;
}

// Badge Logic (โครงเดิม)
$badge_color = 'secondary'; $badge_icon = 'info-circle'; $badge_text = $order['status'];
if (checkSt($st_check, ['pending', 'รอตรวจสอบ', '🟡'])) { $badge_color = 'warning'; $badge_icon = 'clock'; }
elseif (checkSt($st_check, ['cancelled', 'ยกเลิก', '❌'])) { 
    $badge_color = 'danger'; $badge_icon = 'x-circle';
    $badge_text = ($order['cancelled_by'] == 'admin') ? 'ยกเลิกโดยร้านค้า' : 'คุณยกเลิกรายการนี้';
}
elseif (checkSt($st_check, ['delivered', 'สำเร็จ', 'success', '✅'])) { $badge_color = 'success'; $badge_icon = 'box-seam-fill'; $badge_text='จัดส่งสำเร็จ'; }
elseif (checkSt($st_check, ['shipped', 'จัดส่ง', '🚚'])) { $badge_color = 'primary'; $badge_icon = 'truck'; $badge_text='กำลังจัดส่ง'; }
elseif (checkSt($st_check, ['paid', 'ชำระเงิน', 'confirmed', 'ยืนยัน', '🔵'])) { $badge_color = 'info'; $badge_icon = 'check-circle'; }

$confirmed_keywords = ['confirmed', 'paid', 'shipped', 'delivered', 'สำเร็จ', 'success', 'ยืนยัน', 'ชำระเงิน', 'จัดส่ง', '✅', '🚚', '🔵'];
$shipped_keywords   = ['shipped', 'delivered', 'สำเร็จ', 'success', 'จัดส่ง', 'กำลังจัดส่ง', '✅', '🚚'];
$delivered_keywords = ['delivered', 'สำเร็จ', 'success', 'จัดส่งสำเร็จ', '✅'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซื้อ #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --primary: #6366f1; --primary-light: #818cf8; --accent: #a855f7; --bg-dark: #0f172a;
            --text-white: #ffffff; --text-gray: #cbd5e1; --glass-bg: rgba(30, 41, 59, 0.6);
            --glass-border: rgba(255, 255, 255, 0.1); --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
        }
        body { font-family: 'Kanit', sans-serif; background: var(--bg-dark); color: var(--text-white); min-height: 100vh; overflow-x: hidden; }
        .ambient-light { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%), radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%); pointer-events: none; }
        #starfield { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }
        
        .glass-container { background: var(--glass-bg); backdrop-filter: blur(24px); border: 1px solid var(--glass-border); border-radius: 24px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; position: relative; z-index: 1; }
        .receipt-header { background: rgba(255, 255, 255, 0.05); padding: 2.5rem 2rem; border-bottom: 1px solid var(--glass-border); }
        .bookstore-title { font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .info-card { background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1.5rem; transition: all 0.3s ease; }
        .product-card-white { background: #ffffff !important; border-radius: 16px; padding: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .product-card-white .table td { color: #212529 !important; vertical-align: middle; }
        .book-thumbnail { width: 50px; height: 75px; object-fit: cover; border-radius: 4px; }
        
        .status-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 50px; font-weight: 500; }
        .status-badge.warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .status-badge.info { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-badge.success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        
        .timeline-dot { width: 40px; height: 40px; border-radius: 50%; background: rgba(255, 255, 255, 0.05); border: 2px solid var(--glass-border); display: flex; align-items: center; justify-content: center; position: relative; z-index: 1; }
        .timeline-dot.active { background: linear-gradient(135deg, var(--success), #059669) !important; border-color: var(--success) !important; box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
        .timeline-item { display: flex; gap: 1.25rem; margin-bottom: 2rem; position: relative; }
        .timeline-item:not(:last-child)::after { content: ''; position: absolute; left: 19px; top: 40px; width: 2px; height: calc(100% + 2rem); background: var(--glass-border); }
        
        /* ⚪ ปุ่มย้อนกลับสีขาว */
        .btn-custom { padding: 0.8rem 2rem; border-radius: 50px; font-weight: 600; border: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 0.75rem; text-decoration: none; }
        .btn-outline-white { background: transparent; border: 1.5px solid rgba(255,255,255,0.4); color: #fff !important; } 
        .btn-outline-white:hover { background: rgba(255, 255, 255, 0.1); border-color: #fff; }
        .btn-primary-custom { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white !important; }

        .payment-method-badge { display: inline-flex; align-items: center; gap: 0.75rem; padding: 1rem 1.5rem; border-radius: 14px; font-weight: 600; margin-top: 0.5rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa; }
        
        /* 📸 สไตล์สลิป */
        .slip-box { margin-top: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.1); border-radius: 16px; }
        .slip-thumb { width: 130px; height: 170px; object-fit: cover; border-radius: 10px; cursor: pointer; transition: 0.3s; }
        .slip-thumb:hover { transform: scale(1.05); }

        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>
    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>

    <div class="container py-5">
        <div class="glass-container animate__animated animate__fadeInUp">
            
            <div class="receipt-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h1 class="mb-0 fw-bold bookstore-title">BookStore</h1>
                        <p class="mb-0 text-white opacity-75">ออเดอร์ #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?> • <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="no-print">
                        <span class="status-badge <?php echo $badge_color; ?>">
                            <i class="bi bi-<?php echo $badge_icon; ?>"></i> <?php echo $badge_text; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-4 p-lg-5">
                <div class="row g-4 mb-5">
                    <div class="col-lg-7">
                        <div class="info-card h-100">
                            <h5 class="mb-4 fw-bold text-white"><i class="bi bi-truck me-2"></i>สถานะการจัดส่ง</h5>
                            <div class="timeline mt-4">
                                <div class="timeline-item">
                                    <div class="timeline-dot active"><i class="bi bi-check-lg text-white"></i></div>
                                    <div class="timeline-content"><h6 class="text-white mb-0">รับคำสั่งซื้อ</h6><small class="text-gray"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small></div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo checkSt($st_check, $confirmed_keywords) ? 'active' : ''; ?>"><i class="bi bi-check-lg text-white"></i></div>
                                    <div class="timeline-content"><h6 class="text-white mb-0">ยืนยันการชำระเงิน</h6><small class="text-gray">ตรวจสอบเรียบร้อย</small></div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo checkSt($st_check, $shipped_keywords) ? 'active' : ''; ?>"><i class="bi bi-check-lg text-white"></i></div>
                                    <div class="timeline-content"><h6 class="text-white mb-0">จัดส่งพัสดุ</h6><small class="text-gray"><?php echo $order['tracking_number'] ?: 'เตรียมจัดส่ง'; ?></small></div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo checkSt($st_check, $delivered_keywords) ? 'active' : ''; ?>"><i class="bi bi-check-lg text-white"></i></div>
                                    <div class="timeline-content"><h6 class="text-white mb-0">สำเร็จ</h6></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-5">
                        <div class="row g-4 h-100">
                            <div class="col-12">
                                <div class="info-card">
                                    <h6 class="mb-3 fw-bold text-white"><i class="bi bi-geo-alt-fill me-2 text-success"></i>ที่อยู่จัดส่ง</h6>
                                    <p class="mb-1 fw-semibold text-white"><?php echo htmlspecialchars($order['shipping_name'] ?? ''); ?></p>
                                    <p class="mb-2 text-gray small"><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '')); ?></p>
                                    <p class="mb-0 text-white small"><i class="bi bi-telephone-fill me-2"></i> <?php echo htmlspecialchars($order['phone_number'] ?? '-'); ?></p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="info-card text-center">
                                    <h6 class="mb-2 fw-bold text-white">เลขติดตามพัสดุ</h6>
                                    <div class="p-2 border border-dashed border-primary rounded text-primary fw-bold fs-5">
                                        <?php echo $order['tracking_number'] ?: '-- รอการแจ้ง --'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="product-card-white mb-4 table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th class="ps-3">สินค้า</th><th class="text-center">ราคา</th><th class="text-center">จำนวน</th><th class="text-end pe-3">รวม</th></tr></thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <td class="ps-3"><div class="d-flex align-items-center gap-2"><img src="uploads/<?php echo $item['image']; ?>" class="book-thumbnail"><span class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></span></div></td>
                                <td class="text-center">฿<?php echo number_format($item['price']); ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end pe-3 fw-bold">฿<?php echo number_format($item['price'] * $item['quantity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="info-card h-100">
                            <h6 class="mb-3 fw-bold text-white"><i class="bi bi-credit-card-fill me-2"></i>การชำระเงิน</h6>
                            <div class="payment-method-badge">
                                <i class="bi <?php echo ($payment_method === 'cod') ? 'bi-cash-coin' : 'bi-bank'; ?> me-2"></i>
                                <span><?php echo ($payment_method === 'cod') ? 'เก็บเงินปลายทาง' : 'โอนเงินผ่านธนาคาร'; ?></span>
                            </div>

                            <?php if (!empty($slip_img)): ?>
                            <div class="slip-box no-print text-center">
                                <p class="text-white-50 small mb-2">หลักฐานการโอนเงิน:</p>
                                <img src="uploads/slips/<?php echo $slip_img; ?>" class="slip-thumb shadow" data-bs-toggle="modal" data-bs-target="#slipModal">
                                <div class="mt-2 small text-gray opacity-75">* คลิกเพื่อดูรูปใหญ่</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="info-card h-100">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-white opacity-75">รวมสินค้า</span>
                                <span class="fw-semibold">฿<?php echo number_format($subtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom border-white border-opacity-10">
                                <span class="text-white opacity-75">ค่าจัดส่ง</span>
                                <span class="fw-semibold"><?php echo $shipping_fee > 0 ? '฿'.number_format($shipping_fee) : 'ฟรี'; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <span class="fw-bold fs-4">ยอดสุทธิ</span>
                                <span class="fw-bold fs-3 text-info">฿<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-3 mt-5 no-print">
                    <a href="order_history.php" class="btn-custom btn-outline-white"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
                    <button onclick="window.print()" class="btn-custom btn-primary-custom"><i class="bi bi-printer-fill"></i> พิมพ์ใบเสร็จ</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade no-print" id="slipModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-white">หลักฐานการโอนเงิน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img src="uploads/slips/<?php echo $slip_img; ?>" class="img-fluid rounded shadow-lg" style="max-height: 75vh;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Starfield
        const canvas = document.getElementById('starfield'), ctx = canvas.getContext('2d');
        let stars = [], width, height;
        function resize() { width = canvas.width = window.innerWidth; height = canvas.height = window.innerHeight; }
        class Star { constructor() { this.x = Math.random()*width; this.y = Math.random()*height; this.size = Math.random()*2; this.speedY = Math.random()*0.5+0.1; } update() { this.y-=this.speedY; if(this.y<0)this.y=height; } draw() { ctx.fillStyle='rgba(255,255,255,0.5)'; ctx.beginPath(); ctx.arc(this.x,this.y,this.size,0,Math.PI*2); ctx.fill(); } }
        function init() { stars = []; for(let i=0; i<60; i++) stars.push(new Star()); }
        function animate() { ctx.clearRect(0,0,width,height); stars.forEach(s => { s.update(); s.draw(); }); requestAnimationFrame(animate); }
        window.addEventListener('resize', () => { resize(); init(); }); resize(); init(); animate();
    </script>
</body>
</html>
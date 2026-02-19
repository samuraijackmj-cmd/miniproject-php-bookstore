<?php
session_start();
require_once 'config/db.php'; 

// เช็ค Login
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC); 
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสั่งซื้อ | BookStore Premium</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        /* [คงเดิมทุกประการ] */
        :root { --primary: #6366f1; --primary-light: #818cf8; --accent: #a855f7; --bg-dark: #0f172a; --text-white: #ffffff; --text-gray: #cbd5e1; --glass-bg: rgba(30, 41, 59, 0.6); --glass-border: rgba(255, 255, 255, 0.1); }
        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-dark); color: var(--text-white); overflow-x: hidden; min-height: 100vh; position: relative; }
        .ambient-light { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%), radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%); pointer-events: none; }
        #starfield { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem; }
        .stat-card { background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); padding: 1.5rem; border-radius: 20px; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(99, 102, 241, 0.4); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .orders-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .order-card { background: var(--glass-bg); backdrop-filter: blur(16px); border: 1px solid var(--glass-border); border-radius: 24px; padding: 1.75rem; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); position: relative; overflow: hidden; opacity: 0; animation: fadeInUp 0.6s forwards; }
        .order-card:hover { transform: scale(1.01) translateX(8px); border-color: rgba(99, 102, 241, 0.3); background: rgba(30, 41, 59, 0.8); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .order-card::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.03), transparent); transition: 0.5s; skew: -25deg; pointer-events: none; z-index: 1; }
        .order-card:hover::after { left: 150%; }
        .order-header, .order-grid { position: relative; z-index: 2; }
        .order-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .order-id { font-size: 1.2rem; font-weight: 700; color: #fff; }
        .order-id span { color: var(--primary-light); }
        .status-badge { padding: 6px 16px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .status-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-cancel { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .status-admin-cancel { background: rgba(239, 68, 68, 0.25); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.5); }
        .order-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; padding: 1.25rem; background: rgba(255,255,255,0.03); border-radius: 16px; }
        .order-label { font-size: 0.75rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .order-value { font-weight: 600; font-size: 1.1rem; }
        .price-text { color: var(--primary-light); font-size: 1.4rem; font-weight: 800; }
        .btn-action { padding: 10px 20px; border-radius: 12px; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px; text-decoration: none; position: relative; z-index: 3; }
        .btn-view { background: rgba(99, 102, 241, 0.1); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.2); }
        .btn-view:hover { background: var(--primary); color: white; transform: translateY(-2px); }
        .btn-cancel-order { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .btn-cancel-order:hover { background: #ef4444; color: white; transform: translateY(-2px); }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>

    <div class="container py-5">
        <div class="mb-5 animate__animated animate__fadeInDown">
            <h1 class="display-5 fw-bold mb-2 text-white">ประวัติการสั่งซื้อ</h1>
            <p class="text-gray">ขอบคุณที่ไว้วางใจเลือกซื้อหนังสือกับ BookStore</p>
        </div>

        <?php
        $total_orders = count($orders);
        $total_spent = array_sum(array_column($orders, 'total_amount'));
        $pending = count(array_filter($orders, function($o) { return stripos($o['status'], 'รอ') !== false || stripos($o['status'], 'pending') !== false; }));
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-box-seam"></i></div>
                <div class="order-value text-white"><?php echo $total_orders; ?> รายการ</div>
                <div class="order-label">คำสั่งซื้อทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
                <div class="order-value text-white"><?php echo $pending; ?> รายการ</div>
                <div class="order-label">รอดำเนินการ</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-wallet2"></i></div>
                <div class="order-value text-white">฿<?php echo number_format($total_spent); ?></div>
                <div class="order-label">ยอดสะสมทั้งหมด</div>
            </div>
        </div>

        <div class="orders-container">
            <?php if ($total_orders > 0): ?>
                <?php foreach ($orders as $index => $row): 
                    // 🛠️ 1. ปรับการดึงค่าให้ครอบคลุม (ตัดช่องว่าง + ตัวเล็ก)
                    $status_raw = strtolower(trim($row['status']));
                    
                    // ค่า Default (กรณีรอตรวจสอบ)
                    $s_class = 'status-pending'; 
                    $s_icon = 'bi-clock';
                    $s_text = $row['status']; 

                    // 🛠️ 2. เช็คสถานะ "สำเร็จ" (ครอบคลุมทั้งไทย/อังกฤษ)
                    if (strpos($status_raw, 'paid') !== false || strpos($status_raw, 'shipped') !== false || strpos($status_raw, 'delivered') !== false || strpos($status_raw, 'success') !== false || strpos($status_raw, 'สำเร็จ') !== false) {
                        $s_class = 'status-success'; 
                        $s_icon = 'bi-check-circle-fill';
                        
                        if(strpos($status_raw, 'paid') !== false) $s_text = 'ชำระเงินแล้ว';
                        elseif(strpos($status_raw, 'shipped') !== false) $s_text = 'อยู่ระหว่างจัดส่ง';
                        else $s_text = 'จัดส่งสำเร็จ';
                    } 
                    // 🛠️ 3. เช็คสถานะ "ยกเลิก" (ครอบคลุมทั้งไทย/อังกฤษ/Emoji)
                    else if (strpos($status_raw, 'cancel') !== false || strpos($status_raw, 'ยกเลิก') !== false) {
                        $s_class = 'status-cancel'; 
                        $s_icon = 'bi-x-circle-fill';
                        $s_text = 'ถูกยกเลิก'; // <-- แก้ให้เป็นคำนี้ตามที่ขอ

                        // 🔥 ตรวจสอบว่าใครเป็นคนยกเลิก
                        if (isset($row['cancelled_by']) && $row['cancelled_by'] == 'admin') {
                            $s_class = 'status-admin-cancel';
                            $s_text = 'ร้านค้ายกเลิก (สินค้าหมด)';
                            $s_icon = 'bi-shop-window';
                        } elseif (isset($row['cancelled_by']) && $row['cancelled_by'] == 'user') {
                            $s_text = 'คุณยกเลิกรายการ';
                            $s_icon = 'bi-person-x';
                        }
                    }
                    
                    $date = date('d M Y, H:i', strtotime($row['created_at']));
                    $delay = $index * 0.1;
                ?>
                <div class="order-card" style="animation-delay: <?php echo $delay; ?>s;">
                    <div class="order-header">
                        <div class="order-id">เลขที่ออเดอร์ <span>#<?php echo str_pad($row['order_id'], 6, '0', STR_PAD_LEFT); ?></span></div>
                        
                        <span class="status-badge <?php echo $s_class; ?>">
                            <i class="bi <?php echo $s_icon; ?>"></i> 
                            <?php echo $s_text; ?>
                        </span>
                    </div>

                    <div class="order-grid">
                        <div>
                            <div class="order-label">วันที่สั่งซื้อ</div>
                            <div class="order-value"><?php echo $date; ?> น.</div>
                        </div>
                        <div>
                            <div class="order-label">ยอดรวมสุทธิ</div>
                            <div class="order-value price-text">฿<?php echo number_format($row['total_amount'], 2); ?></div>
                        </div>
                        <div class="d-flex align-items-center justify-content-md-end gap-2 mt-3 mt-md-0">
                            <a href="order_detail.php?id=<?php echo $row['order_id']; ?>" class="btn-action btn-view">
                                <i class="bi bi-file-text"></i> รายละเอียด / ใบเสร็จ
                            </a>
                            
                            <?php if (strpos($status_raw, 'รอ') !== false || strpos($status_raw, 'pending') !== false): ?>
                                <a href="cancel_order.php?id=<?php echo $row['order_id']; ?>" 
                                   class="btn-action btn-cancel-order" 
                                   onclick="return confirm('ยืนยันการยกเลิกคำสั่งซื้อนี้?')">
                                    <i class="bi bi-trash"></i> ยกเลิก
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 glass-card">
                    <i class="bi bi-bag-x display-1 text-secondary opacity-50"></i>
                    <h3 class="mt-3 fw-bold">ไม่พบประวัติการสั่งซื้อ</h3>
                    <p class="text-gray">มาเริ่มเติมตู้หนังสือของคุณกันเถอะ!</p>
                    <a href="index.php" class="btn btn-primary rounded-pill px-4 py-2 mt-2">
                        ไปเลือกซื้อหนังสือ
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- 🌌 Starfield Animation (คงเดิม) ---
        const canvas = document.getElementById('starfield');
        const ctx = canvas.getContext('2d');
        let stars = [];
        let width, height;

        function resize() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }

        class Star {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.size = Math.random() * 2;
                this.speedY = Math.random() * 0.5 + 0.1;
                this.opacity = Math.random();
                this.fadeDir = Math.random() > 0.5 ? 0.01 : -0.01;
            }
            update() {
                this.y -= this.speedY;
                if (this.y < 0) this.y = height;
                this.opacity += this.fadeDir;
                if (this.opacity > 1 || this.opacity < 0.2) this.fadeDir = -this.fadeDir;
            }
            draw() {
                ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity * 0.5})`;
                ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill();
            }
        }

        function initStars() {
            stars = [];
            for (let i = 0; i < 60; i++) stars.push(new Star());
        }

        function animateStars() {
            ctx.clearRect(0, 0, width, height);
            stars.forEach(star => { star.update(); star.draw(); });
            requestAnimationFrame(animateStars);
        }

        window.addEventListener('resize', () => { resize(); initStars(); });
        resize(); initStars(); animateStars();
    </script>
</body>
</html>
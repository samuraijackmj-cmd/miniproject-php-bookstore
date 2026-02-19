<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบไฟล์ QR Helper
if (file_exists('includes/qr_helper.php')) {
    require_once 'includes/qr_helper.php';
}

// 1. เช็ค Login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "กรุณาเข้าสู่ระบบก่อนทำการสั่งซื้อ";
    header("Location: login.php?redirect=checkout.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. ดึงสินค้าจาก Database
$stmt_cart = $conn->prepare("SELECT b.*, c.quantity as cart_qty 
                             FROM cart c 
                             JOIN books b ON c.book_id = b.book_id 
                             WHERE c.user_id = ?");
$stmt_cart->execute([$user_id]);
$products = $stmt_cart->fetchAll();

// 3. ถ้าตะกร้าว่าง ให้เด้งกลับหน้าแรก
if (count($products) == 0) {
    header("Location: index.php");
    exit;
}

// 4. ดึงข้อมูลผู้ใช้
$stmt_user = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// 5. คำนวณยอดรวม
$grand_total = 0;
$total_discount = 0;
$total_items = 0;
$order_items_data = [];

foreach ($products as $product) {
    $qty = $product['cart_qty'];
    $price = $product['price'];
    $discount = $product['discount_percent'];
    $final_unit_price = $price - ($price * $discount / 100);
    
    $subtotal = $final_unit_price * $qty;
    $original_subtotal = $price * $qty;
    $discount_amount = $original_subtotal - $subtotal;
    
    $grand_total += $subtotal;
    $total_discount += $discount_amount;
    $total_items += $qty;
    
    // เก็บข้อมูลไว้ใช้แสดงผลด้านล่าง
    $order_items_data[$product['book_id']] = [
        'final_price' => $final_unit_price,
        'subtotal' => $subtotal,
        'original_subtotal' => $original_subtotal,
        'qty' => $qty
    ];
}

$cod_fee = 50;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน | BookStore Premium</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bg-dark: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --accent-grad: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
        }

        /* --- 🔧 Z-Index Fix --- */
        nav.navbar { z-index: 1050 !important; position: sticky; top: 0; }
        .dropdown-menu { z-index: 1060 !important; }

        /* --- 🌌 BACKGROUND EFFECTS --- */
        .ambient-light {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%);
            pointer-events: none;
        }
        #starfield { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }

        /* --- 📑 UI Elements --- */
        .btn-back-nav {
            color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
            font-size: 1rem; margin-bottom: 20px; padding: 8px 16px; background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border); border-radius: 12px; transition: 0.3s; position: relative; z-index: 1;
        }
        .btn-back-nav:hover { background: rgba(255, 255, 255, 0.1); transform: translateX(-5px); color: var(--primary); }

        .modern-card {
            background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border);
            border-radius: 20px; padding: 2rem; margin-bottom: 1.5rem; position: relative; z-index: 1;
        }

        .card-header-text {
            font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem; color: white;
            display: flex; align-items: center; gap: 10px; padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .card-header-text i { color: var(--primary); font-size: 1.4rem; }

        .shipping-label { color: white !important; font-weight: 500; margin-bottom: 8px; display: block; }

        .input-group-modern {
            position: relative; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border);
            border-radius: 12px; display: flex; align-items: center; padding: 0 15px; transition: 0.3s; height: 54px;
        }
        .input-group-modern:focus-within { border-color: var(--primary); background: rgba(99, 102, 241, 0.05); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .input-icon { color: white; font-size: 1.1rem; min-width: 24px; }
        .input-modern { width: 100%; background: transparent; border: none; color: white; padding: 10px; font-size: 1rem; height: 100%; }
        .input-modern:focus { outline: none; }
        .input-modern::placeholder { color: rgba(255,255,255,0.3); }
        textarea.input-modern { height: auto; padding: 15px 10px; resize: none; }

        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .payment-radio { display: none; }
        .payment-selector {
            background: rgba(255, 255, 255, 0.03); border: 2px solid transparent; border-radius: 16px; padding: 20px;
            cursor: pointer; transition: 0.3s; display: flex; flex-direction: column; align-items: center; text-align: center;
        }
        .payment-radio:checked + .payment-selector { background: rgba(99, 102, 241, 0.15); border-color: var(--primary); box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3); }
        .pay-icon { font-size: 2.5rem; margin-bottom: 10px; color: var(--text-sub); transition: 0.3s; }
        .payment-radio:checked + .payment-selector .pay-icon { color: white; transform: scale(1.1); }
        .pay-title { font-weight: 700; font-size: 1.1rem; color: white; }

        .bank-details-box { background: rgba(0,0,0,0.2); border-radius: 16px; padding: 20px; display: none; animation: fadeIn 0.5s; }
        .bank-row-premium {
            display: flex; align-items: center; justify-content: space-between; padding: 20px;
            background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px; color: white; margin-bottom: 15px;
        }
        .bank-acc-number { margin: 5px 0; font-weight: 800; font-size: 1.6rem; letter-spacing: 2px; color: #38bdf8; }

        .upload-clean { border: 2px dashed rgba(255, 255, 255, 0.15); border-radius: 16px; padding: 25px; text-align: center; cursor: pointer; transition: 0.3s; color: white; background: rgba(255, 255, 255, 0.02); }
        .upload-clean:hover { border-color: var(--primary); background: rgba(99, 102, 241, 0.05); }

        .summary-item { display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .summary-img { width: 60px; height: 85px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
        .summary-total-box { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; margin-top: 20px; }
        .row-price { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem; color: var(--text-sub); }
        .row-price.final { margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 1.3rem; font-weight: 700; color: white; }
        .final-price { color: #818cf8; text-shadow: 0 0 15px rgba(99,102,241,0.4); }

        .btn-confirm { width: 100%; padding: 15px; font-size: 1.1rem; font-weight: 700; border-radius: 50px; background: var(--accent-grad); border: none; color: white; box-shadow: 0 5px 20px rgba(99, 102, 241, 0.3); transition: 0.3s; }
        .btn-confirm:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(99, 102, 241, 0.5); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>
    
    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>

    <div class="container py-5">
        <a href="cart.php" class="btn-back-nav animate__animated animate__fadeInLeft">
            <i class="bi bi-chevron-left"></i> กลับไปหน้าตะกร้า
        </a>

        <h2 class="fw-bold text-white text-center mb-4 animate__animated animate__fadeInDown">ยืนยันการสั่งซื้อ</h2>

        <form action="save_order.php" method="POST" enctype="multipart/form-data" id="checkoutForm">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="modern-card animate__animated animate__fadeInUp">
                        <div class="card-header-text"><i class="bi bi-box-seam"></i> ข้อมูลการจัดส่ง</div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="shipping-label">ชื่อ-นามสกุล ผู้รับ</label>
                                <div class="input-group-modern">
                                    <i class="bi bi-person input-icon"></i>
                                    <input type="text" class="input-modern" name="fullname" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="shipping-label">เบอร์โทรศัพท์</label>
                                <div class="input-group-modern">
                                    <i class="bi bi-phone input-icon"></i>
                                    <input type="tel" class="input-modern" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required pattern="[0-9]{10}">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="shipping-label">ที่อยู่จัดส่งสินค้า</label>
                                <div class="input-group-modern" style="height: auto; align-items: flex-start;">
                                    <i class="bi bi-geo-alt input-icon mt-3"></i>
                                    <textarea class="input-modern" name="address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modern-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                        <div class="card-header-text"><i class="bi bi-wallet2"></i> เลือกวิธีชำระเงิน</div>
                        <div class="payment-grid">
                            <label>
                                <input type="radio" name="payment_method" value="bank_transfer" class="payment-radio" checked onchange="togglePayment('bank')">
                                <div class="payment-selector">
                                    <i class="bi bi-qr-code-scan pay-icon"></i>
                                    <div class="pay-title">โอนเงิน / QR Code</div>
                                </div>
                            </label>
                            <label>
                                <input type="radio" name="payment_method" value="cod" class="payment-radio" onchange="togglePayment('cod')">
                                <div class="payment-selector">
                                    <i class="bi bi-cash-stack pay-icon"></i>
                                    <div class="pay-title">เก็บเงินปลายทาง</div>
                                </div>
                            </label>
                        </div>

                        <div id="bankSection" class="bank-details-box" style="display: block;">
                            <div class="bank-row-premium">
                                <div>
                                    <div class="bank-label small opacity-75">ธนาคารกสิกรไทย (KBank)</div>
                                    <h5 class="bank-acc-number mb-0">123-4-56789-0</h5>
                                    <div class="bank-acc-name">บจก. บุ๊คสโตร์ ออนไลน์</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="copyToClipboard('1234567890')">คัดลอก</button>
                            </div>

                            <div class="row align-items-center mt-3">
                                <div class="col-md-6 text-center border-end border-secondary border-opacity-25">
                                    <?php if (class_exists('QRCodeGenerator')): ?>
                                        <div class="bg-white p-2 rounded-4 d-inline-block">
                                            <?php $qr_code_url = QRCodeGenerator::generatePromptPayQR("0812345678", $grand_total); ?>
                                            <img src="<?php echo $qr_code_url; ?>" width="160">
                                        </div>
                                        <div class="small text-white mt-3 fw-bold">สแกน QR ฿<?php echo number_format($grand_total, 2); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="upload-clean w-100" for="slipInput">
                                        <div id="uploadText">
                                            <i class="bi bi-cloud-arrow-up fs-1 text-primary"></i>
                                            <div class="fw-bold mt-2">คลิกเพื่อแนบสลิป</div>
                                        </div>
                                        <img id="slipPreview" style="max-width: 100%; max-height: 180px; display: none; margin: 0 auto; border-radius: 12px;">
                                        <input type="file" name="slip" id="slipInput" hidden accept="image/*" onchange="previewSlip(this)">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="codSection" class="alert alert-warning mt-3" style="display: none;">
                            <i class="bi bi-info-circle me-2"></i> เตรียมเงินสดชำระหน้าบ้านเมื่อได้รับสินค้า
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="modern-card sticky-top animate__animated animate__fadeInRight" style="top: 100px;">
                        <div class="card-header-text"><i class="bi bi-bag-check"></i> สรุปคำสั่งซื้อ</div>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php foreach ($products as $product): 
                                $qty = $product['cart_qty']; 
                                $info = $order_items_data[$product['book_id']];
                                $hasDiscount = ($product['discount_percent'] > 0);
                            ?>
                            <div class="summary-item">
                                <img src="uploads/<?php echo $product['image']; ?>" class="summary-img" onerror="this.src='assets/no-image.png'">
                                <div class="summary-info flex-grow-1">
                                    <h6 class="mb-1 text-white"><?php echo htmlspecialchars($product['title']); ?></h6>
                                    <p class="mb-0 small text-white-50">จำนวน: <?php echo $qty; ?> เล่ม</p>
                                    
                                    <div class="mt-1">
                                        <?php if($hasDiscount): ?>
                                            <span class="text-decoration-line-through text-white-50 me-2" style="font-size: 0.85rem;">
                                                ฿<?php echo number_format($product['price'] * $qty); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="fw-bold text-primary">
                                            ฿<?php echo number_format($info['subtotal']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-total-box">
                            <div class="row-price">
                                <span>ยอดรวมสินค้า</span>
                                <span>฿<?php echo number_format($grand_total + $total_discount); ?></span>
                            </div>
                            
                            <?php if($total_discount > 0): ?>
                                <div class="row-price text-danger">
                                    <span>ส่วนลดทั้งหมด</span>
                                    <span>-฿<?php echo number_format($total_discount); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="row-price text-warning" id="codRow" style="display: none;"><span>ค่าธรรมเนียม COD</span><span>+฿<?php echo $cod_fee; ?></span></div>
                            <div class="row-price text-success"><span>ค่าจัดส่ง</span><span>ฟรี</span></div>
                            
                            <div class="row-price final">
                                <span>ยอดสุทธิ</span>
                                <span class="final-price" id="finalPriceDisplay">฿<?php echo number_format($grand_total); ?></span>
                            </div>
                        </div>

                        <input type="hidden" name="total_amount" id="totalAmountInput" value="<?php echo $grand_total; ?>">
                        <button type="submit" class="btn-confirm mt-4">ยืนยันการสั่งซื้อ</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- 🌌 Starfield Logic ---
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

        // --- 🛒 Payment Logic ---
        const baseTotal = <?php echo $grand_total; ?>;
        const codFee = <?php echo $cod_fee; ?>;

        function togglePayment(method) {
            const bank = document.getElementById('bankSection');
            const cod = document.getElementById('codSection');
            const codRow = document.getElementById('codRow');
            const display = document.getElementById('finalPriceDisplay');
            const input = document.getElementById('totalAmountInput');
            const slipInput = document.getElementById('slipInput');

            if (method === 'bank') {
                bank.style.display = 'block'; cod.style.display = 'none'; codRow.style.display = 'none';
                display.innerText = '฿' + baseTotal.toLocaleString();
                input.value = baseTotal;
                slipInput.required = true;
            } else {
                bank.style.display = 'none'; cod.style.display = 'block'; codRow.style.display = 'flex';
                let total = baseTotal + codFee;
                display.innerText = '฿' + total.toLocaleString();
                input.value = total;
                slipInput.required = false;
            }
        }

        function previewSlip(input) {
            const preview = document.getElementById('slipPreview');
            const text = document.getElementById('uploadText');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; text.style.display = 'none'; }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('คัดลอกเลขบัญชีแล้ว');
        }
    </script>
</body>
</html>
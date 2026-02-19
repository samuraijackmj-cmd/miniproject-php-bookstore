<?php
session_start();
require_once 'config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }

$id = intval($_GET['id']);

// 1. ดึงข้อมูลหนังสือ
$stmt = $conn->prepare("SELECT books.*, categories.category_name 
                        FROM books 
                        LEFT JOIN categories ON books.category_id = categories.category_id 
                        WHERE book_id = :id");
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) { header("Location: index.php"); exit; }

// 2. ดึงหนังสือที่เกี่ยวข้อง
$related_stmt = $conn->prepare("SELECT * FROM books WHERE category_id = ? AND book_id != ? ORDER BY RAND() LIMIT 4");
$related_stmt->execute([$book['category_id'], $id]);
$related_books = $related_stmt->fetchAll();

// --- คำนวณราคา ---
$original_price = $book['price'];
$percent = intval($book['discount_percent']);
$is_sale = ($percent > 0);
$final_price = $original_price - ($original_price * $percent / 100);
$has_stock = ($book['stock_quantity'] > 0);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> | BookStore Premium</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --accent: #f43f5e;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
            --ease-elastic: cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* --- 🌌 Background Effects (Starfield) --- */
        .ambient-light {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%);
            pointer-events: none;
        }
        #starfield {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none;
        }

        /* --- 🖼️ Image Showcase --- */
        .glass-showcase {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 30px;
            padding: 40px;
            text-align: center;
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .product-img {
            width: 100%; max-width: 350px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
            transition: 0.5s var(--ease-elastic);
        }
        .glass-showcase:hover .product-img {
            transform: scale(1.05) translateY(-10px);
            box-shadow: 0 25px 60px rgba(99, 102, 241, 0.3);
        }

        /* Sold Out Style */
        .glass-showcase.sold-out .product-img { filter: grayscale(1) opacity(0.5); }
        .sold-out-badge-large {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-15deg);
            background: #ef4444; color: white; padding: 15px 40px; font-size: 1.5rem; font-weight: 800;
            border: 4px solid white; border-radius: 50px; box-shadow: 0 20px 50px rgba(239, 68, 68, 0.5); z-index: 10;
        }

        /* --- 📝 Content Detail --- */
        .category-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 20px; background: rgba(99, 102, 241, 0.15);
            color: #818cf8; border-radius: 50px; border: 1px solid rgba(99, 102, 241, 0.3);
            font-weight: 500; margin-bottom: 20px;
        }

        .price-display {
            font-size: 3.5rem; font-weight: 800;
            background: linear-gradient(135deg, #fff 30%, #a5b4fc 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        .old-price-display {
            font-size: 1.2rem; text-decoration: line-through; color: var(--text-sub); opacity: 0.6;
        }

        /* Info Boxes */
        .info-box {
            background: rgba(255, 255, 255, 0.03); border: var(--glass-border);
            border-radius: 20px; padding: 20px; text-align: center; transition: 0.3s;
        }
        .info-box:hover { background: rgba(255, 255, 255, 0.07); transform: translateY(-5px); border-color: rgba(255,255,255,0.2); }
        
        /* Action Button */
        .btn-add-large {
            background: linear-gradient(135deg, var(--primary), #a855f7);
            border: none; color: white; width: 100%; padding: 20px;
            font-size: 1.2rem; font-weight: 700; border-radius: 20px;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
            transition: 0.3s; margin-top: 30px;
        }
        .btn-add-large:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 50px rgba(168, 85, 247, 0.6);
        }
        .btn-add-large.disabled {
            background: #334155; opacity: 0.5; box-shadow: none; cursor: not-allowed;
        }

        /* --- 🍞 Modern Box Toast (Square Center) --- */
        #toast-box {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999;
        }
        .modern-box-toast {
            background: rgba(15, 23, 42, 0.95); width: 260px; padding: 30px 20px;
            border-radius: 24px; border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 40px 80px rgba(0,0,0,0.8);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; backdrop-filter: blur(15px);
        }
        .toast-icon-circle {
            width: 60px; height: 60px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin-bottom: 15px; color: white;
        }
        .success-theme .toast-icon-circle { background: rgba(16, 185, 129, 0.2); color: #10b981; box-shadow: 0 0 30px rgba(16, 185, 129, 0.4); }
        .error-theme .toast-icon-circle { background: rgba(239, 68, 68, 0.2); color: #ef4444; box-shadow: 0 0 30px rgba(239, 68, 68, 0.4); }

        /* Related Books */
        .related-card {
            display: block; text-decoration: none; padding: 15px;
            background: rgba(255, 255, 255, 0.02); border: var(--glass-border);
            border-radius: 20px; transition: 0.3s;
        }
        .related-card:hover {
            background: rgba(255, 255, 255, 0.06); transform: translateY(-8px);
            border-color: var(--primary);
        }
        .related-img { width: 100%; border-radius: 12px; margin-bottom: 10px; aspect-ratio: 2/3; object-fit: cover; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>
    
    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>
    
    <div id="toast-box"></div>

    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-5 animate__animated animate__fadeIn">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-white-50 text-decoration-none hover-white">หน้าแรก</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">รายละเอียดหนังสือ</li>
            </ol>
        </nav>

        <div class="row g-5">
            <div class="col-lg-5 animate__animated animate__fadeInLeft">
                <div class="glass-showcase <?php echo !$has_stock ? 'sold-out' : ''; ?>">
                    <?php if($is_sale && $has_stock): ?>
                        <div class="position-absolute top-0 start-0 m-4 z-3">
                            <span class="badge bg-danger rounded-pill px-3 py-2 fs-6 shadow-lg">-<?php echo $percent; ?>%</span>
                        </div>
                    <?php endif; ?>

                    <?php if(!$has_stock): ?>
                        <div class="sold-out-badge-large">สินค้าหมด</div>
                    <?php endif; ?>

                    <img src="uploads/<?php echo $book['image']; ?>" class="product-img" onerror="this.src='assets/no-image.png'">
                </div>
            </div>

            <div class="col-lg-7 animate__animated animate__fadeInRight" style="animation-delay: 0.1s;">
                <div class="ps-lg-4">
                    <div class="category-badge">
                        <i class="bi bi-bookmark-fill"></i> <?php echo htmlspecialchars($book['category_name']); ?>
                    </div>
                    
                    <h1 class="fw-bold display-5 mb-3 text-white"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <p class="text-white-50 fs-5 mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-feather text-primary"></i> <?php echo htmlspecialchars($book['author']); ?>
                    </p>

                    <div class="d-flex align-items-end gap-3 mb-5 border-bottom border-white border-opacity-10 pb-4">
                        <div class="price-display">฿<?php echo number_format($final_price); ?></div>
                        <?php if($is_sale): ?>
                            <div class="old-price-display mb-3">฿<?php echo number_format($original_price); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-4">
                            <div class="info-box">
                                <i class="bi bi-upc-scan text-primary fs-3 mb-2 d-block"></i>
                                <div class="small text-white-50">รหัสสินค้า</div>
                                <div class="fw-bold mt-1">BK-<?php echo str_pad($book['book_id'], 5, '0', STR_PAD_LEFT); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="info-box">
                                <i class="bi bi-box-seam text-primary fs-3 mb-2 d-block"></i>
                                <div class="small text-white-50">สถานะสต็อก</div>
                                <div class="fw-bold mt-1 <?php echo $has_stock ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $has_stock ? 'พร้อมส่ง' : 'หมดชั่วคราว'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold text-white mb-3"><i class="bi bi-justify-left text-primary me-2"></i>รายละเอียด</h5>
                        <p class="text-white-50" style="line-height: 1.8; font-size: 1.05rem;">
                            <?php echo nl2br(htmlspecialchars($book['description'] ?: 'ยังไม่มีข้อมูลรายละเอียดของหนังสือเล่มนี้')); ?>
                        </p>
                    </div>

                    <?php if($has_stock): ?>
                        <button class="btn-add-large" onclick="addToCartAjax(this, <?php echo $book['book_id']; ?>, '<?php echo addslashes($book['title']); ?>')">
                            <i class="bi bi-cart-plus-fill me-2"></i> เพิ่มลงตะกร้า
                        </button>
                    <?php else: ?>
                        <button class="btn-add-large disabled" onclick="showToast('สินค้าหมด', 'สินค้ารายการนี้หมดชั่วคราว', 'error')">
                            <i class="bi bi-bell-slash me-2"></i> สินค้าหมดชั่วคราว
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if(count($related_books) > 0): ?>
        <div class="mt-5 pt-5 border-top border-white border-opacity-10 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <h3 class="fw-bold text-white mb-4"><i class="bi bi-stars text-warning me-2"></i>คุณอาจสนใจสิ่งนี้</h3>
            <div class="row g-4">
                <?php foreach($related_books as $rb): 
                    $rb_final = $rb['price'] - ($rb['price'] * $rb['discount_percent'] / 100); ?>
                <div class="col-6 col-md-3">
                    <a href="product_detail.php?id=<?php echo $rb['book_id']; ?>" class="related-card">
                        <img src="uploads/<?php echo $rb['image']; ?>" class="related-img" onerror="this.src='assets/no-image.png'">
                        <h6 class="text-white fw-bold text-truncate mb-1"><?php echo htmlspecialchars($rb['title']); ?></h6>
                        <div class="text-primary fw-bold">฿<?php echo number_format($rb_final); ?></div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
        // --- 🌌 Starfield Animation Script ---
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
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
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

        // --- 🛒 Cart Logic with Navbar Sync ---
        async function addToCartAjax(btn, id, title) {
            if ($(btn).hasClass('loading')) return;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังเพิ่ม...';
            $(btn).addClass('loading');

            try {
                const res = await fetch(`cart_action.php?action=add&id=${id}&ajax=1`);
                const data = await res.json();

                if (data.status === 'success') {
                    confetti({ particleCount: 80, spread: 80, origin: { y: 0.7 }, colors: ['#6366f1', '#a855f7', '#ffffff'] });
                    
                    // ✅ แก้ไข: เรียกฟังก์ชันอัปเดตตัวเลขใน Navbar (เด้งดึ๋งทันที)
                    if (typeof updateCartCount === "function") {
                        updateCartCount(data.cart_count);
                    }

                    // Show Modern Toast
                    showToast('สำเร็จ', `เพิ่ม "${title}" ลงตะกร้าแล้ว`);
                    
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> เรียบร้อย';
                    btn.style.background = '#10b981';
                    
                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.style.background = '';
                        $(btn).removeClass('loading');
                    }, 2000);
                } else {
                    showToast('ขออภัย', data.message, 'error');
                    btn.innerHTML = originalContent;
                    $(btn).removeClass('loading');
                }
            } catch (e) {
                console.error(e);
                btn.innerHTML = originalContent;
                $(btn).removeClass('loading');
                showToast('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error');
            }
        }

        // --- 🍞 Modern Box Toast Function ---
        function showToast(title, message, type = 'success') {
            $('#toast-box').empty();
            const id = 'toast-' + Date.now();
            const icon = type === 'success' ? 'bi-check-lg' : 'bi-x-lg';
            const themeClass = type === 'success' ? 'success-theme' : 'error-theme';
            
            const html = `
            <div id="${id}" class="modern-box-toast ${themeClass} animate__animated animate__fadeInUp">
                <div class="toast-icon-circle">
                    <i class="bi ${icon}"></i>
                </div>
                <div class="fw-bold fs-5 mb-1">${title}</div>
                <div class="small opacity-75">${message}</div>
            </div>`;
            
            $('#toast-box').append(html);
            
            setTimeout(() => {
                $(`#${id}`).removeClass('animate__fadeInUp').addClass('animate__fadeOutDown');
                setTimeout(() => $(`#${id}`).remove(), 500);
            }, 2500);
        }
    </script>
</body>
</html>
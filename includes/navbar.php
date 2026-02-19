<?php
// 1. เริ่ม Session ถ้ายังไม่มี
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------------
// 🔧 ส่วนแก้ไขสำคัญ: เชื่อมต่อฐานข้อมูลด้วย Absolute Path (แก้ปัญหาหาไฟล์ไม่เจอ)
// ------------------------------------------------------------------------
// หาตำแหน่งโฟลเดอร์หลักของโปรเจกต์ (ถอยจาก includes ออกมา 1 ขั้น)
$project_root = dirname(__DIR__);
$db_file = $project_root . '/config/db.php';

// เชื่อมต่อฐานข้อมูล
if (!isset($conn)) {
    if (file_exists($db_file)) {
        require_once $db_file;
    }
}

// 2. คำนวณจำนวนสินค้าในตะกร้า
$cart_count = 0;

if (isset($_SESSION['user_id'])) {
    // 🟢 กรณีล็อกอิน: นับจาก Database
    if (isset($conn) && $conn instanceof PDO) {
        try {
            $stmt_count = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
            $stmt_count->execute([$_SESSION['user_id']]);
            $result = $stmt_count->fetchColumn();
            $cart_count = $result ? intval($result) : 0;
        } catch (Exception $e) { 
            $cart_count = 0; 
        }
    }
} else {
    // 🟠 กรณีไม่ล็อกอิน: นับจาก Session
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $cart_count = array_sum($_SESSION['cart']);
    }
}

// 3. ดึงข้อมูล Profile
$nav_user_img = null;
$nav_display_name = "";

if (isset($_SESSION['user_id']) && isset($conn) && $conn instanceof PDO) {
    try {
        $stmt_nav = $conn->prepare("SELECT profile_image, username, full_name, role FROM users WHERE user_id = ?");
        $stmt_nav->execute([$_SESSION['user_id']]);
        $nav_user_data = $stmt_nav->fetch();
        if ($nav_user_data) {
            $nav_user_img = $nav_user_data['profile_image'];
            $nav_display_name = !empty($nav_user_data['full_name']) ? $nav_user_data['full_name'] : $nav_user_data['username'];
        }
    } catch (Exception $e) { }
}

// --- จัดการ Link และ Path รูปภาพ (ให้ใช้ได้ทั้งหน้าบ้านและหน้า Admin) ---
$is_admin_page = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
$base_path = $is_admin_page ? '../' : ''; 

$link_home = $base_path . 'index.php';
$link_cart = $base_path . 'cart.php';
$link_login = $base_path . 'login.php';

// ✅ แก้ไขตรงนี้: เปลี่ยนจาก register.php เป็น login.php?action=signup
$link_register = $base_path . 'login.php?action=signup'; 

$link_profile = $base_path . 'profile.php';
$link_address = $base_path . 'manage_address.php';
$link_history = $base_path . 'order_history.php';
$link_logout = $base_path . 'logout.php';
$link_admin_dashboard = $is_admin_page ? 'dashboard.php' : 'admin/dashboard.php';

$profile_img_src = $base_path . 'uploads/profiles/' . ($nav_user_img ?: 'default.png');
$default_img_src = $base_path . 'assets/default-profile.png';
?>

<style>
    :root {
        --p-indigo: #6366f1;
        --p-indigo-glow: rgba(99, 102, 241, 0.5);
        --nav-dark: #0f172a;
        --glass-border: 1px solid rgba(255, 255, 255, 0.08);
        --glass-bg: rgba(15, 23, 42, 0.85);
        --ease: cubic-bezier(0.22, 1, 0.36, 1);
    }

    /* 🌌 Navbar Priority & Glass Effect */
    .navbar-glass {
        background: var(--glass-bg) !important;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: var(--glass-border);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        padding: 12px 0;
        z-index: 1050 !important;
        transition: 0.3s;
    }

    /* ✨ Brand Logo Gradient */
    .brand-gradient {
        background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 0 20px rgba(165, 180, 252, 0.3);
    }

    /* 🔍 Search Bar */
    .search-pill {
        background: rgba(255, 255, 255, 0.03);
        border: var(--glass-border);
        border-radius: 50px;
        padding: 6px 20px;
        transition: all 0.3s var(--ease);
        max-width: 450px;
        width: 100%;
        display: flex;
        align-items: center;
    }
    .search-pill:focus-within {
        border-color: var(--p-indigo);
        background: rgba(15, 23, 42, 0.9);
        box-shadow: 0 0 25px rgba(99, 102, 241, 0.15);
        transform: scale(1.02);
    }
    .search-pill input { color: #fff !important; font-size: 0.95rem; font-weight: 300; }
    .search-pill input::placeholder { color: rgba(255, 255, 255, 0.4); }

    /* 👤 User Pill */
    .user-pill {
        background: rgba(255, 255, 255, 0.03);
        border: var(--glass-border);
        border-radius: 50px;
        padding: 4px 16px 4px 4px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: 0.3s var(--ease);
    }
    .user-pill:hover, .user-pill[aria-expanded="true"] {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .nav-avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.1);
        transition: 0.3s;
    }
    .user-pill:hover .nav-avatar { border-color: var(--p-indigo); }

    /* ✅ CSS นี้ซ่อนลูกศรสามเหลี่ยมอันเกินออกครับ */
    .dropdown-toggle::after { display: none !important; }

    /* ✨ Dropdown Menu */
    .dropdown-menu.custom-dropdown {
        background: #1e293b !important;
        border: var(--glass-border) !important;
        border-radius: 16px !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5) !important;
        padding: 8px !important;
        margin-top: 15px !important;
        opacity: 0; visibility: hidden; transform: translateY(10px);
        transition: all 0.2s var(--ease);
        display: block;
    }
    .dropdown-menu.custom-dropdown.show {
        opacity: 1; visibility: visible; transform: translateY(0);
    }
    .dropdown-item {
        color: #94a3b8 !important;
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 0.9rem;
        display: flex; align-items: center; gap: 12px;
    }
    .dropdown-item:hover {
        background: rgba(99, 102, 241, 0.1) !important;
        color: #fff !important;
        transform: translateX(3px);
    }
    .dropdown-item i { width: 20px; text-align: center; color: var(--p-indigo); }

    /* 🛒 Cart Icon */
    .nav-cart-btn {
        position: relative;
        width: 45px; height: 45px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        color: rgba(255,255,255,0.7);
        transition: 0.3s;
        border: 1px solid transparent;
        text-decoration: none;
    }
    .nav-cart-btn:hover {
        background: rgba(255,255,255,0.05);
        color: #fff;
        border-color: rgba(255,255,255,0.1);
    }

    /* 🚪 Auth Buttons */
    .btn-auth-login {
        background: var(--p-indigo);
        border: none;
        box-shadow: 0 0 0 rgba(99, 102, 241, 0);
        transition: 0.3s;
        position: relative; overflow: hidden;
    }
    .btn-auth-login:hover {
        background: #5558e6;
        box-shadow: 0 0 20px var(--p-indigo-glow);
        transform: translateY(-1px);
    }
    
    .btn-auth-register {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: rgba(255,255,255,0.9);
        transition: 0.3s;
    }
    .btn-auth-register:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: #fff;
        color: #fff;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-glass sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $link_home; ?>">
            <div style="background: rgba(99, 102, 241, 0.2); border-radius: 12px; padding: 6px;">
                <i class="bi bi-book-half fs-4 text-white"></i>
            </div>
            <span class="fs-4 fw-bold brand-gradient" style="letter-spacing: 0.5px;">BookStore</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none opacity-75 hover-opacity-100" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <i class="bi bi-list fs-1 text-white"></i>
        </button>

        <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navContent">
            <form class="d-flex mx-auto search-pill mb-3 mb-lg-0" onsubmit="return false;">
                <i class="bi bi-search text-white opacity-50 me-2"></i>
                <input class="form-control bg-transparent border-0 text-white shadow-none py-1 ps-0" 
                       type="search" id="searchInput" 
                       placeholder="ค้นหาหนังสือ..." autocomplete="off">
            </form>

            <div class="d-flex align-items-center justify-content-lg-end justify-content-between gap-3 gap-lg-4">
                
                <a class="nav-cart-btn" href="<?php echo $link_cart; ?>">
                    <i class="bi bi-bag-heart fs-4"></i>
                    
                    <span id="cart-count-badge" 
                          class="badge rounded-pill bg-danger position-absolute top-0 end-0 translate-middle-y shadow-sm" 
                          style="font-size: 0.65rem; border: 2px solid var(--nav-dark); <?php echo ($cart_count > 0) ? '' : 'display:none;'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <div class="user-pill dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                            <img src="<?php echo $profile_img_src; ?>" class="nav-avatar" onerror="this.src='<?php echo $default_img_src; ?>'">
                            <div class="text-start d-none d-sm-block pe-2">
                                <div class="text-white-50 lh-1" style="font-size: 0.65rem; margin-bottom: 2px;">สวัสดีคุณ</div>
                                <div class="fw-bold text-white small text-truncate" style="max-width: 100px; line-height: 1;">
                                    <?php echo htmlspecialchars($nav_display_name); ?>
                                </div>
                            </div>
                            <i class="bi bi-chevron-down small opacity-50 text-white"></i>
                        </div>

                        <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="dropdownUser">
                            <li><span class="dropdown-header small text-uppercase opacity-50 mb-1 px-3" style="font-size: 0.7rem;">เมนูสมาชิก</span></li>
                            <li><a class="dropdown-item" href="<?php echo $link_profile; ?>"><i class="bi bi-person"></i> โปรไฟล์</a></li>
                            <li><a class="dropdown-item" href="<?php echo $link_address; ?>"><i class="bi bi-geo-alt"></i> ที่อยู่จัดส่ง</a></li>
                            <li><a class="dropdown-item" href="<?php echo $link_history; ?>"><i class="bi bi-receipt"></i> ประวัติคำสั่งซื้อ</a></li>
                            
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li><hr class="dropdown-divider opacity-10 my-1"></li>
                                <li><a class="dropdown-item text-warning fw-bold" href="<?php echo $link_admin_dashboard; ?>"><i class="bi bi-speedometer2"></i> แดชบอร์ดแอดมิน</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider opacity-10 my-1"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $link_logout; ?>"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
                        </ul>
                    </div>

                <?php else: ?>
                    <div class="d-flex align-items-center gap-2">
                         <a href="<?php echo $link_login; ?>" class="btn btn-primary btn-auth-login rounded-pill px-4 fw-bold" style="font-size: 0.9rem;">
                            เข้าสู่ระบบ
                        </a>
                        <a href="<?php echo $link_register; ?>" class="btn btn-auth-register rounded-pill px-3 fw-bold text-decoration-none" style="font-size: 0.9rem;">
                            สมัครสมาชิก
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    // ฟังก์ชันนี้จะถูกเรียกใช้เมื่อมีการอัปเดตตะกร้าผ่าน AJAX
    function updateCartCount(count) {
        const badge = document.getElementById('cart-count-badge');
        if (badge) {
            badge.innerText = count;
            if (count > 0) {
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
            
            // Animation
            badge.classList.add('animate__animated', 'animate__heartBeat');
            setTimeout(() => {
                badge.classList.remove('animate__animated', 'animate__heartBeat');
            }, 1000);
        }
    }
</script>
<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db.php';

// --- [LOGIC] ดึงข้อมูลสถิติ ---
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = :today AND status != 'cancelled'");
$stmt->execute([':today' => $today]);
$today_sales = $stmt->fetch()['total'] ?? 0;

$this_month = date('Y-m');
$stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = :month AND status != 'cancelled'");
$stmt->execute([':month' => $this_month]);
$month_sales = $stmt->fetch()['total'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')");
$pending_orders = $stmt->fetch()['count'];

$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch()['count'];
$low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$total_alerts = $pending_orders + $low_stock_count;

// กราฟ
$dates = []; $sales_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = :date AND status != 'cancelled'");
    $stmt->execute([':date' => $date]);
    $total = $stmt->fetch()['total'] ?? 0;
    $dates[] = date('d/m', strtotime($date));
    $sales_data[] = (float)$total;
}

// สินค้าใกล้หมด
$low_stock_items = $conn->query("SELECT book_id, title, image, stock_quantity FROM books WHERE stock_quantity < 5 ORDER BY stock_quantity ASC LIMIT 4")->fetchAll();

// สินค้าขายดี
$top_selling = $conn->query("SELECT b.title, b.image, SUM(oi.quantity) as total_qty, SUM(oi.quantity * oi.price) as revenue FROM order_items oi JOIN books b ON oi.book_id = b.book_id JOIN orders o ON oi.order_id = o.order_id WHERE o.status != 'cancelled' GROUP BY oi.book_id ORDER BY total_qty DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Management Console</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600&display=swap');
        
        :root {
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --primary: #6366f1;
            --secondary: #a855f7;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Kanit', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #ffffff !important; /* Force White Text */
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.15) 0%, transparent 40%);
            min-height: 100vh;
        }

        /* --- Force Text White Overrides --- */
        h1, h2, h3, h4, h5, h6, p, span, div, a, li, td, th { color: #ffffff; }
        .text-muted { color: #cbd5e1 !important; } /* Lighter gray for readability on dark */
        .text-danger { color: #f87171 !important; }
        .text-warning { color: #fbbf24 !important; }
        .text-success { color: #34d399 !important; }
        .text-primary { color: #818cf8 !important; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-body); }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        /* Glass Cards */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        .glass-card:hover { transform: translateY(-5px); border-color: rgba(99, 102, 241, 0.4); }

        /* Sidebar */
        .admin-sidebar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--glass-border);
            min-height: 100vh;
            padding: 2rem 1.5rem;
            position: fixed;
            width: 280px;
            z-index: 1000;
            transition: 0.3s;
        }
        .main-content { margin-left: 280px; padding: 2rem; transition: 0.3s; }

        .nav-link {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; color: #cbd5e1 !important;
            border-radius: 16px; margin-bottom: 8px;
            transition: 0.3s; font-weight: 500;
        }
        .nav-link:hover { color: #fff !important; background: rgba(255, 255, 255, 0.1); padding-left: 24px; }
        .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white !important; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        /* Stats Box */
        .stat-box {
            position: relative; padding: 24px; border-radius: 20px;
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.6), rgba(30, 41, 59, 0.3));
            border: 1px solid var(--glass-border);
            overflow: hidden; transition: 0.3s;
        }
        .stat-box:hover { border-color: var(--primary); transform: translateY(-3px); }
        .stat-icon-bg {
            width: 56px; height: 56px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; background: rgba(255,255,255,0.1);
            position: absolute; right: 20px; top: 20px;
        }

        /* Badges */
        .status-badge { padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .st-success { background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3); }
        .st-warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .st-danger { background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }

        /* Dropdown fixes */
        .dropdown-menu-dark {
            background-color: #1e293b;
            border-color: rgba(255,255,255,0.1);
        }
        .dropdown-item { color: #cbd5e1; }
        .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: #fff; }

        @media (max-width: 991px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="admin-sidebar d-flex flex-column" id="sidebar">
    <div class="mb-5 px-2 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary rounded-3 p-2 d-flex align-items-center justify-content-center text-white shadow-lg">
                <i class="bi bi-book-half fs-5"></i>
            </div>
            <div>
                <h5 class="m-0 fw-bold">BOOK<span class="text-primary">STORE</span></h5>
                <span class="text-muted small">Admin Console</span>
            </div>
        </div>
        <button class="btn btn-link text-white d-lg-none" onclick="toggleSidebar()"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="text-muted small fw-bold mb-3 px-3">MENU</div>
    <nav class="nav flex-column gap-1 mb-auto">
        <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="orders.php" class="nav-link">
            <i class="bi bi-cart-check-fill"></i> Orders
            <?php if($pending_orders > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_orders; ?></span>
            <?php endif; ?>
        </a>
        <a href="books.php" class="nav-link"><i class="bi bi-journal-album"></i> Products</a>
        <a href="categories.php" class="nav-link"><i class="bi bi-bookmarks-fill"></i> Categories</a>
        <a href="users.php" class="nav-link"><i class="bi bi-people-fill"></i> Customers</a>
    </nav>

    <div class="mt-4">
        <a href="../index.php" class="btn btn-dark border-secondary w-100 py-3 rounded-4 d-flex align-items-center justify-content-center gap-2 hover-scale">
            <i class="bi bi-shop text-primary"></i>
            <span class="fw-bold">Go to Storefront</span>
        </a>
    </div>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5" style="position: relative; z-index: 100;">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-dark border-secondary d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <h2 class="fw-bold m-0 text-white">Dashboard</h2>
                <p class="text-muted m-0 small">Overview & Statistics</p>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            
            <div class="dropdown">
                <button class="btn btn-dark rounded-circle border-secondary text-white position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 45px; height: 45px;">
                    <i class="bi bi-bell-fill"></i>
                    <?php if($total_alerts > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark">
                            <?php echo $total_alerts; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-secondary mt-2 p-2" style="width: 300px;">
                    <li><h6 class="dropdown-header text-white border-bottom border-secondary pb-2 mb-2">Notifications</h6></li>
                    <?php if($low_stock_count > 0): ?>
                        <li><a class="dropdown-item rounded-2 text-danger small py-2" href="books.php"><i class="bi bi-exclamation-circle me-2"></i> สินค้าใกล้หมด <?php echo $low_stock_count; ?> รายการ</a></li>
                    <?php endif; ?>
                    <?php if($pending_orders > 0): ?>
                        <li><a class="dropdown-item rounded-2 text-warning small py-2" href="orders.php"><i class="bi bi-clock me-2"></i> ออเดอร์รอตรวจสอบ <?php echo $pending_orders; ?> รายการ</a></li>
                    <?php endif; ?>
                    <?php if($total_alerts == 0): ?>
                        <li><span class="dropdown-item-text text-muted small text-center d-block">ไม่มีการแจ้งเตือนใหม่</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-transparent border-0 p-1 d-flex align-items-center gap-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="text-end d-none d-md-block">
                        <div class="fw-bold text-white small">Administrator</div>
                        <div class="text-muted" style="font-size: 0.7rem;">System Manager</div>
                    </div>
                    <div class="rounded-circle bg-gradient text-white d-flex align-items-center justify-content-center fw-bold shadow-sm border border-secondary" 
                         style="width: 45px; height: 45px; background: linear-gradient(135deg, #6366f1, #a855f7);">
                        AD
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-secondary mt-3">
                    <li><h6 class="dropdown-header text-muted">Account Management</h6></li>
                    <li><a class="dropdown-item text-white py-2" href="admin_profile.php"><i class="bi bi-person-circle me-2"></i> My Profile</a></li>
                    <li><a class="dropdown-item text-white py-2" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                    <li><a class="dropdown-item text-danger py-2" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>

        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="0">
            <div class="stat-box">
                <div class="text-muted small fw-bold mb-1">ยอดขายวันนี้</div>
                <div class="fs-2 fw-bold text-white">฿<?php echo number_format($today_sales); ?></div>
                <div class="stat-icon-bg text-primary"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-box">
                <div class="text-muted small fw-bold mb-1">ยอดขายเดือนนี้</div>
                <div class="fs-2 fw-bold text-white">฿<?php echo number_format($month_sales); ?></div>
                <div class="stat-icon-bg text-info"><i class="bi bi-bar-chart-fill"></i></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="stat-box">
                <div class="text-muted small fw-bold mb-1">รอตรวจสอบ</div>
                <div class="fs-2 fw-bold text-white"><?php echo $pending_orders; ?></div>
                <div class="stat-icon-bg text-warning"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="stat-box">
                <div class="text-muted small fw-bold mb-1">สินค้าใกล้หมด</div>
                <div class="fs-2 fw-bold text-danger"><?php echo $low_stock_count; ?></div>
                <div class="stat-icon-bg text-danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="400">
            <div class="glass-card h-100">
                <h5 class="fw-bold mb-4 text-white"><i class="bi bi-activity text-primary me-2"></i>Revenue Analytics</h5>
                <div style="height: 350px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="500">
            
            <div class="glass-card mb-4" style="border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-danger"><i class="bi bi-exclamation-circle me-2"></i>สินค้าใกล้หมด</h5>
                    <a href="books.php" class="text-muted small text-decoration-none">จัดการ ></a>
                </div>
                <?php if(count($low_stock_items) > 0): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach($low_stock_items as $item): ?>
                        <div class="d-flex align-items-center gap-3 p-2 rounded-3 bg-dark border border-secondary">
                            <img src="../uploads/<?php echo $item['image']; ?>" class="rounded-2" style="width:35px; height:45px; object-fit:cover;">
                            <div class="flex-grow-1 text-truncate">
                                <div class="fw-bold text-white small"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="text-danger small fw-bold">เหลือ: <?php echo $item['stock_quantity']; ?> เล่ม</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted small py-3">ไม่มีสินค้าใกล้หมด</div>
                <?php endif; ?>
            </div>

            <div class="glass-card">
                <h5 class="fw-bold mb-4 text-warning"><i class="bi bi-trophy-fill me-2"></i>ขายดีประจำร้าน</h5>
                <div class="d-flex flex-column gap-2">
                    <?php $rank = 1; foreach($top_selling as $ts): ?>
                    <div class="d-flex align-items-center gap-3 p-2 rounded-3 hover-bg-light">
                        <div class="badge bg-warning text-dark rounded-circle shadow-sm" style="width:24px; height:24px; display:flex; align-items:center; justify-content:center;"><?php echo $rank++; ?></div>
                        <img src="../uploads/<?php echo $ts['image']; ?>" class="rounded-2" style="width:35px; height:45px; object-fit:cover;">
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-bold text-white small text-truncate"><?php echo htmlspecialchars($ts['title']); ?></div>
                            <div class="text-muted small"><?php echo $ts['total_qty']; ?> sold</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }

    const ctx = document.getElementById('salesChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.5)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'รายได้',
                data: <?php echo json_encode($sales_data); ?>,
                backgroundColor: gradient,
                borderColor: '#6366f1',
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6366f1',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#cbd5e1' } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#cbd5e1' } }
            }
        }
    });
</script>
</body>
</html>
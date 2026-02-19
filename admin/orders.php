<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db.php';

// ค่าเริ่มต้น (Initial Load)
$low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$pending_orders_count = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')")->fetchColumn();
$total_alerts = $pending_orders_count + $low_stock_count;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

// Query เริ่มต้น (กันเหนียว เผื่อ JS ยังไม่ทำงาน)
$sql = "SELECT orders.*, users.username, users.full_name FROM orders LEFT JOIN users ON orders.user_id = users.user_id WHERE 1=1";
$params = [];
if (!empty($search)) {
    $sql .= " AND (users.full_name LIKE :search OR users.username LIKE :search OR orders.order_id LIKE :search OR orders.order_number LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($status_filter != 'all') {
    if ($status_filter == 'pending') $sql .= " AND orders.status IN ('pending', 'รอตรวจสอบ')";
    elseif ($status_filter == 'paid') $sql .= " AND orders.status IN ('paid', 'ชำระเงินแล้ว', 'waiting')";
    elseif ($status_filter == 'shipped') $sql .= " AND orders.status IN ('shipped', 'จัดส่งแล้ว')";
    elseif ($status_filter == 'cancelled') $sql .= " AND orders.status IN ('cancelled', 'ยกเลิก')";
}
$sql .= " ORDER BY orders.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | Admin Console</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600&display=swap');
        :root { --bg-body: #0f172a; --bg-sidebar: #1e293b; --primary: #6366f1; --secondary: #a855f7; --glass-bg: rgba(30, 41, 59, 0.7); --glass-border: rgba(255, 255, 255, 0.1); }
        body { font-family: 'Kanit', 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: #ffffff !important; overflow-x: hidden; background-image: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.15) 0%, transparent 40%); min-height: 100vh; }
        h1, h2, h3, h4, h5, h6, p, span, div, a, li, td, th, label { color: #ffffff; }
        .text-muted { color: #cbd5e1 !important; }
        .text-danger { color: #f87171 !important; }
        .text-warning { color: #fbbf24 !important; }
        .text-success { color: #34d399 !important; }
        .text-primary { color: #818cf8 !important; }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: var(--bg-body); } ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        .glass-card { background: var(--glass-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 24px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); transition: all 0.3s ease; }
        .admin-sidebar { background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); border-right: 1px solid var(--glass-border); min-height: 100vh; padding: 2rem 1.5rem; position: fixed; width: 280px; z-index: 1000; transition: 0.3s; }
        .main-content { margin-left: 280px; padding: 2rem; transition: 0.3s; }
        .nav-link { display: flex; align-items: center; gap: 14px; padding: 14px 18px; color: #cbd5e1 !important; border-radius: 16px; margin-bottom: 8px; transition: 0.3s; font-weight: 500; }
        .nav-link:hover { color: #fff !important; background: rgba(255, 255, 255, 0.1); padding-left: 24px; }
        .nav-link.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white !important; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); }
        .form-control-glass { background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); color: #fff !important; border-radius: 50px; padding: 10px 20px; }
        .form-control-glass:focus { background: rgba(255, 255, 255, 0.1); border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .form-select-glass { background-color: rgba(30, 41, 59, 0.8); border: 1px solid var(--glass-border); color: #fff !important; border-radius: 50px; padding: 10px 20px; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); }
        .table, .modern-table { --bs-table-bg: transparent !important; background-color: transparent !important; }
        .modern-table thead th { background: rgba(255,255,255,0.05); color: #cbd5e1; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; border-bottom: 1px solid var(--glass-border); padding: 1rem; }
        .modern-table td { background-color: transparent !important; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 1rem; vertical-align: middle; color: #fff !important; }
        .modern-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.05) !important; }
        .order-id-badge { background: rgba(99, 102, 241, 0.15); color: #818cf8 !important; padding: 4px 10px; border-radius: 8px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.85rem; border: 1px solid rgba(99, 102, 241, 0.3); }
        .status-badge { padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .st-success { background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3); }
        .st-warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .st-danger { background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }
        .st-info { background: rgba(34, 211, 238, 0.15); color: #22d3ee; border: 1px solid rgba(34, 211, 238, 0.3); }
        .st-primary { background: rgba(129, 140, 248, 0.15); color: #818cf8; border: 1px solid rgba(129, 140, 248, 0.3); }
        .btn-action { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; transition: 0.2s; border: 1px solid var(--glass-border); color: #fff; background: rgba(255,255,255,0.05); }
        .btn-action:hover { background: var(--primary); border-color: var(--primary); transform: translateY(-2px); }
        .dropdown-menu-dark { background-color: #1e293b; border-color: rgba(255,255,255,0.1); }
        .dropdown-item { color: #cbd5e1; }
        .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: #fff; }
        @media (max-width: 991px) { .admin-sidebar { transform: translateX(-100%); } .admin-sidebar.show { transform: translateX(0); } .main-content { margin-left: 0; } }
        /* Animation CSS from animate.css helper */
        .animate__fadeIn { animation-duration: 0.8s; }
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
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="orders.php" class="nav-link active">
            <i class="bi bi-cart-check-fill"></i> Orders
            <span id="sidebarBadge" class="badge bg-danger rounded-pill ms-auto <?php echo $pending_orders_count > 0 ? '' : 'd-none'; ?>">
                <?php echo $pending_orders_count; ?>
            </span>
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
                <h2 class="fw-bold m-0 text-white">Order Management</h2>
                <p class="text-muted m-0 small">Track and manage customer orders.</p>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            
            <div class="dropdown">
                <button class="btn btn-dark rounded-circle border-secondary text-white position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 45px; height: 45px;">
                    <i class="bi bi-bell-fill"></i>
                    <span id="notifyBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark <?php echo $total_alerts > 0 ? '' : 'd-none'; ?>">
                        <?php echo $total_alerts; ?>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-secondary mt-2 p-2" style="width: 300px;">
                    <li><h6 class="dropdown-header text-white border-bottom border-secondary pb-2 mb-2">Notifications</h6></li>
                    <?php if($low_stock_count > 0): ?>
                        <li><a class="dropdown-item rounded-2 text-danger small py-2" href="books.php"><i class="bi bi-exclamation-circle me-2"></i> สินค้าใกล้หมด <?php echo $low_stock_count; ?> รายการ</a></li>
                    <?php endif; ?>
                    <li id="notifyTextLi" class="<?php echo $pending_orders_count > 0 ? '' : 'd-none'; ?>">
                        <a class="dropdown-item rounded-2 text-warning small py-2" href="orders.php">
                            <i class="bi bi-clock me-2"></i> ออเดอร์รอตรวจสอบ <span id="notifyTextCount"><?php echo $pending_orders_count; ?></span> รายการ
                        </a>
                    </li>
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

    <div class="glass-card mb-4" data-aos="fade-up">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary text-muted" style="border-radius: 50px 0 0 50px;">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" id="searchInput" class="form-control form-control-glass border-start-0" placeholder="Search by Order ID, Customer Name..." value="<?php echo htmlspecialchars($search); ?>" style="border-radius: 0 50px 50px 0;">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" id="statusSelect" class="form-select form-select-glass" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>รอตรวจสอบ (Pending)</option>
                    <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>ชำระเงินแล้ว (Paid)</option>
                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>จัดส่งแล้ว (Shipped)</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>ยกเลิก (Cancelled)</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <a href="orders.php" class="btn btn-outline-light rounded-pill w-100"><i class="bi bi-arrow-clockwise me-2"></i>Reset</a>
            </div>
        </form>
    </div>

    <div class="glass-card" data-aos="fade-up" data-aos-delay="100">
        <div class="table-responsive">
            <table class="table modern-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="orderTableBody">
                    <?php if(count($orders) > 0): foreach ($orders as $order): 
                        // Logic แสดงผลตอนโหลดครั้งแรก (Fallback)
                        $status = trim($order['status']);
                        $badge_class = 'st-warning'; $status_text = $status;
                        if (stripos($status, 'สำเร็จ') !== false || stripos($status, 'completed') !== false) { $badge_class = 'st-success'; $status_text = 'สำเร็จ'; }
                        elseif (stripos($status, 'ยกเลิก') !== false || stripos($status, 'cancelled') !== false) { $badge_class = 'st-danger'; $status_text = 'ยกเลิก'; }
                        elseif (stripos($status, 'จัดส่ง') !== false || stripos($status, 'shipped') !== false) { $badge_class = 'st-info'; $status_text = 'จัดส่งแล้ว'; }
                        elseif (stripos($status, 'ชำระเงิน') !== false || stripos($status, 'paid') !== false) { $badge_class = 'st-primary'; $status_text = 'ชำระเงินแล้ว'; }
                    ?>
                    <tr>
                        <td class="ps-4"><span class="order-id-badge"><?php echo !empty($order['order_number']) ? htmlspecialchars($order['order_number']) : '#ORD-'.str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></span></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center border border-secondary" style="width:36px;height:36px;">
                                    <span class="text-white small fw-bold"><?php echo strtoupper(substr($order['username'] ?? 'U', 0, 1)); ?></span>
                                </div>
                                <div><div class="fw-bold text-white small"><?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></div><div class="text-muted" style="font-size:0.75rem;">@<?php echo htmlspecialchars($order['username']); ?></div></div>
                            </div>
                        </td>
                        <td class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?php echo date('d M Y', strtotime($order['created_at'])); ?><br><i class="bi bi-clock me-1"></i> <?php echo date('H:i', strtotime($order['created_at'])); ?></td>
                        <td class="fw-bold text-white">฿<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><span class="status-badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></td>
                        <td class="text-end pe-4"><a href="order_detail.php?id=<?php echo $order['order_id']; ?>" class="btn-action" title="View Details"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-box-seam fs-1 d-block mb-3"></i>No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if(count($orders) > 10): ?>
        <div class="d-flex justify-content-between align-items-center mt-4 px-2">
            <span class="text-muted small">Showing 1 to 10 of <?php echo count($orders); ?> entries</span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item disabled"><a class="page-link bg-transparent border-secondary text-muted" href="#">Previous</a></li>
                    <li class="page-item active"><a class="page-link bg-primary border-primary text-white" href="#">1</a></li>
                    <li class="page-item"><a class="page-link bg-transparent border-secondary text-white" href="#">2</a></li>
                    <li class="page-item"><a class="page-link bg-transparent border-secondary text-white" href="#">Next</a></li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }

    // --- ✅ REALTIME SCRIPT (Fixed Cache) ---
    let searchVal = "<?php echo htmlspecialchars($search); ?>";
    let statusVal = "<?php echo htmlspecialchars($status_filter); ?>";

    function fetchOrders() {
        // 1. แนบ Timestamp เพื่อกัน Browser Cache
        const timestamp = new Date().getTime();

        // 2. เรียกไฟล์ Backend
        fetch(`fetch_orders_realtime.php?search=${encodeURIComponent(searchVal)}&status=${encodeURIComponent(statusVal)}&t=${timestamp}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                // 3. อัปเดตตาราง (เฉพาะเมื่อ HTML เปลี่ยนไปจากเดิม)
                const tableBody = document.getElementById('orderTableBody');
                if(tableBody.innerHTML !== data.html) {
                     tableBody.innerHTML = data.html;
                }

                // 4. อัปเดตตัวเลขแจ้งเตือน Sidebar
                const sidebarBadge = document.getElementById('sidebarBadge');
                if (data.pending_orders > 0) {
                    sidebarBadge.textContent = data.pending_orders;
                    sidebarBadge.classList.remove('d-none');
                } else {
                    sidebarBadge.classList.add('d-none');
                }

                // 5. อัปเดตกระดิ่งแจ้งเตือน Top Bar
                const notifyBadge = document.getElementById('notifyBadge');
                if (data.total_alerts > 0) {
                    notifyBadge.textContent = data.total_alerts;
                    notifyBadge.classList.remove('d-none');
                } else {
                    notifyBadge.classList.add('d-none');
                }

                // 6. อัปเดตข้อความใน Dropdown
                const notifyTextLi = document.getElementById('notifyTextLi');
                const notifyTextCount = document.getElementById('notifyTextCount');
                if (notifyTextLi && notifyTextCount) {
                    if (data.pending_orders > 0) {
                        notifyTextCount.textContent = data.pending_orders;
                        notifyTextLi.classList.remove('d-none');
                    } else {
                        notifyTextLi.classList.add('d-none');
                    }
                }
            })
            .catch(error => console.error('Realtime Error:', error));
    }

    // เรียกครั้งแรกทันที
    fetchOrders();

    // วนลูปเรียกทุก 2 วินาที
    setInterval(fetchOrders, 2000);
</script>
</body>
</html>
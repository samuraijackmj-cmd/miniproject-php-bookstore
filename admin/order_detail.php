<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db.php';

if (!isset($_GET['id'])) { 
    header("Location: orders.php"); 
    exit; 
}
$order_id = $_GET['id'];
$admin_name = $_SESSION['full_name'] ?? 'admin'; 

// --- [LOGIC] 1. ระบบแจ้งเตือน (สำหรับ Top Bar) ---
$low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$pending_orders_count = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')")->fetchColumn();
$total_alerts = $pending_orders_count + $low_stock_count;

// --- [LOGIC] 2. อัปเดตออเดอร์และบันทึก Log ---
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status']; 
    $tracking_number = trim($_POST['tracking_number']); 

    $stmt_check = $conn->prepare("SELECT status FROM orders WHERE order_id = :id");
    $stmt_check->execute([':id' => $order_id]);
    $old_status_raw = $stmt_check->fetchColumn() ?: '🟡 รอตรวจสอบ';

    if ($old_status_raw !== $new_status) {
        $log_stmt = $conn->prepare("INSERT INTO order_logs (order_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)");
        $log_stmt->execute([$order_id, $old_status_raw, $new_status, $admin_name]);
    }

    $check_new = trim($new_status);
    $check_old = trim($old_status_raw);

    // ตัดสต็อก (เมื่อเปลี่ยนจาก รอตรวจสอบ -> สำเร็จ/จัดส่ง/ชำระเงิน)
    if ((stripos($check_old, 'รอตรวจสอบ') !== false || stripos($check_old, 'pending') !== false) && 
        (stripos($check_new, 'ชำระเงิน') !== false || stripos($check_new, 'จัดส่ง') !== false || stripos($check_new, 'สำเร็จ') !== false)) {
        
        $stmt_items = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = :id");
        $stmt_items->execute([':id' => $order_id]);
        foreach ($stmt_items->fetchAll() as $item) {
            $conn->prepare("UPDATE books SET stock_quantity = stock_quantity - :qty WHERE book_id = :bid")
                 ->execute([':qty' => $item['quantity'], ':bid' => $item['book_id']]);
        }
    }

    // คืนสต็อก (เมื่อเปลี่ยนจาก ชำระเงิน/จัดส่ง -> ยกเลิก)
    if ((stripos($check_old, 'ชำระเงิน') !== false || stripos($check_old, 'จัดส่ง') !== false) && 
        (stripos($check_new, 'ยกเลิก') !== false || stripos($check_new, 'cancelled') !== false)) {
        
        $stmt_items = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = :id");
        $stmt_items->execute([':id' => $order_id]);
        foreach ($stmt_items->fetchAll() as $item) {
            $conn->prepare("UPDATE books SET stock_quantity = stock_quantity + :qty WHERE book_id = :bid")
                 ->execute([':qty' => $item['quantity'], ':bid' => $item['book_id']]);
        }
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = :status, tracking_number = :tracking WHERE order_id = :id");
    $stmt->execute([':status' => $new_status, ':tracking' => $tracking_number, ':id' => $order_id]);
    
    header("Location: order_detail.php?id=$order_id&msg=updated");
    exit;
}

// 3. ดึงข้อมูลออเดอร์
$stmt = $conn->prepare("SELECT orders.*, users.full_name, users.phone, users.address as user_address FROM orders JOIN users ON orders.user_id = users.user_id WHERE orders.order_id = :id");
$stmt->execute([':id' => $order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo "Order not found.";
    exit;
}

$stmt = $conn->prepare("SELECT order_items.*, books.title, books.image FROM order_items JOIN books ON order_items.book_id = books.book_id WHERE order_id = :id");
$stmt->execute([':id' => $order_id]);
$items = $stmt->fetchAll();

// 4. ดึง Log
$stmt_logs = $conn->prepare("SELECT * FROM order_logs WHERE order_id = ? ORDER BY created_at DESC");
$stmt_logs->execute([$order_id]);
$logs = $stmt_logs->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Detail #<?php echo $order_id; ?> | Admin Console</title>
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
            color: #ffffff !important;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.15) 0%, transparent 40%);
            min-height: 100vh;
        }

        /* Force White Text */
        h1, h2, h3, h4, h5, h6, p, span, div, a, li, td, th, label, i { color: #ffffff; }
        .text-muted { color: #cbd5e1 !important; }
        .text-danger { color: #f87171 !important; }
        .text-warning { color: #fbbf24 !important; }
        .text-success { color: #34d399 !important; }
        .text-primary { color: #818cf8 !important; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-body); }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

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

        /* Glass Card */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 24px;
        }

        /* Form Controls */
        .form-control-glass, .form-select-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: #fff !important;
            border-radius: 12px;
            padding: 10px 15px;
        }
        .form-control-glass:focus, .form-select-glass:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .form-select-glass option { background: #1e293b; }

        /* Table */
        .table, .modern-table { --bs-table-bg: transparent !important; background-color: transparent !important; }
        .modern-table thead th {
            background: rgba(255,255,255,0.05); color: #cbd5e1;
            font-weight: 600; text-transform: uppercase; font-size: 0.8rem;
            border-bottom: 1px solid var(--glass-border); padding: 1rem;
        }
        .modern-table td {
            background-color: transparent !important;
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            padding: 1rem; vertical-align: middle; 
            color: #fff !important;
        }

        /* Helpers */
        .btn-action {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; border: none; padding: 10px 24px; border-radius: 50px;
            font-weight: 600; width: 100%; transition: 0.3s;
        }
        .btn-action:hover { transform: translateY(-2px); color: white; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); }

        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; }
        .info-row:last-child { border-bottom: none; }
        
        .log-item { padding: 10px; border-left: 2px solid var(--glass-border); margin-left: 10px; position: relative; }
        .log-item::before { content: ''; width: 10px; height: 10px; background: var(--primary); border-radius: 50%; position: absolute; left: -6px; top: 15px; }

        /* Dropdown */
        .dropdown-menu-dark { background-color: #1e293b; border-color: rgba(255,255,255,0.1); }
        .dropdown-item { color: #cbd5e1; }
        .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: #fff; }

        #tracking_area { display: none; }

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
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="orders.php" class="nav-link active">
            <i class="bi bi-cart-check-fill"></i> Orders
            <?php if($pending_orders_count > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_orders_count; ?></span>
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
            <div class="d-flex align-items-center gap-3">
                <a href="orders.php" class="btn btn-dark border-secondary rounded-circle" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h2 class="fw-bold m-0 text-white">Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h2>
                    <p class="text-muted m-0 small">View details and update status.</p>
                </div>
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
                    <?php if($pending_orders_count > 0): ?>
                        <li><a class="dropdown-item rounded-2 text-warning small py-2" href="orders.php"><i class="bi bi-clock me-2"></i> ออเดอร์รอตรวจสอบ <?php echo $pending_orders_count; ?> รายการ</a></li>
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

    <div class="row g-4">
        <div class="col-lg-8" data-aos="fade-up">
            
            <div class="glass-card">
                <h5 class="fw-bold mb-4 text-white"><i class="bi bi-basket me-2 text-primary"></i>รายการสินค้า</h5>
                <div class="table-responsive">
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">สินค้า</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-end pe-4">รวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="../uploads/<?php echo $item['image']; ?>" class="rounded" style="width:40px; height:55px; object-fit:cover;">
                                        <span class="text-white"><?php echo htmlspecialchars($item['title']); ?></span>
                                    </div>
                                </td>
                                <td class="text-center text-muted">x<?php echo $item['quantity']; ?></td>
                                <td class="text-end pe-4 text-white fw-bold">฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="border-top: 1px solid rgba(255,255,255,0.1);">
                            <tr>
                                <td colspan="2" class="text-end py-3 text-muted">ยอดรวมสุทธิ</td>
                                <td class="text-end pe-4 py-3 text-primary fs-5 fw-bold">฿<?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="glass-card" data-aos="fade-up" data-aos-delay="100">
                <h5 class="fw-bold mb-4 text-white"><i class="bi bi-clock-history me-2 text-warning"></i>ประวัติการดำเนินการ</h5>
                <div class="ps-2">
                    <?php if (empty($logs)): ?>
                        <p class="text-muted small">ยังไม่มีประวัติการเปลี่ยนแปลงสถานะ</p>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <div class="log-item">
                            <div class="d-flex justify-content-between">
                                <span class="text-white fw-bold"><?php echo $log['new_status']; ?></span>
                                <span class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></span>
                            </div>
                            <div class="small text-muted mt-1">
                                เปลี่ยนจาก <span class="text-white-50"><?php echo $log['old_status']; ?></span> 
                                โดย <i class="bi bi-person-fill ms-1"></i> <?php echo htmlspecialchars($log['changed_by']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
            
            <div class="glass-card border-top border-4 border-primary">
                <h5 class="fw-bold mb-4 text-white">จัดการสถานะ</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted">สถานะปัจจุบัน</label>
                        <select name="status" id="status_dropdown" class="form-select form-select-glass" onchange="checkStatus(this.value)">
                            <option value="🟡 รอตรวจสอบ" <?php echo ($order['status'] == '🟡 รอตรวจสอบ') ? 'selected' : ''; ?>>🟡 รอตรวจสอบ</option>
                            <option value="🔵 ชำระเงินแล้ว" <?php echo ($order['status'] == '🔵 ชำระเงินแล้ว') ? 'selected' : ''; ?>>🔵 ชำระเงินแล้ว</option>
                            <option value="🚚 จัดส่งแล้ว" <?php echo ($order['status'] == '🚚 จัดส่งแล้ว') ? 'selected' : ''; ?>>🚚 จัดส่งแล้ว</option>
                            <option value="✅ สำเร็จ" <?php echo ($order['status'] == '✅ สำเร็จ') ? 'selected' : ''; ?>>✅ สำเร็จ</option>
                            <option value="❌ ยกเลิก" <?php echo ($order['status'] == '❌ ยกเลิก') ? 'selected' : ''; ?>>❌ ยกเลิก</option>
                        </select>
                    </div>

                    <div id="tracking_area" class="mb-4">
                        <label class="form-label text-info">เลขพัสดุ (Tracking)</label>
                        <div class="input-group">
                            <input type="text" name="tracking_number" id="tracking_input" class="form-control form-control-glass border-end-0" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                            <button class="btn btn-outline-secondary border-start-0" type="button" onclick="randomTracking()"><i class="bi bi-shuffle"></i></button>
                        </div>
                    </div>

                    <button type="submit" name="update_status" class="btn btn-action">
                        <i class="bi bi-save me-2"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>

            <div class="glass-card">
                <h5 class="fw-bold mb-4 text-white"><i class="bi bi-person-lines-fill me-2 text-success"></i>ข้อมูลจัดส่ง</h5>
                <div class="info-row">
                    <span class="text-muted">ชื่อผู้รับ</span>
                    <span class="text-white text-end"><?php echo htmlspecialchars($order['fullname'] ?? $order['full_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="text-muted">เบอร์โทร</span>
                    <span class="text-white text-end"><?php echo htmlspecialchars($order['phone']); ?></span>
                </div>
                <div class="mt-3">
                    <span class="text-muted d-block mb-1">ที่อยู่จัดส่ง</span>
                    <p class="text-white small m-0 bg-white bg-opacity-10 p-2 rounded">
                        <?php echo nl2br(htmlspecialchars($order['address'] ?? $order['user_address'])); ?>
                    </p>
                </div>
            </div>

            <div class="glass-card">
                <h5 class="fw-bold mb-3 text-white"><i class="bi bi-receipt me-2 text-warning"></i>หลักฐานการโอน</h5>
                <?php if (!empty($order['slip_image'])): ?>
                    <a href="../uploads/slips/<?php echo $order['slip_image']; ?>" target="_blank" class="d-block position-relative group-hover">
                        <img src="../uploads/slips/<?php echo $order['slip_image']; ?>" class="img-fluid rounded border border-secondary w-100" alt="Payment Slip">
                        <div class="position-absolute top-50 start-50 translate-middle badge bg-dark bg-opacity-75">
                            <i class="bi bi-zoom-in me-1"></i> ดูรูปใหญ่
                        </div>
                    </a>
                <?php elseif (($order['payment_method'] ?? '') === 'cod'): ?>
                    <div class="alert alert-success bg-opacity-25 border-success text-center mb-0">
                        <i class="bi bi-cash-coin fs-1 d-block mb-2"></i>
                        <strong>เก็บเงินปลายทาง (COD)</strong>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger bg-opacity-25 border-danger text-center mb-0">
                        <i class="bi bi-image-alt fs-1 d-block mb-2"></i>
                        ไม่พบหลักฐานการโอน
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }

    function checkStatus(val) {
        const area = document.getElementById('tracking_area');
        const input = document.getElementById('tracking_input');
        if (val.includes('จัดส่ง')) {
            area.style.display = 'block';
            if (input.value === '') randomTracking();
        } else {
            area.style.display = 'none';
        }
    }
    
    function randomTracking() {
        const pre = ['TH', 'KERRY', 'FLASH'];
        const num = Math.floor(Math.random() * 90000000) + 10000000;
        document.getElementById('tracking_input').value = pre[Math.floor(Math.random()*pre.length)] + num;
    }
    
    // Initial Check
    window.onload = function() { checkStatus(document.getElementById('status_dropdown').value); };
</script>
</body>
</html>
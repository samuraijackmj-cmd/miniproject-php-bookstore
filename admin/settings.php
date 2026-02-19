<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db.php';

// --- [LOGIC] 1. ระบบแจ้งเตือน (คงเดิม) ---
$low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$pending_orders_count = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')")->fetchColumn();
$total_alerts = $pending_orders_count + $low_stock_count;

$msg = ""; 

// --- [LOGIC] 2. ตรวจสอบการกดปุ่มบันทึก (INSERT or UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_name = trim($_POST['site_name']);
    $contact_email = trim($_POST['contact_email']);
    $shipping_fee = floatval($_POST['shipping_fee']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

    try {
        $check = $conn->query("SELECT id FROM settings WHERE id = 1")->fetch();
        if ($check) {
            $sql = "UPDATE settings SET site_name = ?, contact_email = ?, shipping_fee = ?, maintenance_mode = ? WHERE id = 1";
            $stmt = $conn->prepare($sql);
            $success = $stmt->execute([$site_name, $contact_email, $shipping_fee, $maintenance_mode]);
        } else {
            $sql = "INSERT INTO settings (id, site_name, contact_email, shipping_fee, maintenance_mode) VALUES (1, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $success = $stmt->execute([$site_name, $contact_email, $shipping_fee, $maintenance_mode]);
        }

        if ($success) {
            $msg = "<div class='alert alert-success bg-success text-white border-0 shadow-sm animate__animated animate__fadeIn'>
                        <i class='bi bi-check-circle-fill me-2'></i>บันทึกการตั้งค่าเรียบร้อยแล้ว
                    </div>";
        }
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger bg-danger text-white border-0 shadow-sm'>
                    <i class='bi bi-exclamation-triangle-fill me-2'></i>เกิดข้อผิดพลาด: " . $e->getMessage() . "
                </div>";
    }
}

// --- [LOGIC] 3. ดึงข้อมูลล่าสุดมาแสดง ---
$stmt = $conn->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();

if (!$settings) {
    $settings = [
        'site_name' => 'BookStore Premium',
        'contact_email' => 'admin@bookstore.com',
        'shipping_fee' => 50,
        'maintenance_mode' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Admin Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600&display=swap');
        :root { --bg-body: #0f172a; --bg-sidebar: #1e293b; --primary: #6366f1; --secondary: #a855f7; --glass-bg: rgba(30, 41, 59, 0.7); --glass-border: rgba(255, 255, 255, 0.1); }
        body { font-family: 'Kanit', 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: #ffffff !important; background-image: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.15) 0%, transparent 40%); min-height: 100vh; }
        .admin-sidebar { background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); border-right: 1px solid var(--glass-border); min-height: 100vh; padding: 2rem 1.5rem; position: fixed; width: 280px; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 14px; padding: 14px 18px; color: #cbd5e1 !important; border-radius: 16px; margin-bottom: 8px; transition: 0.3s; font-weight: 500; text-decoration: none; }
        .nav-link:hover { color: #fff !important; background: rgba(255, 255, 255, 0.1); padding-left: 24px; }
        .nav-link.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white !important; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); }
        .glass-card { background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 24px; padding: 32px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); }
        .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); color: #fff !important; border-radius: 12px; padding: 12px 16px; }
        .btn-save { background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none; color: #fff !important; padding: 12px 32px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .form-check-input { width: 3em; height: 1.5em; cursor: pointer; }
    </style>
</head>
<body>

<div class="admin-sidebar d-flex flex-column" id="sidebar">
    <div class="mb-5 px-2 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary rounded-3 p-2 text-white shadow-lg"><i class="bi bi-book-half fs-5"></i></div>
            <div><h5 class="m-0 fw-bold">BOOK<span class="text-primary">STORE</span></h5><span class="text-muted small">Admin Console</span></div>
        </div>
    </div>
    <nav class="nav flex-column gap-1 mb-auto">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="orders.php" class="nav-link"><i class="bi bi-cart-check-fill"></i> Orders</a>
        <a href="books.php" class="nav-link"><i class="bi bi-journal-album"></i> Products</a>
        <a href="settings.php" class="nav-link active"><i class="bi bi-gear-fill"></i> Settings</a>
    </nav>
    <div class="mt-4"><a href="../index.php" class="nav-link text-white"><i class="bi bi-shop"></i> Storefront</a></div>
</div>

<div class="main-content">
    <div class="mb-5">
        <h2 class="fw-bold m-0 text-white">System Settings</h2>
        <p class="text-muted m-0 small">Configure general system preferences.</p>
    </div>

    <?php if(!empty($msg)) echo $msg; ?>

    <form method="POST">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="glass-card h-100">
                    <h5 class="fw-bold mb-4 text-info"><i class="bi bi-sliders me-2"></i>ข้อมูลทั่วไป</h5>
                    <div class="mb-3">
                        <label class="form-label text-white small">ชื่อร้านค้า (Site Title)</label>
                        <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-white small">อีเมลติดต่อ (Contact Email)</label>
                        <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                    </div>
                    <hr class="border-secondary opacity-25 my-4">
                    <div class="form-check form-switch d-flex align-items-center gap-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="maintenance_mode" id="maintenance" <?php echo ($settings['maintenance_mode'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label text-white" for="maintenance">โหมดปิดปรับปรุง (Maintenance Mode)</label>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="glass-card h-100">
                    <h5 class="fw-bold mb-4 text-warning"><i class="bi bi-truck me-2"></i>การจัดส่ง</h5>
                    <div class="mb-4">
                        <label class="form-label text-white small">ค่าจัดส่งมาตรฐาน (บาท)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary text-white">฿</span>
                            <input type="number" name="shipping_fee" class="form-control" value="<?php echo $settings['shipping_fee']; ?>" required>
                        </div>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.03);">
                        <p class="text-white small mb-0"><i class="bi bi-info-circle me-1"></i> ค่าจัดส่งนี้จะถูกนำไปรวมกับราคาสินค้าทั้งหมดในหน้าชำระเงิน</p>
                    </div>
                </div>
            </div>

            <div class="col-12 text-end">
                <div class="glass-card p-3">
                    <button type="submit" class="btn btn-save shadow">บันทึกการตั้งค่าทั้งหมด</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
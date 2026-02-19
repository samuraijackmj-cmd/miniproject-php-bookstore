<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$msg = "";

// --- [LOGIC] 1. ระบบแจ้งเตือน (Notification) ---
$low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$pending_orders_count = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')")->fetchColumn();
$total_alerts = $pending_orders_count + $low_stock_count;

// --- [LOGIC] 2. Handle Form Submit ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Update Basic Info
    $sql = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
    $params = [$username, $email, $user_id];

    // Change Password Logic
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?";
            $params = [$username, $email, $hashed_password, $user_id];
        } else {
            $msg = "<div class='alert alert-danger bg-danger text-white border-0'>รหัสผ่านยืนยันไม่ตรงกัน</div>";
        }
    }

    if (empty($msg)) {
        $stmt = $conn->prepare($sql);
        if ($stmt->execute($params)) {
            $msg = "<div class='alert alert-success bg-success text-white border-0'>บันทึกข้อมูลเรียบร้อยแล้ว</div>";
            // Update Session Name if changed
            $_SESSION['username'] = $username; 
        } else {
            $msg = "<div class='alert alert-danger bg-danger text-white border-0'>เกิดข้อผิดพลาดในการบันทึก</div>";
        }
    }
}

// Fetch User Data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Admin Console</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
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

        /* Elements */
        h1, h2, h3, h4, h5, h6, p, span, div, a, label { color: #ffffff; }
        .text-muted { color: #cbd5e1 !important; }
        
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
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* Form Controls */
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: #fff !important;
            border-radius: 12px;
            padding: 12px 16px;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        /* Profile Specific */
        .avatar-lg { 
            width: 100px; height: 100px; border-radius: 50%; 
            background: linear-gradient(135deg, #6366f1, #a855f7); 
            display: flex; align-items: center; justify-content: center; 
            font-size: 3rem; font-weight: bold; margin: 0 auto 20px; 
            box-shadow: 0 0 30px rgba(99,102,241,0.4); 
            border: 4px solid rgba(255,255,255,0.1);
        }

        .btn-primary-grad {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none; color: #fff !important; 
            padding: 12px 32px; border-radius: 50px; 
            font-weight: 600; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); 
            transition: 0.3s;
        }
        .btn-primary-grad:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5); }

        /* Dropdown */
        .dropdown-menu-dark { background-color: #1e293b; border-color: rgba(255,255,255,0.1); }
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
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="orders.php" class="nav-link">
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
            <div>
                <h2 class="fw-bold m-0 text-white">My Profile</h2>
                <p class="text-muted m-0 small">Manage your account settings.</p>
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
        
        <div class="col-md-4">
            <div class="glass-card text-center h-100">
                <div class="avatar-lg">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($admin['username']); ?></h4>
                <p class="text-muted small">Administrator</p>
                <div class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 mb-4">
                    <i class="bi bi-shield-check me-1"></i> Super Admin
                </div>
                
                <hr class="border-secondary opacity-25 my-4">
                
                <div class="text-start px-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-white bg-opacity-10 p-2 rounded-circle me-3"><i class="bi bi-envelope text-white"></i></div>
                        <div>
                            <div class="small text-muted">Email Address</div>
                            <div class="fw-bold"><?php echo htmlspecialchars($admin['email']); ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-10 p-2 rounded-circle me-3"><i class="bi bi-calendar3 text-white"></i></div>
                        <div>
                            <div class="small text-muted">Joined Date</div>
                            <div class="fw-bold"><?php echo date('d M Y', strtotime($admin['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="glass-card">
                <h5 class="fw-bold mb-4 d-flex align-items-center">
                    <i class="bi bi-pencil-square text-primary me-2"></i> แก้ไขข้อมูลส่วนตัว
                </h5>
                
                <?php echo $msg; ?>

                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Username</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                    </div>

                    <hr class="border-secondary opacity-25 my-4">
                    <h6 class="fw-bold text-white mb-3"><i class="bi bi-key me-2 text-warning"></i>เปลี่ยนรหัสผ่าน (เว้นว่างหากไม่ต้องการเปลี่ยน)</h6>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted">รหัสผ่านใหม่</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="text-end mt-5">
                        <button type="submit" class="btn btn-primary-grad">
                            <i class="bi bi-check-circle-fill me-2"></i>บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }
</script>
</body>
</html>
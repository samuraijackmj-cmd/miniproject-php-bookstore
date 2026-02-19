<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}
require_once '../config/db.php';

// ดึงหมวดหมู่มาใส่ใน Select Box
$cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

// ดึงจำนวนออเดอร์ที่ค้างอยู่ (สำหรับ Badge ใน Sidebar)
$stmt_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')");
$pending_orders = $stmt_orders->fetch()['count'] ?? 0;

// ดึงจำนวนหนังสือเหลือน้อย (สำหรับ Notification Dropdown)
$low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$total_alerts = $pending_orders + $low_stock_count;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isbn = trim($_POST['isbn']); // รับค่า ISBN
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $price = $_POST['price'];
    $discount_percent = !empty($_POST['discount_percent']) ? intval($_POST['discount_percent']) : 0;
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $description = trim($_POST['description']);
    
    // จัดการอัปโหลดรูปภาพ
    $image = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $image = uniqid() . "." . $ext;
            if (!is_dir("../uploads/")) { 
                mkdir("../uploads/", 0777, true); 
            }
            move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
        }
    }

    // เพิ่ม ISBN ลงใน SQL
    $sql = "INSERT INTO books (isbn, title, author, price, discount_percent, stock_quantity, category_id, description, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$isbn, $title, $author, $price, $discount_percent, $stock, $category_id, $description, $image]);

    header("Location: books.php?msg=added");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book | Admin Console</title>
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
            color: #ffffff !important; /* Force White Text Globally */
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.15) 0%, transparent 40%);
            min-height: 100vh;
        }

        /* Force White Text Specific Elements */
        h1, h2, h3, h4, h5, h6, p, span, div, a, li, td, th, label, i { color: #ffffff; }
        .text-muted { color: #cbd5e1 !important; } /* Light Gray for muted text */
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
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* Form Elements */
        .form-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #ffffff !important; /* White Label */
            margin-bottom: 8px;
            opacity: 0.9;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid var(--glass-border) !important;
            color: #ffffff !important; /* White Input Text */
            border-radius: 12px;
            padding: 12px 16px;
            transition: 0.3s;
        }
        
        .form-control::placeholder { color: rgba(255, 255, 255, 0.4) !important; }

        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
            color: #ffffff !important;
        }
        
        .form-select option { background: #1e293b; color: #fff; }
        
        /* Upload Area */
        .upload-area {
            border: 2px dashed var(--glass-border);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            background: rgba(255, 255, 255, 0.02);
            transition: 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }
        .upload-area i { color: #818cf8 !important; }
        .upload-area p { color: #cbd5e1 !important; }

        #imgPreview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 12px;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: none;
        }

        /* Buttons */
        .btn-primary-grad {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none; color: #fff !important; 
            padding: 12px 32px; border-radius: 50px; 
            font-weight: 600; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); 
            transition: 0.3s;
        }
        .btn-primary-grad:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5); color: #fff; }
        
        .btn-cancel {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            color: #ffffff !important;
            padding: 12px 32px; border-radius: 50px;
            font-weight: 500; transition: 0.3s;
            text-decoration: none;
        }
        .btn-cancel:hover { background: rgba(255,255,255,0.1); color: #fff !important; }

        /* Dropdown */
        .dropdown-menu-dark { background-color: #1e293b; border-color: rgba(255,255,255,0.1); }
        .dropdown-item { color: #cbd5e1; }
        .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: #fff; }

        /* Size Info Box */
        .size-info-box {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 12px;
            background: #ffffff; /* White background for visibility */
        }
        .size-info-box small {
            color: #000000 !important; /* Black text on white bg */
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .size-info-box i { color: #6366f1 !important; margin-right: 8px; }

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
            <?php if($pending_orders > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_orders; ?></span>
            <?php endif; ?>
        </a>
        <a href="books.php" class="nav-link active"><i class="bi bi-journal-album"></i> Products</a>
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
                <h2 class="fw-bold m-0 text-white">Add New Book</h2>
                <p class="text-muted m-0 small">Create a new book entry in your inventory.</p>
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

    <div class="glass-card">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-5">
                <div class="col-lg-8">
                    <h5 class="mb-4 d-flex align-items-center text-white">
                        <i class="bi bi-info-circle me-3 text-primary"></i> ข้อมูลหนังสือ
                    </h5>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">ISBN / รหัสสินค้า</label>
                            <input type="text" name="isbn" class="form-control" placeholder="เช่น 978-x-xxx-xxxxx-x" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">หมวดหมู่</label>
                            <select name="category_id" class="form-select" required>
                                <option value="" disabled selected>เลือกหมวดหมู่</option>
                                <?php foreach($cats as $c): ?>
                                    <option value="<?php echo $c['category_id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">ชื่อหนังสือ</label>
                        <input type="text" name="title" class="form-control" placeholder="ระบุชื่อหนังสือ" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">ผู้แต่ง</label>
                        <input type="text" name="author" class="form-control" placeholder="ระบุชื่อผู้แต่ง" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">ราคา (บาท)</label>
                            <input type="number" name="price" class="form-control" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ส่วนลด (%)</label>
                            <input type="number" name="discount_percent" class="form-control" value="0" placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">จำนวนสต็อก</label>
                            <input type="number" name="stock" class="form-control" value="1" required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">คำอธิบาย / เรื่องย่อ</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="รายละเอียดหนังสือ..."></textarea>
                    </div>
                </div>

                <div class="col-lg-4">
                    <h5 class="mb-4 d-flex align-items-center text-white">
                        <i class="bi bi-image me-3 text-primary"></i> รูปภาพปก
                    </h5>
                    
                    <div class="upload-area" onclick="document.getElementById('imgInput').click()">
                        <i class="bi bi-cloud-arrow-up fs-1"></i>
                        <p class="mt-3 small">คลิกเพื่ออัปโหลดรูปภาพ</p>
                        <input type="file" name="image" id="imgInput" class="d-none" accept="image/*" required>
                        <img id="imgPreview" src="#" alt="Preview">
                    </div>
                    
                    <div class="size-info-box">
                        <small>
                            <i class="bi bi-info-circle-fill"></i> 
                            แนะนำขนาด: 500x700px (แนวตั้ง)
                        </small>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5 pt-4 border-top border-secondary border-opacity-25">
                <a href="books.php" class="btn btn-cancel">ยกเลิก</a>
                <button type="submit" class="btn btn-primary-grad">
                    <i class="bi bi-plus-lg me-2"></i> บันทึกหนังสือ
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }

    // Image Preview Logic
    document.getElementById('imgInput').onchange = evt => {
        const [file] = document.getElementById('imgInput').files
        if (file) {
            const preview = document.getElementById('imgPreview');
            preview.src = URL.createObjectURL(file)
            preview.style.display = 'block'
        }
    }
</script>
</body>
</html>
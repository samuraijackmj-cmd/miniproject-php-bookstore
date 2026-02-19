<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}
require_once '../config/db.php';

// --- [LOGIC] ดึงข้อมูลสถิติ Notification ---
$low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$pending_orders_count = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')")->fetchColumn();
$total_alerts = $pending_orders_count + $low_stock_count;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management | Admin Console</title>
    
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

        /* Glass Components */
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

        .form-control-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: #fff !important;
            border-radius: 50px;
            padding: 10px 20px;
        }
        .form-control-glass:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .form-control-glass::placeholder { color: rgba(255,255,255,0.4); }

        /* Table Styles */
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
        .modern-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.05) !important; }

        /* Buttons */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; border: none; padding: 10px 24px; border-radius: 50px;
            font-weight: 600; width: 100%; transition: 0.3s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5); color: white; }

        /* Dropdown */
        .dropdown-menu-dark { background-color: #1e293b; border-color: rgba(255,255,255,0.1); }
        .dropdown-item { color: #cbd5e1; }
        .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: #fff; }

        .btn-delete-cat {
            width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 10px; transition: 0.2s; border: 1px solid rgba(239,68,68,0.3);
            color: #ef4444; background: rgba(239,68,68,0.1);
        }
        .btn-delete-cat:hover { background: #ef4444; color: white; border-color: #ef4444; }

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
        <a href="categories.php" class="nav-link active"><i class="bi bi-bookmarks-fill"></i> Categories</a>
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
                <h2 class="fw-bold m-0 text-white">Category Management</h2>
                <p class="text-muted m-0 small">Organize your book categories.</p>
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
        <div class="col-md-4" data-aos="fade-right">
            <div class="glass-card h-100">
                <h5 class="fw-bold mb-4 text-white">
                    <i class="bi bi-plus-circle text-primary me-2"></i> เพิ่มหมวดหมู่ใหม่
                </h5>
                <form id="addCategoryForm">
                    <div class="mb-4">
                        <label class="form-label text-muted">ชื่อหมวดหมู่หนังสือ</label>
                        <input type="text" name="category_name" class="form-control form-control-glass" placeholder="เช่น นิยาย, การ์ตูน, ธุรกิจ..." required>
                    </div>
                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-check-lg me-2"></i> บันทึกข้อมูล
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-8" data-aos="fade-left">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-white">
                        <i class="bi bi-list-ul text-info me-2"></i> รายการหมวดหมู่
                    </h5>
                    <span class="badge bg-dark border border-secondary text-muted rounded-pill px-3 py-2" id="cat-count-badge">
                        Loading...
                    </span>
                </div>
                
                <div class="table-responsive">
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 100px;">ID</th>
                                <th>Category Name</th>
                                <th class="text-end pe-4" style="width: 100px;">Manage</th>
                            </tr>
                        </thead>
                        <tbody id="categoryTableBody">
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }

    function escapeHtml(str) { 
        const div = document.createElement('div'); 
        div.appendChild(document.createTextNode(str)); 
        return div.innerHTML; 
    }
    
    async function fetchCategories() {
        const tableBody = document.getElementById('categoryTableBody');
        const countBadge = document.getElementById('cat-count-badge');
        
        try {
            const response = await fetch('category_action_ajax.php?action=fetch');
            const data = await response.json();
            
            let html = '';
            if (data.length > 0) {
                data.forEach(c => {
                    html += `<tr>
                        <td class="ps-4"><span class="text-muted font-monospace">#${String(c.category_id).padStart(3, '0')}</span></td>
                        <td><span class="fw-bold text-white">${escapeHtml(c.category_name)}</span></td>
                        <td class="text-end pe-4">
                            <button onclick="deleteCategory(${c.category_id})" class="btn-delete-cat" title="ลบ">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </td>
                    </tr>`;
                });
                countBadge.innerHTML = `<i class="bi bi-folder2 me-1"></i>${data.length} รายการ`;
            } else { 
                html = '<tr><td colspan="3" class="text-center py-5 text-muted">ไม่พบข้อมูลหมวดหมู่</td></tr>'; 
                countBadge.innerHTML = `<i class="bi bi-folder2 me-1"></i>0 รายการ`;
            }
            
            tableBody.innerHTML = html;
        } catch (error) { 
            tableBody.innerHTML = '<tr><td colspan="3" class="text-center py-5 text-danger">ไม่สามารถโหลดข้อมูลได้</td></tr>'; 
        }
    }

    document.getElementById('addCategoryForm').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add');
        
        try {
            const response = await fetch('category_action_ajax.php', { method: 'POST', body: formData });
            const res = await response.json();
            
            if (res.success) { 
                e.target.reset(); 
                fetchCategories(); 
                alert('✅ เพิ่มหมวดหมู่สำเร็จ');
            } else { 
                alert('⚠️ ' + res.message); 
            }
        } catch (error) { 
            alert('❌ เกิดข้อผิดพลาด'); 
        }
    };

    async function deleteCategory(id) {
        if (!confirm('ยืนยันการลบหมวดหมู่นี้?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('category_id', id);
        
        try {
            const response = await fetch('category_action_ajax.php', { method: 'POST', body: formData });
            const res = await response.json();
            
            if (res.success) { 
                fetchCategories(); 
                alert('✅ ลบหมวดหมู่สำเร็จ');
            } else { 
                alert('❌ ' + res.message); 
            }
        } catch (error) { 
            alert('❌ เกิดข้อผิดพลาด'); 
        }
    }
    
    window.onload = fetchCategories;
</script>

</body>
</html>
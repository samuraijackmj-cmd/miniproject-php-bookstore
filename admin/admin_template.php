<?php
// 🇹🇭 ตั้งเวลาไทยเพื่อให้วันที่ตรงกัน
date_default_timezone_set('Asia/Bangkok'); 
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

require_once '../config/db.php';
require_once 'admin_template.php'; // เรียกใช้ Template ที่คุณส่งมา

// --- 1. เตรียมข้อมูลสำหรับเนื้อหา ---
$low_stock_threshold = 5;
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

$stmt_low = $conn->prepare("SELECT COUNT(*) FROM books WHERE stock_quantity < :threshold");
$stmt_low->execute([':threshold' => $low_stock_threshold]);
$low_stock_count = $stmt_low->fetchColumn();

// ดึงจำนวนออเดอร์ใหม่เพื่อแสดงในกล่องสถิติ
$stmt_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'รอตรวจสอบ', '🟡 รอตรวจสอบ')");
$pending_orders_count = $stmt_orders->fetch()['count'] ?? 0;

$total_books = $conn->query("SELECT COUNT(*) FROM books")->fetchColumn();

// --- 2. สร้างฟังก์ชันเนื้อหา ($content_func) ---
$content = function() use ($conn, $low_stock_count, $total_books, $pending_orders_count, $categories) {
?>
    <div class="d-flex justify-content-end mb-4">
        <a href="book_add.php" class="btn" style="background: var(--primary-grad); border: none; border-radius: 12px; color: #fff !important; padding: 10px 22px; font-weight: 600; text-decoration: none;">
            <i class="bi bi-plus-lg me-2"></i>เพิ่มหนังสือใหม่
        </a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card p-4 border-start border-danger border-4" style="background: rgba(255,255,255,0.03); border-radius:24px;">
                <div class="small opacity-75 text-white">สต็อกใกล้หมด</div>
                <div class="h2 fw-bold" style="color: #f87171 !important;"><?php echo $low_stock_count; ?> รายการ</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 border-start border-primary border-4" style="background: rgba(255,255,255,0.03); border-radius:24px;">
                <div class="small opacity-75 text-white">หนังสือทั้งหมด</div>
                <div class="h2 fw-bold text-white"><?php echo $total_books; ?> เล่ม</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 border-start border-warning border-4" style="background: rgba(255,255,255,0.03); border-radius:24px;">
                <div class="small opacity-75 text-white">ออเดอร์ใหม่</div>
                <div class="h2 fw-bold" style="color: #fbbf24 !important;"><?php echo $pending_orders_count; ?> รายการ</div>
            </div>
        </div>
    </div>

    <div class="glass-card p-4 mb-4" style="background: rgba(255,255,255,0.03); border-radius: 24px; border: 1px solid var(--glass-border);">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small opacity-75 text-white">ค้นหาหนังสือ</label>
                <input type="text" id="searchInput" class="form-control" style="background: rgba(15, 23, 42, 0.5); border: 1px solid var(--glass-border); color: #fff; border-radius: 12px;" placeholder="ชื่อหนังสือ หรือ ผู้แต่ง...">
            </div>
            <div class="col-md-4">
                <label class="form-label small opacity-75 text-white">หมวดหมู่</label>
                <select id="catFilter" class="form-select" style="background: rgba(15, 23, 42, 0.5); border: 1px solid var(--glass-border); color: #fff; border-radius: 12px;">
                    <option value="">ทั้งหมด</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-light w-100 border-opacity-25 py-2" style="border-radius:12px;" onclick="resetFilter()">ล้างการค้นหา</button>
            </div>
        </div>
    </div>

    <div class="main-card shadow-lg">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">รูป</th>
                        <th>รายละเอียด</th>
                        <th class="text-center">ราคา</th>
                        <th class="text-center">สต็อก</th>
                        <th class="text-end pe-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="bookTableBody">
                    </tbody>
            </table>
        </div>
    </div>

    <script>
        async function fetchBooks() {
            const search = document.getElementById('searchInput').value;
            const cat = document.getElementById('catFilter').value;
            const tableBody = document.getElementById('bookTableBody');
            try {
                const response = await fetch(`search_books_ajax.php?search=${encodeURIComponent(search)}&cat=${cat}`);
                tableBody.innerHTML = await response.text();
            } catch (error) { 
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-dark fw-bold">เกิดข้อผิดพลาด</td></tr>'; 
            }
        }
        function resetFilter() {
            document.getElementById('searchInput').value = '';
            document.getElementById('catFilter').value = '';
            fetchBooks();
        }
        document.getElementById('searchInput').addEventListener('input', fetchBooks);
        document.getElementById('catFilter').addEventListener('change', fetchBooks);
        window.onload = fetchBooks;
    </script>
<?php
};

// --- 3. เรียกใช้งาน Template และส่งฟังก์ชันเนื้อหาเข้าไป ---
render_admin_layout("จัดการคลังหนังสือ", $content, $conn, "books"); 
?>
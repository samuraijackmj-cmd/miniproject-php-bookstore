<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

require_once '../config/db.php';

// ตรวจสอบ ID
if (!isset($_GET['id'])) { header("Location: books.php"); exit; }
$id = intval($_GET['id']);

// ดึงข้อมูลหนังสือ
$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = :id");
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) { header("Location: books.php"); exit; }

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

$msg_error = "";

// --- ส่วนทำงานเมื่อกดบันทึก ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isbn = trim($_POST['isbn']);
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $price = floatval($_POST['price']);
    $discount_percent = !empty($_POST['discount_percent']) ? intval($_POST['discount_percent']) : 0;
    $stock = intval($_POST['stock_quantity']);
    $category_id = intval($_POST['category_id']);
    $description = trim($_POST['description']);
    $image_name = $book['image']; 

    // 1. จัดการรูปภาพ
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $new_image_name = "book_" . uniqid() . "." . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_image_name)) {
                if (!empty($book['image']) && file_exists($upload_dir . $book['image'])) { 
                    @unlink($upload_dir . $book['image']); 
                }
                $image_name = $new_image_name;
            }
        } else {
            $msg_error = "นามสกุลไฟล์รูปภาพไม่ถูกต้อง";
        }
    }

    // 2. อัปเดตข้อมูล
    if (empty($msg_error)) {
        try {
            $sql = "UPDATE books SET 
                    isbn = :isbn, 
                    title = :title, 
                    author = :author, 
                    price = :price, 
                    discount_percent = :discount, 
                    stock_quantity = :stock, 
                    category_id = :cat_id, 
                    description = :desc, 
                    image = :img 
                    WHERE book_id = :id";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                ':isbn' => $isbn, 
                ':title' => $title, 
                ':author' => $author, 
                ':price' => $price, 
                ':discount' => $discount_percent, 
                ':stock' => $stock, 
                ':cat_id' => $category_id, 
                ':desc' => $description, 
                ':img' => $image_name, 
                ':id' => $id
            ]);

            if ($result) {
                echo "<script>window.location.href='books.php?msg=updated';</script>";
                exit;
            }
        } catch (PDOException $e) {
            $msg_error = "เกิดข้อผิดพลาดทางเทคนิค: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลหนังสือ | Admin Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600&display=swap');
        :root { --bg-body: #0f172a; --primary: #6366f1; --secondary: #a855f7; --glass-bg: rgba(30, 41, 59, 0.7); --glass-border: rgba(255, 255, 255, 0.1); }
        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-body); color: #fff; min-height: 100vh; background-image: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%); }
        
        .admin-sidebar { background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); border-right: 1px solid var(--glass-border); min-height: 100vh; padding: 2rem 1.5rem; position: fixed; width: 280px; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 2rem; transition: 0.3s; }
        .glass-card { background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 24px; padding: 32px; }
        .nav-link { display: flex; align-items: center; gap: 14px; padding: 14px 18px; color: #cbd5e1 !important; border-radius: 16px; margin-bottom: 8px; transition: 0.3s; }
        .nav-link.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white !important; }
        
        .form-control, .form-select { background: rgba(255, 255, 255, 0.05) !important; border: 1px solid var(--glass-border) !important; color: #fff !important; border-radius: 12px; padding: 12px 16px; }
        .form-control:focus, .form-select:focus { background: rgba(255, 255, 255, 0.1) !important; border-color: var(--primary) !important; box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25) !important; }
        
        .current-img { max-width: 150px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
        .upload-area { border: 2px dashed var(--glass-border); border-radius: 16px; padding: 1.5rem; text-align: center; cursor: pointer; transition: 0.3s; }
        .upload-area:hover { border-color: var(--primary); background: rgba(99, 102, 241, 0.05); }
        .btn-primary-grad { background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none; color: #fff !important; padding: 12px 32px; border-radius: 50px; font-weight: 600; transition: 0.3s; }
        .btn-cancel { color: #cbd5e1; text-decoration: none; padding: 12px 24px; transition: 0.3s; }
        .btn-cancel:hover { color: #fff; }
        
        /* เพิ่ม CSS สำหรับ Placeholder ให้เป็นสีขาวจางๆ เพื่อให้อ่านง่าย */
        ::placeholder { color: rgba(255, 255, 255, 0.5) !important; opacity: 1; }
        
        @media (max-width: 991px) { .admin-sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<div class="admin-sidebar d-flex flex-column" id="sidebar">
    <div class="mb-5 px-2">
        <h5 class="m-0 fw-bold">BOOK<span class="text-primary">STORE</span></h5>
        <span class="text-white small">Admin Console</span>
    </div>
    <nav class="nav flex-column gap-1">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="orders.php" class="nav-link"><i class="bi bi-cart-check-fill"></i> Orders</a>
        <a href="books.php" class="nav-link active"><i class="bi bi-journal-album"></i> Products</a>
        <a href="categories.php" class="nav-link"><i class="bi bi-bookmarks-fill"></i> Categories</a>
        <a href="../logout.php" class="nav-link text-danger mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
    
    <?php if($msg_error): ?>
        <div class="alert alert-danger rounded-4 border-0 shadow-lg mb-4 p-3 d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-octagon-fill fs-4"></i>
            <div>
                <strong>เกิดข้อผิดพลาด!</strong> <br>
                <?php echo $msg_error; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="glass-card animate__animated animate__fadeInUp">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="books.php" class="btn btn-outline-light rounded-circle" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;"><i class="bi bi-arrow-left"></i></a>
            <h4 class="m-0 text-white">แก้ไขข้อมูลหนังสือ #<?php echo $id; ?></h4>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-white small text-uppercase fw-bold">ISBN / รหัสสินค้า</label>
                            <input type="text" name="isbn" class="form-control" value="<?php echo htmlspecialchars($book['isbn']); ?>" required>
                            <div class="form-text text-white-50 small">ใส่ขีด - หากไม่มีรหัส</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white small text-uppercase fw-bold">หมวดหมู่</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $book['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white small text-uppercase fw-bold">ชื่อหนังสือ</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white small text-uppercase fw-bold">ผู้แต่ง</label>
                        <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label text-white small text-uppercase fw-bold">ราคา (บาท)</label>
                            <input type="number" step="0.01" name="price" id="priceInput" class="form-control" value="<?php echo $book['price']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white small text-uppercase fw-bold">ส่วนลด (%)</label>
                            <input type="number" name="discount_percent" id="discountInput" class="form-control" min="0" max="100" value="<?php echo $book['discount_percent']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white small text-uppercase fw-bold">สต็อกคงเหลือ</label>
                            <input type="number" name="stock_quantity" class="form-control" value="<?php echo $book['stock_quantity']; ?>" required>
                        </div>
                    </div>

                    <div class="p-3 rounded-3 mb-4" style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-white opacity-75">ราคาขายจริง (หลังหักส่วนลด):</span>
                            <span id="finalPriceDisplay" class="fw-bold text-primary fs-5">฿0.00</span>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-white small text-uppercase fw-bold">เรื่องย่อ / รายละเอียด</label>
                        <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($book['description']); ?></textarea>
                    </div>
                </div>

                <div class="col-lg-4 text-center">
                    <h6 class="mb-4 text-white text-uppercase small fw-bold">รูปภาพปกหนังสือ</h6>
                    <div class="mb-4 position-relative d-inline-block">
                        <?php 
                            $img_path = "../uploads/" . $book['image'];
                            $display_img = (!empty($book['image']) && file_exists($img_path)) ? $img_path : "../assets/no-image.png";
                        ?>
                        <img id="mainPreview" src="<?php echo $display_img; ?>" class="current-img mb-3">
                    </div>

                    <label class="upload-area w-100" for="imgInput">
                        <i class="bi bi-cloud-arrow-up fs-2 text-primary"></i>
                        <div class="small mt-2 text-white">คลิกเพื่อเปลี่ยนรูปภาพ</div>
                        <div class="text-white-50" style="font-size: 0.75rem;">รองรับ JPG, PNG, WEBP</div>
                        <input type="file" name="image" id="imgInput" accept="image/*" style="display:none">
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5 pt-4 border-top border-secondary border-opacity-25">
                <a href="books.php" class="btn btn-cancel">ยกเลิก</a>
                <button type="submit" class="btn btn-primary-grad px-5 shadow-lg">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</div>

<script>
    const pIn = document.getElementById('priceInput');
    const dIn = document.getElementById('discountInput');
    const fOut = document.getElementById('finalPriceDisplay');

    function calc() {
        const p = parseFloat(pIn.value) || 0;
        const d = parseFloat(dIn.value) || 0;
        const total = p - (p * d / 100);
        fOut.innerHTML = '฿' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    pIn.oninput = calc; dIn.oninput = calc; calc();

    document.getElementById('imgInput').onchange = function() {
        const [file] = this.files;
        if (file) {
            document.getElementById('mainPreview').src = URL.createObjectURL(file);
        }
    };
</script>
</body>
</html>
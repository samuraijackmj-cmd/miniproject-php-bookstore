<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้
$user_profile = $conn->prepare("SELECT full_name, profile_image FROM users WHERE user_id = ?");
$user_profile->execute([$user_id]);
$user_profile = $user_profile->fetch();

// ดึงรายการโปรด
$wishlist_stmt = $conn->prepare("
    SELECT b.*, w.added_date 
    FROM wishlist w 
    JOIN books b ON w.book_id = b.book_id 
    WHERE w.user_id = ? 
    ORDER BY w.added_date DESC
");
$wishlist_stmt->execute([$user_id]);
$wishlist_items = $wishlist_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการโปรด | BookStore</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-body: #0f172a;
            --bg-panel: #1e293b;
            --primary: #6366f1;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
        }

        .wishlist-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }

        .wishlist-card:hover {
            border-color: rgba(99, 102, 241, 0.6);
            box-shadow: 0 8px 20px -5px rgba(99, 102, 241, 0.3);
        }

        .wishlist-img {
            width: 150px;
            height: 220px;
            object-fit: cover;
        }

        .wishlist-content {
            flex: 1;
            padding: 20px;
        }

        .wishlist-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
        }

        .wishlist-author {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .wishlist-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #a5b4fc;
            margin-bottom: 12px;
        }

        .btn-group-wishlist {
            display: flex;
            gap: 12px;
        }

        .btn-wish-action {
            padding: 8px 18px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-wish-action:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="mb-5">
            <h1 class="mb-2">
                <i class="bi bi-heart-fill text-danger me-2"></i>รายการโปรด
            </h1>
            <p class="text-muted">จำนวนรายการ: <strong><?= count($wishlist_items) ?></strong></p>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-heart"></i>
                </div>
                <h3 class="mb-3">ยังไม่มีรายการโปรด</h3>
                <p class="text-muted mb-4">ไปค้นหาหนังสือที่คุณชอบและเพิ่มเข้ารายการโปรด</p>
                <a href="index.php" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-shop me-2"></i>ไปเลือกซื้อหนังสือ
                </a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($wishlist_items as $item): 
                    $price = $item['price'] - ($item['price'] * $item['discount_percent'] / 100);
                    $is_out = ($item['stock_quantity'] <= 0);
                ?>
                    <div class="col-12">
                        <div class="wishlist-card d-flex">
                            <a href="product_detail.php?id=<?= $item['book_id'] ?>" class="text-decoration-none">
                                <img src="uploads/<?= $item['image'] ?>" class="wishlist-img" 
                                     style="<?= $is_out ? 'filter: grayscale(1); opacity: 0.5;' : '' ?>" 
                                     onerror="this.src='assets/no-image.png'">
                            </a>
                            
                            <div class="wishlist-content">
                                <h3 class="wishlist-title">
                                    <a href="product_detail.php?id=<?= $item['book_id'] ?>" class="text-decoration-none text-white">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </h3>
                                
                                <div class="wishlist-author">
                                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($item['author']) ?>
                                </div>

                                <div class="wishlist-price">
                                    ฿<?= number_format($price) ?>
                                    <?php if($item['discount_percent'] > 0): ?>
                                    <span class="badge bg-danger ms-2">-<?= $item['discount_percent'] ?>%</span>
                                    <?php endif; ?>
                                </div>

                                <div class="btn-group-wishlist mt-3">
                                    <?php if (!$is_out): ?>
                                    <button class="btn-wish-action" onclick="addToCart(<?= $item['book_id'] ?>)">
                                        <i class="bi bi-cart-plus-fill me-1"></i>เพิ่มตะกร้า
                                    </button>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">สินค้าหมด</span>
                                    <?php endif; ?>
                                    
                                    <button class="btn-wish-action" style="background: rgba(236, 72, 153, 0.2); color: #f472b6;" 
                                            onclick="removeFromWishlist(<?= $item['book_id'] ?>)">
                                        <i class="bi bi-trash-fill me-1"></i>ลบออก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function addToCart(id) {
            $.ajax({
                url: 'cart_action.php?action=add&id=' + id + '&ajax=1',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'เพิ่มลงตะกร้าสำเร็จ!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function() { 
                    window.location = 'cart_action.php?action=add&id='+id; 
                }
            });
        }

        function removeFromWishlist(id) {
            Swal.fire({
                title: 'ลบออกจากรายการโปรด?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ใช่',
                cancelButtonText: 'ไม่'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'wishlist_action.php?action=toggle&id=' + id,
                        type: 'POST',
                        dataType: 'json',
                        success: function(res) {
                            if(res.status === 'success') {
                                location.reload();
                            }
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>

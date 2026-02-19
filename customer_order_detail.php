<?php
session_start();
require_once 'config/db.php';

// 1. ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. ตรวจสอบว่ามี ID ส่งมาไหม
if (!isset($_GET['id'])) {
    header("Location: order_history.php");
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 3. ดึงข้อมูลออเดอร์ และ **ตรวจสอบว่าเป็นของ User นี้จริงหรือไม่** (สำคัญมาก!)
$sql = "SELECT * FROM orders WHERE order_id = :oid AND user_id = :uid";
$stmt = $conn->prepare($sql);
$stmt->execute([':oid' => $order_id, ':uid' => $user_id]);
$order = $stmt->fetch();

// ถ้าไม่เจอออเดอร์ หรือไม่ใช่ของตัวเอง ให้เด้งออก
if (!$order) {
    echo "<script>alert('ไม่พบคำสั่งซื้อ หรือคุณไม่มีสิทธิ์เข้าถึง'); window.location='order_history.php';</script>";
    exit;
}

// 4. ดึงรายการสินค้าในออเดอร์นั้น
$sql_items = "SELECT order_items.*, books.title, books.image 
              FROM order_items 
              JOIN books ON order_items.book_id = books.book_id 
              WHERE order_id = :oid";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->execute([':oid' => $order_id]);
$items = $stmt_items->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดคำสั่งซื้อ #<?php echo $order_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap');
        body { font-family: 'Prompt', sans-serif; background-color: #f5f5f5; }
        .img-thumb { width: 60px; height: 80px; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0">📦 รายละเอียดคำสั่งซื้อ #<?php echo $order_id; ?></h3>
            <a href="order_history.php" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left"></i> ย้อนกลับ
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="m-0 fw-bold">รายการสินค้า</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">สินค้า</th>
                                        <th>ราคาต่อหน่วย</th>
                                        <th>จำนวน</th>
                                        <th class="text-end pe-4">รวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <?php if($item['image']): ?>
                                                    <img src="uploads/<?php echo $item['image']; ?>" class="img-thumb border me-3">
                                                <?php else: ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center img-thumb border me-3 text-secondary">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($item['title']); ?></span>
                                            </div>
                                        </td>
                                        <td>฿<?php echo number_format($item['price'], 2); ?></td>
                                        <td>x <?php echo $item['quantity']; ?></td>
                                        <td class="text-end pe-4 fw-bold">฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold py-3">ยอดรวมสุทธิ</td>
                                        <td class="text-end pe-4 fw-bold text-primary fs-5">฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 mb-3">
                    <div class="card-body">
                        <h6 class="text-muted mb-3">สถานะคำสั่งซื้อ</h6>
                        <?php 
                        $status = $order['status'];
                        $badge_color = match($status) {
                            'pending' => 'bg-warning text-dark',
                            'paid' => 'bg-info text-white',
                            'shipped' => 'bg-primary',
                            'completed' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        $status_text = match($status) {
                            'pending' => 'รอตรวจสอบการชำระเงิน',
                            'paid' => 'ชำระเงินแล้ว / รอจัดส่ง',
                            'shipped' => 'จัดส่งเรียบร้อย',
                            'completed' => 'รายการสำเร็จ',
                            'cancelled' => 'ยกเลิก',
                            default => $status
                        };
                        ?>
                        <div class="alert <?php echo str_replace('bg-', 'alert-', $badge_color); ?> mb-0 text-center fw-bold border-0">
                            <?php echo $status_text; ?>
                        </div>
                        <div class="text-center mt-3 text-muted small">
                            สั่งซื้อเมื่อ: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </div>
                    </div>
                </div>

                <?php if(!empty($order['slip_image'])): ?>
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold">หลักฐานการโอนเงิน</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="uploads/slips/<?php echo $order['slip_image']; ?>" class="img-fluid rounded border mb-2" alt="Slip">
                        <br>
                        <a href="uploads/slips/<?php echo $order['slip_image']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-zoom-in"></i> ดูรูปขยาย
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
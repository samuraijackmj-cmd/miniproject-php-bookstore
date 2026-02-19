<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
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

// 1. ส่วนประมวลผลอัปเดตออเดอร์และบันทึกประวัติ (Log)
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

    // ✅ แก้ไขการตรวจสอบสถานะให้ใช้ stripos แทน
    $check_new = trim($new_status);
    $check_old = trim($old_status_raw);

    // ✅ ตรวจสอบการลดสต็อก (เมื่อเปลี่ยนจากรอตรวจสอบ → ชำระเงิน/จัดส่ง/สำเร็จ)
    if ((stripos($check_old, 'รอตรวจสอบ') !== false || stripos($check_old, 'pending') !== false) && 
        (stripos($check_new, 'ชำระเงิน') !== false || stripos($check_new, 'จัดส่ง') !== false || stripos($check_new, 'สำเร็จ') !== false || stripos($check_new, 'completed') !== false)) {
        
        $stmt_items = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = :id");
        $stmt_items->execute([':id' => $order_id]);
        foreach ($stmt_items->fetchAll() as $item) {
            $conn->prepare("UPDATE books SET stock_quantity = stock_quantity - :qty WHERE book_id = :bid")
                 ->execute([':qty' => $item['quantity'], ':bid' => $item['book_id']]);
        }
    }

    // ✅ ตรวจสอบการคืนสต็อก (เมื่อเปลี่ยนจากชำระเงิน/จัดส่ง → ยกเลิก)
    if ((stripos($check_old, 'ชำระเงิน') !== false || stripos($check_old, 'จัดส่ง') !== false || stripos($check_old, 'paid') !== false || stripos($check_old, 'shipped') !== false) && 
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

// 2. ดึงข้อมูลออเดอร์และสินค้า
$stmt = $conn->prepare("SELECT orders.*, users.full_name, users.phone, users.address as user_address FROM orders JOIN users ON orders.user_id = users.user_id WHERE orders.order_id = :id");
$stmt->execute([':id' => $order_id]);
$order = $stmt->fetch();

$stmt = $conn->prepare("SELECT order_items.*, books.title, books.image FROM order_items JOIN books ON order_items.book_id = books.book_id WHERE order_id = :id");
$stmt->execute([':id' => $order_id]);
$items = $stmt->fetchAll();

// 3. ดึงประวัติการเปลี่ยนแปลง
$stmt_logs = $conn->prepare("SELECT * FROM order_logs WHERE order_id = ? ORDER BY created_at DESC");
$stmt_logs->execute([$order_id]);
$logs = $stmt_logs->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Order #<?php echo $order_id; ?> | Admin Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');
        :root { --bg-dark: #0f172a; --glass: rgba(255, 255, 255, 0.05); --border: rgba(255, 255, 255, 0.1); --primary-grad: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); }
        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-dark); background-image: radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.15) 0%, transparent 50%), radial-gradient(circle at 100% 100%, rgba(168, 85, 247, 0.15) 0%, transparent 50%); color: #ffffff; min-height: 100vh; overflow-x: hidden; }
        
        .navbar { background: rgba(15, 23, 42, 0.8) !important; backdrop-filter: blur(15px); border-bottom: 1px solid var(--border); }
        .navbar-brand { font-weight: 700; background: var(--primary-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-logout-red { background: #ef4444; color: #fff !important; border-radius: 50px; padding: 8px 22px; font-weight: 600; text-decoration: none; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
        .btn-home-outline { border: 1.5px solid rgba(255, 255, 255, 0.2); color: #fff !important; border-radius: 50px; padding: 8px 22px; text-decoration: none; }

        .glass-card { background: var(--glass); backdrop-filter: blur(25px); border: 1px solid var(--border); border-radius: 24px; padding: 25px; }
        .table-container-white { background: rgba(255, 255, 255, 0.95); border-radius: 20px; overflow: hidden; }
        .table { color: #000 !important; margin-bottom: 0; }
        .table thead th { background: rgba(0,0,0,0.05); color: #475569; padding: 1.2rem; font-weight: 600; border: none; }
        .table tbody td { border-bottom: 1px solid rgba(0,0,0,0.05); padding: 1.2rem; vertical-align: middle; }

        .btn-save-high-contrast { background: #ffffff !important; color: #000000 !important; border: none; border-radius: 12px; font-weight: 800; padding: 15px; transition: 0.3s; box-shadow: 0 10px 25px rgba(255, 255, 255, 0.15); }
        .btn-save-high-contrast:hover { background: #f1f5f9 !important; transform: translateY(-2px); }

        .btn-outline-glass { background: rgba(255, 255, 255, 0.05); color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 12px; padding: 12px; font-weight: 600; transition: 0.3s; text-decoration: none; display: block; text-align: center; }
        .btn-outline-glass:hover { background: rgba(255, 255, 255, 0.1); border-color: #fff; transform: scale(1.02); }

        .log-item { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 12px 18px; margin-bottom: 10px; border-left: 4px solid #6366f1; }
        .section-title { font-weight: 700; border-left: 5px solid #6366f1; padding-left: 15px; }
        #tracking_area { display: none; }

        /* 🆕 Payment Method Badge */
        .payment-badge { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            padding: 10px 18px; 
            border-radius: 12px; 
            font-weight: 600; 
            font-size: 0.95rem;
        }
        .payment-badge.bank { 
            background: rgba(59, 130, 246, 0.2); 
            color: #60a5fa; 
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .payment-badge.cod { 
            background: rgba(16, 185, 129, 0.2); 
            color: #34d399; 
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* 🆕 Info Box */
        .info-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .info-box-title {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .info-box-content {
            color: #fff;
            font-weight: 500;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark py-3 sticky-top">
        <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
            <div class="navbar-brand fs-4 d-flex align-items-center"><i class="bi bi-grid-1x2-fill me-3"></i>ADMIN CONSOLE</div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white small opacity-75 d-none d-md-inline"><i class="bi bi-person-circle me-1"></i> admin</span>
                <a href="../index.php" class="btn-home-outline"><i class="bi bi-house-door me-1"></i>หน้าบ้าน</a>
                <a href="../logout.php" class="btn btn-logout-red">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="glass-card mb-4">
                    <h2 class="section-title mb-4 text-white">รายการออเดอร์ #<?php echo $order_id; ?></h2>
                    <div class="table-container-white shadow-lg">
                        <table class="table align-middle">
                            <thead>
                                <tr><th class="ps-4">รายการหนังสือ</th><th class="text-center">จำนวน</th><th class="text-end pe-4">รวม</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="../uploads/<?php echo $item['image']; ?>" style="width:40px; height:55px; object-fit:cover; border-radius:5px;">
                                            <span class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">x<?php echo $item['quantity']; ?></td>
                                    <td class="text-end pe-4 fw-bold text-primary">฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot style="background: rgba(99,102,241,0.05);">
                                <tr><td colspan="2" class="text-end fw-bold py-3">ยอดรวมสุทธิ:</td><td class="text-end pe-4 py-3 fw-bold fs-5 text-primary">฿<?php echo number_format($order['total_amount'], 2); ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- 🆕 ข้อมูลลูกค้าและการจัดส่ง -->
                <div class="glass-card mb-4">
                    <h5 class="mb-4 opacity-75 d-flex align-items-center">
                        <i class="bi bi-truck me-3 text-warning"></i>ข้อมูลการจัดส่ง
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-box-title">
                                    <i class="bi bi-person"></i>ชื่อผู้รับ
                                </div>
                                <div class="info-box-content">
                                    <?php echo htmlspecialchars($order['fullname'] ?? $order['full_name']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-box-title">
                                    <i class="bi bi-telephone"></i>เบอร์โทรศัพท์
                                </div>
                                <div class="info-box-content">
                                    <?php echo htmlspecialchars($order['phone']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-box">
                                <div class="info-box-title">
                                    <i class="bi bi-geo-alt"></i>ที่อยู่จัดส่ง
                                </div>
                                <div class="info-box-content">
                                    <?php echo nl2br(htmlspecialchars($order['address'] ?? $order['user_address'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card">
                    <h5 class="mb-4 opacity-75 d-flex align-items-center"><i class="bi bi-clock-history me-3 text-info"></i>ประวัติการเปลี่ยนสถานะ</h5>
                    <div class="log-list">
                        <?php if (empty($logs)): ?>
                            <div class="text-center py-4 opacity-25">ยังไม่มีประวัติการบันทึก</div>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <div class="log-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-white-50 small me-3"><?php echo date('H:i', strtotime($log['created_at'])); ?> น.</span>
                                    <span class="text-info"><?php echo $log['old_status']; ?></span>
                                    <i class="bi bi-arrow-right mx-2 text-white-50"></i>
                                    <span class="text-success fw-bold"><?php echo $log['new_status']; ?></span>
                                </div>
                                <span class="small opacity-50"><i class="bi bi-person me-1"></i><?php echo $log['changed_by']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="glass-card mb-4" style="border-top: 4px solid #6366f1;">
                    <h5 class="mb-4 text-white">จัดการออเดอร์</h5>

                    <!-- 🆕 แสดงวิธีการชำระเงิน -->
                    <div class="mb-4">
                        <p class="small text-white-50 mb-2">วิธีการชำระเงิน:</p>
                        <?php 
                        $payment_method = $order['payment_method'] ?? 'bank_transfer';
                        if ($payment_method === 'cod'): 
                        ?>
                            <div class="payment-badge cod">
                                <i class="bi bi-cash-coin"></i>
                                <span>เก็บเงินปลายทาง (COD)</span>
                            </div>
                        <?php else: ?>
                            <div class="payment-badge bank">
                                <i class="bi bi-bank"></i>
                                <span>โอนเงินผ่านธนาคาร</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="POST">
                        <div class="mb-4">
                            <p class="small text-white-50 mb-2">สถานะปัจจุบัน:</p>
                            <?php
                            $current_status = trim($order['status']); 
                            
                            // ✅ แสดง Badge พร้อม Emoji เหมือนหน้า orders.php
                            if (stripos($current_status, 'สำเร็จ') !== false || stripos($current_status, 'completed') !== false || stripos($current_status, '✅') !== false) {
                                echo '<span class="badge bg-success rounded-pill px-3 py-2 fs-6 mb-3">✅ สำเร็จ</span>';
                            } 
                            elseif (stripos($current_status, 'ยกเลิก') !== false || stripos($current_status, 'cancelled') !== false || stripos($current_status, '❌') !== false) {
                                echo '<span class="badge bg-danger rounded-pill px-3 py-2 fs-6 mb-3">❌ ยกเลิก</span>';
                            }
                            elseif (stripos($current_status, 'จัดส่ง') !== false || stripos($current_status, 'shipped') !== false || stripos($current_status, '🚚') !== false) {
                                echo '<span class="badge bg-info text-white rounded-pill px-3 py-2 fs-6 mb-3">🚚 จัดส่งแล้ว</span>';
                            }
                            elseif (stripos($current_status, 'ชำระเงิน') !== false || stripos($current_status, 'paid') !== false || stripos($current_status, '🔵') !== false) {
                                echo '<span class="badge bg-primary rounded-pill px-3 py-2 fs-6 mb-3">🔵 ชำระเงินแล้ว</span>';
                            }
                            else {
                                echo '<span class="badge bg-warning text-dark rounded-pill px-3 py-2 fs-6 mb-3">🟡 รอตรวจสอบ</span>';
                            }
                            ?>
                            
                            <label class="small text-white-50 mb-2 mt-3">เปลี่ยนสถานะเป็น:</label>
                            <select name="status" id="status_dropdown" class="form-select bg-dark text-white border-secondary py-2" onchange="checkStatus(this.value)">
                                <option value="🟡 รอตรวจสอบ" <?php echo ($order['status'] == '🟡 รอตรวจสอบ') ? 'selected' : ''; ?>>🟡 รอตรวจสอบ</option>
                                <option value="🔵 ชำระเงินแล้ว" <?php echo ($order['status'] == '🔵 ชำระเงินแล้ว') ? 'selected' : ''; ?>>🔵 ชำระเงินแล้ว</option>
                                <option value="🚚 จัดส่งแล้ว" <?php echo ($order['status'] == '🚚 จัดส่งแล้ว') ? 'selected' : ''; ?>>🚚 จัดส่งแล้ว</option>
                                <option value="✅ สำเร็จ" <?php echo ($order['status'] == '✅ สำเร็จ') ? 'selected' : ''; ?>>✅ สำเร็จ</option>
                                <option value="❌ ยกเลิก" <?php echo ($order['status'] == '❌ ยกเลิก') ? 'selected' : ''; ?>>❌ ยกเลิก</option>
                            </select>
                        </div>

                        <div id="tracking_area" class="mb-4 p-3 rounded" style="background: rgba(255,255,255,0.05); border: 1px dashed var(--border);">
                            <label class="small text-info mb-2">เลขพัสดุ Tracking</label>
                            <div class="input-group">
                                <input type="text" name="tracking_number" id="tracking_input" class="form-control" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                                <button class="btn btn-primary" type="button" onclick="randomTracking()"><i class="bi bi-shuffle"></i></button>
                            </div>
                        </div>

                        <button type="submit" name="update_status" class="btn btn-save-high-contrast w-100 mb-3 shadow-lg">
                            <i class="bi bi-arrow-repeat me-2"></i>อัปเดตสถานะออเดอร์
                        </button>

                        <a href="orders.php" class="btn-outline-glass">
                            <i class="bi bi-arrow-left-circle me-2"></i>กลับไปจัดการออเดอร์ทั้งหมด
                        </a>
                    </form>
                </div>

                <!-- 🆕 แสดงสลิปเฉพาะเมื่อชำระเงินผ่านธนาคาร -->
                <?php if($payment_method === 'bank_transfer' && !empty($order['slip_image'])): ?>
                <div class="glass-card">
                    <h6 class="small mb-3 opacity-50">
                        <i class="bi bi-receipt me-2"></i>หลักฐานการชำระเงิน
                    </h6>
                    <a href="../uploads/slips/<?php echo $order['slip_image']; ?>" target="_blank">
                        <img src="../uploads/slips/<?php echo $order['slip_image']; ?>" class="img-fluid rounded-3 shadow" style="border: 2px solid rgba(99, 102, 241, 0.3);">
                    </a>
                </div>
                <?php endif; ?>

                <!-- 🆕 ข้อความสำหรับ COD -->
                <?php if($payment_method === 'cod'): ?>
                <div class="glass-card" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-info-circle-fill text-success fs-4"></i>
                        <div>
                            <h6 class="text-success mb-2">ชำระเงินปลายทาง</h6>
                            <p class="small mb-0 opacity-75">ลูกค้าจะชำระเงินเมื่อได้รับสินค้า ณ หน้าบ้าน</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
        window.onload = function() { checkStatus(document.getElementById('status_dropdown').value); };
    </script>
</body>
</html>
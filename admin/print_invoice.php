<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { exit('สิทธิ์ไม่ถูกต้อง'); }
require_once '../config/db.php';

if (!isset($_GET['id'])) { exit('ไม่พบเลขที่ออเดอร์'); }
$order_id = $_GET['id'];

// ดึงข้อมูลออเดอร์และลูกค้า
$stmt = $conn->prepare("SELECT orders.*, users.full_name, users.phone, users.address 
                        FROM orders JOIN users ON orders.user_id = users.user_id 
                        WHERE order_id = :id");
$stmt->execute([':id' => $order_id]);
$order = $stmt->fetch();

// ดึงรายการสินค้า
$stmt = $conn->prepare("SELECT order_items.*, books.title 
                        FROM order_items JOIN books ON order_items.book_id = books.book_id 
                        WHERE order_id = :id");
$stmt->execute([':id' => $order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?php echo htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; font-size: 14px; background: #fff; }
        .invoice-box { padding: 30px; border: 1px solid #eee; max-width: 800px; margin: auto; }
        .table thead { background: #f8f9fa; }
        @media print {
            .no-print { display: none; } /* ซ่อนปุ่มเวลาพิมพ์ */
            .invoice-box { border: none; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()"> <div class="container my-4 no-print text-center">
        <button onclick="window.print()" class="btn btn-primary">🖨️ กดพิมพ์ใบเสร็จ</button>
        <button onclick="window.close()" class="btn btn-secondary">ปิดหน้าต่างนี้</button>
    </div>

    <div class="invoice-box shadow-sm">
        <div class="row mb-4">
            <div class="col-6">
                <h2 class="fw-bold text-primary">INVOICE</h2>
                <p class="m-0"><strong>ร้านหนังสือ Online Store</strong></p>
                <p class="m-0">123 ถนนตัวอย่าง แขวงตัวอย่าง เขตตัวอย่าง กรุงเทพฯ 10000</p>
            </div>
            <div class="col-6 text-end">
                <h5 class="m-0">เลขที่ออเดอร์: <?php echo $order['order_number'] ?: '#'.$order['order_id']; ?></h5>
                <p class="m-0">วันที่สั่งซื้อ: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <hr>

        <div class="row mb-4">
            <div class="col-12">
                <h6 class="fw-bold">ข้อมูลลูกค้า (ที่อยู่จัดส่ง)</h6>
                <p class="m-0"><?php echo htmlspecialchars($order['full_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8'); ?>)</p>
                <p class="m-0 text-muted"><?php echo nl2br(htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        </div>

        <table class="table table-bordered align-middle">
            <thead>
                <tr class="text-center">
                    <th width="50">#</th>
                    <th>รายการสินค้า</th>
                    <th width="100">ราคา/หน่วย</th>
                    <th width="80">จำนวน</th>
                    <th width="120">รวมเงิน</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($items as $item): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end">฿<?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end fw-bold">฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end fw-bold">ยอดสุทธิรวมทั้งสิ้น</td>
                    <td class="text-end fw-bold text-primary fs-5">฿<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-5 row">
            <div class="col-6 text-center">
                <br><br>
                <p>__________________________</p>
                <p>( ผู้รับของ / ลูกค้า )</p>
            </div>
            <div class="col-6 text-center">
                <br><br>
                <p>__________________________</p>
                <p>( ผู้ส่งของ / แอดมิน )</p>
            </div>
        </div>
    </div>

</body>
</html>
<?php
// admin/fetch_orders_realtime.php

// 1. เริ่ม Buffer ทันที (สำคัญมาก! ช่วยกันขยะหลุดไปปนกับ JSON)
ob_start();

session_start();
// ปิด Error Report ชั่วคราว เพื่อให้ JSON สะอาดที่สุด
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/db.php';

// 2. ตั้ง Header บังคับไม่ให้ Browser จำค่าเดิม (Anti-Cache)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json; charset=utf-8');

// รับค่าค้นหาเดิม (เพื่อให้ Realtime ไม่ล้างค่าที่พี่กำลังค้นหา)
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

// --- Logic 1: นับจำนวนแจ้งเตือน ---
$low_stock_count = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$pending_orders_count = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')")->fetchColumn();
$total_alerts = $pending_orders_count + $low_stock_count;

// --- Logic 2: Query ออเดอร์ ---
$sql = "SELECT orders.*, users.username, users.full_name 
        FROM orders 
        LEFT JOIN users ON orders.user_id = users.user_id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (users.full_name LIKE :search OR users.username LIKE :search OR orders.order_id LIKE :search OR orders.order_number LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status_filter != 'all') {
    if ($status_filter == 'pending') $sql .= " AND orders.status IN ('pending', 'รอตรวจสอบ')";
    elseif ($status_filter == 'paid') $sql .= " AND orders.status IN ('paid', 'ชำระเงินแล้ว', 'waiting')";
    elseif ($status_filter == 'shipped') $sql .= " AND orders.status IN ('shipped', 'จัดส่งแล้ว')";
    elseif ($status_filter == 'cancelled') $sql .= " AND orders.status IN ('cancelled', 'ยกเลิก')";
}

$sql .= " ORDER BY orders.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// 3. ล้าง Buffer อีกรอบก่อนสร้าง HTML เพื่อความชัวร์
ob_clean();

// 4. สร้าง HTML ตาราง
if(count($orders) > 0): foreach ($orders as $order): 
    $status = trim($order['status']);
    $badge_class = 'st-warning'; $status_text = $status;

    if (stripos($status, 'สำเร็จ') !== false || stripos($status, 'completed') !== false) {
        $badge_class = 'st-success'; $status_text = 'สำเร็จ';
    } elseif (stripos($status, 'ยกเลิก') !== false || stripos($status, 'cancelled') !== false) {
        $badge_class = 'st-danger'; $status_text = 'ยกเลิก';
    } elseif (stripos($status, 'จัดส่ง') !== false || stripos($status, 'shipped') !== false) {
        $badge_class = 'st-info'; $status_text = 'จัดส่งแล้ว';
    } elseif (stripos($status, 'ชำระเงิน') !== false || stripos($status, 'paid') !== false) {
        $badge_class = 'st-primary'; $status_text = 'ชำระเงินแล้ว';
    }
?>
<tr class="animate__animated animate__fadeIn">
    <td class="ps-4">
        <span class="order-id-badge">
            <?php echo !empty($order['order_number']) ? htmlspecialchars($order['order_number']) : '#ORD-'.str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?>
        </span>
    </td>
    <td>
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center border border-secondary" style="width:36px;height:36px;">
                <span class="text-white small fw-bold"><?php echo strtoupper(substr($order['username'] ?? 'U', 0, 1)); ?></span>
            </div>
            <div>
                <div class="fw-bold text-white small"><?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></div>
                <div class="text-muted" style="font-size:0.75rem;">@<?php echo htmlspecialchars($order['username']); ?></div>
            </div>
        </div>
    </td>
    <td class="text-muted small">
        <i class="bi bi-calendar3 me-1"></i> <?php echo date('d M Y', strtotime($order['created_at'])); ?>
        <br>
        <i class="bi bi-clock me-1"></i> <?php echo date('H:i', strtotime($order['created_at'])); ?>
    </td>
    <td class="fw-bold text-white">฿<?php echo number_format($order['total_amount'], 2); ?></td>
    <td><span class="status-badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></td>
    <td class="text-end pe-4">
        <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" class="btn-action" title="View Details"><i class="bi bi-eye"></i></a>
    </td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td colspan="6" class="text-center py-5">
        <div class="d-flex flex-column align-items-center justify-content-center opacity-50">
            <i class="bi bi-box-seam fs-1 mb-3"></i>
            <p class="m-0">No orders found.</p>
        </div>
    </td>
</tr>
<?php endif; 

$html_content = ob_get_clean(); // เก็บ HTML ใส่ตัวแปร

// ส่ง JSON กลับไปให้หน้าหลัก
echo json_encode([
    'html' => $html_content,
    'total_alerts' => $total_alerts,
    'pending_orders' => $pending_orders_count
]);
exit;
?>
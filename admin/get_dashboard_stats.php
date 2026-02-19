<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { exit; }

// 1. ยอดขายวันนี้
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = :today AND status != 'cancelled'");
$stmt->execute([':today' => $today]);
$today_sales = (float)($stmt->fetch()['total'] ?? 0);

// 2. ยอดขายเดือนนี้
$this_month = date('Y-m');
$stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = :month AND status != 'cancelled'");
$stmt->execute([':month' => $this_month]);
$month_sales = (float)($stmt->fetch()['total'] ?? 0);

// 3. ออเดอร์รอจัดส่ง
$stmt = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'paid', 'waiting', 'รอตรวจสอบ', 'ชำระเงินแล้ว')");
$pending_orders = (int)$stmt->fetch()['count'];

// 4. ข้อมูลกราฟ 7 วัน
$dates = []; $sales_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = :date AND status != 'cancelled'");
    $stmt->execute([':date' => $date]);
    $total = $stmt->fetch()['total'] ?? 0;
    $dates[] = date('d/m', strtotime($date));
    $sales_data[] = (float)$total;
}

// 5. 5 อันดับขายดี
$stmt = $conn->query("SELECT b.title, SUM(oi.quantity) as total_qty FROM order_items oi JOIN books b ON oi.book_id = b.book_id JOIN orders o ON oi.order_id = o.order_id WHERE o.status != 'cancelled' GROUP BY oi.book_id ORDER BY total_qty DESC LIMIT 5");
$top_selling = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. สต็อกต่ำ
$stmt = $conn->query("SELECT title, stock_quantity FROM books WHERE stock_quantity < 5 ORDER BY stock_quantity ASC LIMIT 5");
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'today_sales' => number_format($today_sales, 2),
    'month_sales' => number_format($month_sales, 2),
    'pending_orders' => $pending_orders,
    'chart_labels' => $dates,
    'chart_data' => $sales_data,
    'top_selling' => $top_selling,
    'low_stock' => $low_stock
]);
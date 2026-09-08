<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit; }
require_once '../config/db.php';

// 1. สรุปยอดขาย (Cards)
$today_sales = $conn->query("SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = CURDATE() AND status != 'cancelled'")->fetchColumn() ?: 0;
$month_sales = $conn->query("SELECT SUM(total_amount) FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND status != 'cancelled'")->fetchColumn() ?: 0;
$total_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0;

// 2. ดึงสถิติหนังสือขายดี 5 อันดับแรก
$top_books = $conn->query("SELECT b.title, SUM(oi.quantity) as total_qty, SUM(oi.price * oi.quantity) as revenue
                            FROM order_items oi
                            JOIN books b ON oi.book_id = b.book_id
                            JOIN orders o ON oi.order_id = o.order_id
                            WHERE o.status != 'cancelled'
                            GROUP BY oi.book_id
                            ORDER BY total_qty DESC
                            LIMIT 5")->fetchAll();

// 3. เตรียมข้อมูลสำหรับกราฟ (ยอดขายรายวัน 7 วันย้อนหลัง)
$chart_query = $conn->query("SELECT DATE(order_date) as date, SUM(total_amount) as daily_total 
                             FROM orders 
                             WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status != 'cancelled'
                             GROUP BY DATE(order_date) 
                             ORDER BY date ASC")->fetchAll();
$dates = [];
$totals = [];
foreach($chart_query as $row) {
    $dates[] = date('d/m', strtotime($row['date']));
    $totals[] = $row['daily_total'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานยอดขาย - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap');
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .card { border: none; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4 shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">👾 Admin Console / Report</a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">กลับหน้าหลัก</a>
        </div>
    </nav>

    <div class="container pb-5">
        <h3 class="fw-bold mb-4"><i class="bi bi-bar-chart-line-fill text-primary"></i> สรุปรายงานยอดขาย</h3>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary text-white me-3"><i class="bi bi-currency-dollar"></i></div>
                        <div>
                            <p class="text-muted mb-0 small">ยอดขายวันนี้</p>
                            <h4 class="fw-bold mb-0">฿<?php echo number_format($today_sales, 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success text-white me-3"><i class="bi bi-calendar-check"></i></div>
                        <div>
                            <p class="text-muted mb-0 small">ยอดขายเดือนนี้</p>
                            <h4 class="fw-bold mb-0">฿<?php echo number_format($month_sales, 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning text-white me-3"><i class="bi bi-box-seam"></i></div>
                        <div>
                            <p class="text-muted mb-0 small">ออเดอร์ที่สำเร็จ</p>
                            <h4 class="fw-bold mb-0"><?php echo $total_orders; ?> <small class="fs-6 fw-normal">รายการ</small></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card p-4 h-100">
                    <h6 class="fw-bold mb-4">แนวโน้มยอดขาย 7 วันล่าสุด</h6>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-4 h-100">
                    <h6 class="fw-bold mb-4">🏆 5 อันดับหนังสือขายดี</h6>
                    <?php if(empty($top_books)): ?>
                        <p class="text-center text-muted my-5">ยังไม่มีข้อมูลการขาย</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($top_books as $index => $book): ?>
                            <div class="list-group-item px-0 border-0 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-light text-dark me-2"><?php echo $index+1; ?></span>
                                        <span class="text-truncate fw-500" style="max-width: 150px;"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <span class="badge rounded-pill bg-info text-dark"><?php echo htmlspecialchars($book['total_qty'], ENT_QUOTES, 'UTF-8'); ?> เล่ม</span>
                                </div>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar" style="width: <?php echo ($book['total_qty'] / $top_books[0]['total_qty']) * 100; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: <?php echo json_encode($totals); ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
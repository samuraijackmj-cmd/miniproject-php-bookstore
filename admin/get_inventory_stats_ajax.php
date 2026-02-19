<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$low_stock = $conn->query("SELECT COUNT(*) FROM books WHERE stock_quantity < 5")->fetchColumn();
$total_books = $conn->query("SELECT COUNT(*) FROM books")->fetchColumn();
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'รอตรวจสอบ', '🟡 รอตรวจสอบ')")->fetchColumn();

echo json_encode([
    'low_stock' => (int)$low_stock,
    'total_books' => (int)$total_books,
    'pending_orders' => (int)$pending_orders
]);
<?php
session_start();
require_once 'config/db.php';

if (!isset($_GET['order_id']) || !isset($_SESSION['user_id'])) {
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// ดึงสถานะล่าสุดและเลขพัสดุ
$stmt = $conn->prepare("SELECT status, tracking_number FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($order);
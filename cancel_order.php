<?php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: order_history.php"); exit; }

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $current_status = $stmt->fetchColumn();

    if ($current_status == 'รอตรวจสอบ' || $current_status == 'pending') {
        // คืนสต็อก
        $stmt_items = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = ?");
        $stmt_items->execute([$order_id]);
        foreach ($stmt_items->fetchAll() as $item) {
            $conn->prepare("UPDATE books SET stock_quantity = stock_quantity + ? WHERE book_id = ?")
                 ->execute([$item['quantity'], $item['book_id']]);
        }
        // เปลี่ยนสถานะ
        $conn->prepare("UPDATE orders SET status = 'ยกเลิก' WHERE order_id = ?")->execute([$order_id]);
        $conn->commit();
        header("Location: order_history.php?msg=success");
    } else {
        header("Location: order_history.php?msg=error");
    }
} catch (Exception $e) { $conn->rollBack(); die($e->getMessage()); }
<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบว่ามีการเข้าสู่ระบบหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $user_id = $_SESSION['user_id'];
    $book_id = intval($_GET['id']);

    try {
        // ตรวจสอบว่าสินค้านี้อยู่ในรายการโปรดหรือไม่
        $stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
        $existing = $stmt->fetch();

        header('Content-Type: application/json');

        if ($existing) {
            // ลบออกจาก wishlist
            $stmt_delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
            $stmt_delete->execute([$user_id, $book_id]);
            echo json_encode(['status' => 'success', 'added' => false]);
        } else {
            // เพิ่มเข้า wishlist
            $stmt_insert = $conn->prepare("INSERT INTO wishlist (user_id, book_id, added_date) VALUES (?, ?, NOW())");
            $stmt_insert->execute([$user_id, $book_id]);
            echo json_encode(['status' => 'success', 'added' => true]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

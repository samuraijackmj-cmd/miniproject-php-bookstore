<?php
session_start();

// 1. ตรวจสอบสิทธิ์ Admin (ป้องกันผู้ใช้งานทั่วไปแอบลบ)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
csrf_generate();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // 2. ดึงชื่อไฟล์รูปภาพเดิมมาเพื่อลบออกจาก Server
        $stmt = $conn->prepare("SELECT image FROM books WHERE book_id = ?");
        $stmt->execute([$id]);
        $book = $stmt->fetch();

        if ($book) {
            // ลบรูปภาพออกจากโฟลเดอร์ uploads
            if (!empty($book['image']) && file_exists("../uploads/" . $book['image'])) {
                @unlink("../uploads/" . $book['image']);
            }

            // 3. ลบข้อมูลหนังสือออกจากฐานข้อมูล
            $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
            $stmt->execute([$id]);
            
            // หมายเหตุ: หากมีการเก็บประวัติการลบ (Logs) สามารถเพิ่มโค้ดตรงนี้ได้
        }
    } catch (PDOException $e) {
        // กรณีเกิด Error (เช่น มีข้อมูลผูกกับตารางอื่น) ให้เด้งเตือน
        echo "<script>alert('เกิดข้อผิดพลาดในการลบหนังสือ กรุณาลองใหม่อีกครั้ง'); window.location='books.php';</script>";
        error_log('book_delete error: ' . $e->getMessage());
        exit;
    }
}

// 4. กลับไปยังหน้าจัดการหนังสือ
header("Location: books.php");
exit;
?>
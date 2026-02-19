<?php
// 1. เริ่ม Buffer ทันที และล้าง Buffer เก่าทิ้ง
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ปิด Error หน้าจอเพื่อกัน JSON พัง
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';

// เช็คว่า User Login อยู่ไหม
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// --- ฟังก์ชันช่วยคำนวณยอดรวม (รองรับทั้ง DB และ Session) ---
function calculateCartTotals($conn, $user_id) {
    $grand_total = 0;
    $total_items = 0;

    if ($user_id) {
        // [Member] คำนวณจาก Database
        $sql = "SELECT c.quantity, b.price, b.discount_percent 
                FROM cart c 
                JOIN books b ON c.book_id = b.book_id 
                WHERE c.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $price = floatval($item['price']);
            $discount = floatval($item['discount_percent']);
            $final_price = $price - ($price * $discount / 100);
            
            $grand_total += $final_price * $item['quantity'];
            $total_items += $item['quantity'];
        }
    } else {
        // [Guest] คำนวณจาก Session
        if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
            $ids = array_map('intval', array_keys($_SESSION['cart']));
            if (!empty($ids)) {
                $ids_string = implode(',', $ids);
                $sql = "SELECT book_id, price, discount_percent FROM books WHERE book_id IN ($ids_string)";
                $stmt = $conn->query($sql);
                $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($books as $book) {
                    $bid = $book['book_id'];
                    if (!isset($_SESSION['cart'][$bid])) continue;
                    
                    $qty = $_SESSION['cart'][$bid];
                    $price = floatval($book['price']);
                    $discount = floatval($book['discount_percent']);
                    $final_price = $price - ($price * $discount / 100);

                    $grand_total += $final_price * $qty;
                    $total_items += $qty;
                }
            }
        }
    }
    
    return ['grand_total' => $grand_total, 'total_items' => $total_items];
}

// --- เริ่มการทำงาน ---
$action = isset($_GET['action']) ? $_GET['action'] : '';
$is_ajax = isset($_GET['ajax']); 
$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด', 'cart_count' => 0, 'grand_total' => '0'];

try {
    // เตรียม Session Cart ให้พร้อมเสมอ (สำหรับ Guest)
    if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

    switch ($action) {
        
        // --- 1. เพิ่ม/อัปเดตสินค้า (Add/Update) ---
        case 'add':
        case 'update':
            if (isset($_GET['id'])) {
                $book_id = intval($_GET['id']);
                // ถ้า action=add ให้บวกเพิ่ม, ถ้า update ให้แทนที่ค่าเดิม
                $qty_param = isset($_GET['qty']) ? intval($_GET['qty']) : 1;
                
                // ตรวจสอบ Stock ใน DB
                $stmt = $conn->prepare("SELECT stock_quantity, price, discount_percent FROM books WHERE book_id = ?");
                $stmt->execute([$book_id]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($book) {
                    // คำนวณจำนวนที่ต้องการ
                    if ($action == 'add') {
                        // ต้องเช็คก่อนว่าเดิมมีเท่าไหร่
                        if ($user_id) {
                            $check = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?");
                            $check->execute([$user_id, $book_id]);
                            $curr = $check->fetch(PDO::FETCH_ASSOC);
                            $current_qty = $curr ? $curr['quantity'] : 0;
                        } else {
                            $current_qty = isset($_SESSION['cart'][$book_id]) ? $_SESSION['cart'][$book_id] : 0;
                        }
                        $target_qty = $current_qty + $qty_param;
                    } else {
                        // update คือแทนที่เลย
                        $target_qty = $qty_param;
                    }

                    // ตรวจสอบว่าเกิน Stock ไหม
                    $max_stock = intval($book['stock_quantity']);
                    
                    if ($max_stock <= 0) {
                         $response['status'] = 'limit';
                         $response['message'] = 'สินค้าหมดชั่วคราว';
                         $target_qty = 0; // ไม่ให้เพิ่ม
                    } elseif ($target_qty > $max_stock) {
                        $target_qty = $max_stock;
                        $response['status'] = 'limit';
                        $response['message'] = 'สินค้าเหลือเพียง ' . $max_stock . ' เล่ม';
                        $response['max_qty'] = $max_stock;
                    } else {
                        $response['status'] = 'success';
                        $response['message'] = ($action == 'add') ? 'เพิ่มลงตะกร้าเรียบร้อย' : 'อัปเดตแล้ว';
                    }

                    if ($target_qty > 0) {
                        // --- บันทึกข้อมูล (แยก Member / Guest) ---
                        if ($user_id) {
                            // [MEMBER] : SQL
                            // เช็คว่ามี Record เดิมไหม
                            $check = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND book_id = ?");
                            $check->execute([$user_id, $book_id]);
                            
                            if ($check->rowCount() > 0) {
                                $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
                                $upd->execute([$target_qty, $user_id, $book_id]);
                            } else {
                                $ins = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
                                $ins->execute([$user_id, $book_id, $target_qty]);
                            }
                        } else {
                            // [GUEST] : Session
                            $_SESSION['cart'][$book_id] = $target_qty;
                        }

                        // คำนวณ Subtotal ของรายการนี้ส่งกลับไป (สำหรับหน้า Cart)
                        $price = floatval($book['price']);
                        $discount = floatval($book['discount_percent']);
                        $final_price = $price - ($price * $discount / 100);
                        $item_subtotal = $final_price * $target_qty;
                        $response['item_subtotal'] = number_format($item_subtotal);

                    } elseif ($target_qty <= 0 && $action == 'update') {
                        // กรณีปรับลดจนเหลือ 0 ให้ลบออก
                        if ($user_id) {
                            $del = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
                            $del->execute([$user_id, $book_id]);
                        } else {
                            unset($_SESSION['cart'][$book_id]);
                        }
                        $response['status'] = 'removed';
                        $response['message'] = 'ลบสินค้าแล้ว';
                    }
                    
                } else {
                    $response['message'] = 'ไม่พบสินค้านี้';
                }
            }
            break;

        // --- 2. ลบสินค้า (Delete) ---
        case 'delete':
            if (isset($_GET['id'])) {
                $book_id = intval($_GET['id']);
                
                if ($user_id) {
                    $del = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
                    $del->execute([$user_id, $book_id]);
                } else {
                    unset($_SESSION['cart'][$book_id]);
                }
                
                $response['status'] = 'removed';
                $response['message'] = 'ลบสินค้าเรียบร้อย';
            }
            break;

        // --- 3. ล้างตะกร้า (Clear) ---
        case 'clear':
            if ($user_id) {
                $del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $del->execute([$user_id]);
            } else {
                $_SESSION['cart'] = [];
            }
            
            $response['status'] = 'success';
            $response['message'] = 'ล้างตะกร้าเรียบร้อย';
            break;
            
        default:
            if (!$is_ajax) { header("Location: index.php"); exit; }
            break;
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Server Error: ' . $e->getMessage();
}

// --- ประมวลผลรอบสุดท้าย (คำนวณยอดเงินรวมใหม่) ---
$totals = calculateCartTotals($conn, $user_id);
$response['cart_count'] = $totals['total_items'];
$response['grand_total'] = number_format($totals['grand_total']);

// --- ส่ง Response ---
if ($is_ajax) {
    ob_end_clean(); 
    echo json_encode($response);
    exit;
} else {
    // Fallback (กรณีไม่ได้ใช้ JS)
    $redirect_url = ($_SERVER['HTTP_REFERER'] ?? 'index.php');
    if (strpos($redirect_url, 'cart.php') === false && ($action == 'delete' || $action == 'clear')) { 
        $redirect_url = 'cart.php'; 
    }
    header("Location: " . $redirect_url);
    exit;
}
?>
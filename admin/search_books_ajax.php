<?php
require_once '../config/db.php';

// กำหนดเกณฑ์แจ้งเตือนสินค้าใกล้หมด
$low_stock_threshold = 5;

// 1. รับค่าจาก AJAX
$search = isset($_GET['search']) ? $_GET['search'] : '';
$cat = isset($_GET['cat']) ? $_GET['cat'] : '';

// 2. เตรียม SQL Query
$sql = "SELECT books.*, categories.category_name 
        FROM books 
        LEFT JOIN categories ON books.category_id = categories.category_id 
        WHERE (books.title LIKE :search OR books.isbn LIKE :search)";

if (!empty($cat)) {
    $sql .= " AND books.category_id = :cat";
}

// เรียงลำดับ
$sql .= " ORDER BY books.stock_quantity ASC, books.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%');
if (!empty($cat)) {
    $stmt->bindValue(':cat', $cat);
}
$stmt->execute();
$books = $stmt->fetchAll();

// 3. แสดงผลข้อมูล
if (count($books) > 0):
    foreach ($books as $book): 
        $stock = (int)$book['stock_quantity'];
        
        // Logic สีและสถานะ
        if ($stock <= 0) {
            $stock_color = 'color: #ff4d4d;';
            $badge_html = '<span class="badge bg-danger ms-2" style="font-size: 0.6rem;">หมด</span>';
            $img_style = 'filter: grayscale(100%); opacity: 0.5;';
        } elseif ($stock < $low_stock_threshold) {
            $stock_color = 'color: #ffca28;';
            $badge_html = '<span class="badge bg-warning text-dark ms-2" style="font-size: 0.6rem;">ใกล้หมด</span>';
            $img_style = '';
        } else {
            $stock_color = 'color: #34d399;';
            $badge_html = '';
            $img_style = '';
        }
        ?>
        <tr id="row-<?= $book['book_id'] ?>">
            <td class="ps-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted font-monospace small" style="min-width: 110px; display: inline-block;">
                        <?= !empty($book['isbn']) ? htmlspecialchars($book['isbn']) : '-' ?>
                    </span>
                    
                    <img src="../uploads/<?= !empty($book['image']) ? $book['image'] : 'no-image.jpg' ?>" 
                         id="img-<?= $book['book_id'] ?>"
                         class="rounded shadow-sm" 
                         style="width:45px; height:60px; object-fit:cover; transition: 0.3s; <?= $img_style ?>">
                </div>
            </td>

            <td>
                <div class="fw-bold text-white mb-1">
                    <?= htmlspecialchars($book['title']) ?>
                    <span id="badge-<?= $book['book_id'] ?>"><?= $badge_html ?></span>
                </div>
                <div class="badge rounded-pill fw-normal" 
                     style="background: rgba(92, 103, 242, 0.1); color: #7c83ff; border: 1px solid rgba(92, 103, 242, 0.2);">
                    <?= htmlspecialchars($book['category_name'] ?? 'General') ?>
                </div>
            </td>

            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-2" 
                     style="background: rgba(255,255,255,0.03); padding: 4px 8px; border-radius: 50px; width: fit-content; margin: 0 auto; border: 1px solid rgba(255,255,255,0.05);">
                    
                    <button type="button" onclick="updateStock(<?= $book['book_id'] ?>, 'decrease')" 
                            class="btn btn-sm text-white p-0 d-flex align-items-center justify-content-center hover-scale" 
                            style="width: 24px; height: 24px; border-radius: 50%;">
                        <i class="bi bi-dash"></i>
                    </button>
                    
                    <span id="stock-<?= $book['book_id'] ?>" class="fw-bold mx-2" style="font-size: 1.1rem; min-width: 25px; <?= $stock_color ?>">
                        <?= $stock ?>
                    </span>
                    
                    <button type="button" onclick="updateStock(<?= $book['book_id'] ?>, 'increase')" 
                            class="btn btn-sm text-white p-0 d-flex align-items-center justify-content-center hover-scale" 
                            style="width: 24px; height: 24px; border-radius: 50%;">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </td>

            <td class="text-end font-monospace text-white fs-6">
                ฿<?= number_format($book['price']) ?>
            </td>

            <td class="text-end pe-4">
                <div class="action-btns">
                    <a href="book_edit.php?id=<?= $book['book_id'] ?>" class="btn-action btn-edit" title="แก้ไข">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <a href="book_delete.php?id=<?= $book['book_id'] ?>" 
                       class="btn-action btn-delete" 
                       title="ลบ" 
                       onclick="return confirm('ยืนยันการลบหนังสือ: <?= htmlspecialchars($book['title']) ?> ?');">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </td>
        </tr>
    <?php endforeach; 
else: ?>
    <tr>
        <td colspan="5" class="text-center py-5">
            <div class="d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                <i class="bi bi-search fs-1 mb-2"></i>
                <p class="m-0">ไม่พบข้อมูลหนังสือที่ค้นหา</p>
            </div>
        </td>
    </tr>
<?php endif; ?>
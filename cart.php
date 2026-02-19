<?php
session_start();
require_once 'config/db.php';

// --- ตัวแปรสำหรับแสดงผล (คงเดิม) ---
$cart_items = [];
$grand_total = 0;
$total_discount = 0;
$total_items = 0;
$has_out_of_stock = false;

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if ($user_id) {
    try {
        $sql = "SELECT b.*, c.quantity as cart_qty FROM cart c JOIN books b ON c.book_id = b.book_id WHERE c.user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $product) {
            $qty = $product['cart_qty'];
            calculateItem($product, $qty, $cart_items, $grand_total, $total_discount, $total_items, $has_out_of_stock);
        }
    } catch (PDOException $e) { }
} else {
    if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
        $ids = array_map('intval', array_keys($_SESSION['cart']));
        if (!empty($ids)) {
            $ids_string = implode(',', $ids);
            $sql = "SELECT * FROM books WHERE book_id IN ($ids_string)";
            $stmt = $conn->query($sql);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $product) {
                $book_id = $product['book_id'];
                if (!isset($_SESSION['cart'][$book_id])) continue;
                $qty = $_SESSION['cart'][$book_id];
                calculateItem($product, $qty, $cart_items, $grand_total, $total_discount, $total_items, $has_out_of_stock);
            }
        }
    }
}

function calculateItem($product, $qty, &$cart_items, &$grand_total, &$total_discount, &$total_items, &$has_out_of_stock) {
    $is_out_of_stock = ($product['stock_quantity'] <= 0);
    $is_not_enough = ($qty > $product['stock_quantity']);
    
    if ($is_out_of_stock || $is_not_enough) { 
        $has_out_of_stock = true; 
    } 
    
    // คำนวณราคาเริ่มต้น (เดี๋ยว JS จะมาอัปเดตต่อ)
    $calc_qty = ($qty > $product['stock_quantity'] && $product['stock_quantity'] > 0) ? $product['stock_quantity'] : $qty;
    if($is_out_of_stock) $calc_qty = 0;

    $original_price = floatval($product['price']);
    $final_price = $original_price - ($original_price * floatval($product['discount_percent']) / 100);
    $subtotal = $final_price * $calc_qty;

    if (!$is_out_of_stock && !$is_not_enough) {
        $grand_total += $subtotal;
        $total_discount += ($original_price * $calc_qty) - $subtotal;
        $total_items += $calc_qty;
    }
    
    $product['qty'] = $qty;
    $product['final_price'] = $final_price;
    $product['subtotal'] = $subtotal;
    $product['is_out_of_stock'] = $is_out_of_stock;
    $product['is_not_enough'] = $is_not_enough;
    $cart_items[] = $product;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า | BookStore Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        /* --- 🌌 Styles คงเดิม --- */
        :root { --primary: #6366f1; --primary-light: #818cf8; --accent: #a855f7; --bg-dark: #0f172a; --text-white: #ffffff; --text-gray: #cbd5e1; --border-glass: rgba(255, 255, 255, 0.1); }
        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-dark); color: var(--text-white); overflow-x: hidden; min-height: 100vh; position: relative; }
        .ambient-light { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%), radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%); pointer-events: none; }
        #starfield { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }
        .glass-card { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(20px); border: 1px solid var(--border-glass); border-radius: 24px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); overflow: hidden; }
        .page-title { font-size: 2.5rem; font-weight: 800; color: #fff; text-shadow: 0 0 20px rgba(99, 102, 241, 0.5); margin-bottom: 0.5rem; }
        .table { --bs-table-bg: transparent; color: var(--text-white); margin-bottom: 0; vertical-align: middle; }
        .table thead th { border-bottom: 1px solid rgba(255,255,255,0.2); color: var(--text-gray); font-weight: 500; font-size: 1rem; padding: 1.2rem; }
        .table tbody td { border-bottom: 1px solid var(--border-glass); padding: 1.5rem 1rem; }
        .cart-img-wrapper { width: 70px; height: 100px; position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); }
        .cart-product-img { width: 100%; height: 100%; object-fit: cover; }
        .cart-img-wrapper.sold-out .cart-product-img { filter: grayscale(100%); opacity: 0.5; }
        .cart-img-wrapper.sold-out::after { content: "หมด"; position: absolute; inset: 0; background: rgba(0,0,0,0.7); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 800; text-transform: uppercase; border: 2px solid #ef4444; }
        .product-title { font-weight: 600; font-size: 1.1rem; color: #fff; text-decoration: none; display: block; margin-bottom: 5px; transition: 0.3s; }
        .product-title:hover { color: var(--primary-light); }
        .price-current { font-size: 1.15rem; font-weight: 700; color: #fff; }
        .price-original { font-size: 0.9rem; text-decoration: line-through; color: var(--text-gray); margin-left: 8px; }
        .qty-group { display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--border-glass); border-radius: 50px; padding: 5px; width: fit-content; margin: 0 auto; }
        .btn-qty { width: 32px; height: 32px; border-radius: 50%; border: none; background: rgba(255,255,255,0.1); color: #fff; display: flex; align-items: center; justify-content: center; transition: 0.2s; cursor: pointer; }
        .btn-qty:hover:not(:disabled) { background: var(--primary); }
        .qty-display { width: 45px; text-align: center; font-weight: 700; font-size: 1.1rem; color: #fff; }
        .btn-delete { color: #ef4444; font-size: 1.3rem; border: none; background: none; opacity: 0.7; transition: 0.2s; }
        .btn-delete:hover { color: #ff6b6b; opacity: 1; }
        .summary-card { position: sticky; top: 100px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; color: var(--text-gray); font-size: 1rem; }
        .summary-total { display: flex; justify-content: space-between; margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.15); font-size: 1.5rem; font-weight: 800; color: #fff; }
        .btn-checkout { background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border: none; width: 100%; padding: 18px; border-radius: 16px; font-weight: 700; font-size: 1.2rem; box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4); text-decoration: none; display: block; text-align: center; margin-top: 20px; transition: 0.3s; }
        .btn-checkout:hover:not(.disabled) { transform: translateY(-3px); }
        .btn-checkout.disabled { background: #334155 !important; cursor: not-allowed; box-shadow: none; opacity: 0.5; pointer-events: none; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>

    <div class="container py-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <h1 class="page-title m-0"><i class="bi bi-cart-fill text-primary"></i> ตะกร้าสินค้า</h1>
            <span class="badge bg-white bg-opacity-10 text-white rounded-pill border border-white border-opacity-25 px-3">
                <?php echo $total_items; ?> รายการ
            </span>
        </div>

        <div id="out-of-stock-alert" class="alert alert-danger bg-danger bg-opacity-25 text-white border border-danger border-opacity-50 rounded-4 d-flex align-items-center gap-3 mb-4 <?php echo $has_out_of_stock ? '' : 'd-none'; ?> animate__animated animate__shakeX">
            <i class="bi bi-exclamation-triangle-fill fs-3"></i>
            <div><strong>มีสินค้าหมดหรือจำนวนไม่พอ!</strong> <br> <small class="opacity-75">กรุณาลบรายการหรือลดจำนวนก่อนชำระเงิน</small></div>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="glass-card text-center py-5">
                <i class="bi bi-cart-x display-1 text-secondary opacity-25"></i>
                <h3 class="mt-3 fw-bold">ตะกร้าของคุณว่างเปล่า</h3>
                <a href="index.php" class="btn btn-outline-light rounded-pill px-5 py-2 fw-bold mt-3"><i class="bi bi-arrow-left me-2"></i> เลือกซื้อหนังสือ</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="glass-card">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr><th class="ps-4">สินค้า</th><th class="text-center">ราคา</th><th class="text-center">จำนวน</th><th class="text-end pe-4">รวม</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr id="row-<?php echo $item['book_id']; ?>" class="cart-item-row" data-id="<?php echo $item['book_id']; ?>" style="<?php echo ($item['is_out_of_stock'] || $item['is_not_enough']) ? 'opacity: 0.5;' : ''; ?>">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="cart-img-wrapper <?php echo $item['is_out_of_stock'] ? 'sold-out' : ''; ?>">
                                                    <img src="uploads/<?php echo $item['image']; ?>" class="cart-product-img" onerror="this.src='assets/no-image.png'">
                                                </div>
                                                <div>
                                                    <a href="product_detail.php?id=<?php echo $item['book_id']; ?>" class="product-title"><?php echo htmlspecialchars($item['title']); ?></a>
                                                    <div class="stock-status-badge">
                                                        <?php if($item['is_out_of_stock']): ?>
                                                            <span class="badge bg-danger mt-1">สินค้าหมด</span>
                                                        <?php elseif($item['is_not_enough']): ?>
                                                            <span class="badge bg-warning text-dark mt-1">สินค้าไม่พอ</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if(!$item['is_out_of_stock']): ?>
                                                <div class="price-current" id="price-final-<?php echo $item['book_id']; ?>">฿<?php echo number_format($item['final_price']); ?></div>
                                                <div class="price-original" id="price-original-<?php echo $item['book_id']; ?>" style="<?php echo ($item['discount_percent'] > 0) ? '' : 'display:none;'; ?>">฿<?php echo number_format($item['price']); ?></div>
                                            <?php else: ?><span class="text-gray">-</span><?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="qty-group">
                                                <button class="btn-qty" onclick="updateQtyAjax(<?php echo $item['book_id']; ?>, -1, this)" <?php echo $item['is_out_of_stock'] ? 'disabled' : ''; ?>><i class="bi bi-dash"></i></button>
                                                <div class="qty-display" id="qty-display-<?php echo $item['book_id']; ?>"><?php echo $item['qty']; ?></div>
                                                <button class="btn-qty" onclick="updateQtyAjax(<?php echo $item['book_id']; ?>, 1, this)" <?php echo $item['is_out_of_stock'] ? 'disabled' : ''; ?>><i class="bi bi-plus"></i></button>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4 fw-bold">
                                            <?php if(!$item['is_out_of_stock']): ?>
                                                <span class="text-white" id="subtotal-<?php echo $item['book_id']; ?>">฿<?php echo number_format($item['subtotal']); ?></span>
                                            <?php else: ?><span class="text-gray">-</span><?php endif; ?>
                                        </td>
                                        <td class="text-center"><button onclick="deleteItemAjax(<?php echo $item['book_id']; ?>)" class="btn-delete"><i class="bi bi-trash3-fill"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top border-white border-opacity-10 text-end">
                            <a href="#" onclick="clearCartAjax(event)" class="text-danger text-decoration-none small fw-bold opacity-75 hover-opacity-100"><i class="bi bi-trash me-1"></i> ล้างตะกร้าทั้งหมด</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="glass-card p-4 summary-card">
                        <h4 class="fw-bold mb-4 text-white"><i class="bi bi-receipt me-2 text-primary"></i> สรุปรายการ</h4>
                        <div class="summary-total"><span>ยอดสุทธิ</span><span class="grand-total-display" id="grand-total-display">฿<?php echo number_format($grand_total); ?></span></div>
                        <div class="mt-2">
                            <a href="<?php echo $has_out_of_stock ? 'javascript:void(0)' : 'checkout.php'; ?>" 
                               id="checkout-btn" 
                               class="btn-checkout <?php echo $has_out_of_stock ? 'disabled' : ''; ?>" 
                               <?php echo $has_out_of_stock ? 'onclick="return false;" style="pointer-events: none;"' : 'onclick="handleCheckout(event)"'; ?>>
                                <?php echo $has_out_of_stock ? 'มีสินค้าหมดในตะกร้า' : 'ดำเนินการชำระเงิน <i class="bi bi-arrow-right-circle-fill ms-2"></i>'; ?>
                            </a>
                        </div>
                        <div class="text-center mt-3"><a href="index.php" class="text-gray text-decoration-none small hover-white"><i class="bi bi-chevron-left"></i> เลือกซื้อสินค้าต่อ</a></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ฟังก์ชันจัดรูปแบบตัวเลข (ใส่คอมม่า)
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // --- 🌌 Real-time Stock & Price Polling (ฉบับอัปเกรด) ---
        function checkCartStockRealtime() {
            const rows = $('.cart-item-row'); 
            if (rows.length === 0) return;
            
            const ids = []; 
            rows.each(function() { ids.push($(this).data('id')); });

            // ดึงข้อมูล Real-time
            fetch(`get_stock_status.php?ids=${ids.join(',')}&t=${Date.now()}`)
                .then(res => res.json())
                .then(data => {
                    const stockList = data.stocks ? data.stocks : (Array.isArray(data) ? data : []);
                    let anyOut = false;
                    let newGrandTotal = 0; // ตัวแปรคำนวณยอดรวมใหม่
                    
                    stockList.forEach(item => {
                        const bookId = item.book_id;
                        const $row = $(`#row-${bookId}`);
                        const $img = $row.find('.cart-img-wrapper');
                        const $badge = $row.find('.stock-status-badge');
                        const $btnQ = $row.find('.btn-qty');

                        // 1. อัปเดตราคา (Price Logic)
                        const currentQty = parseInt($(`#qty-display-${bookId}`).text()) || 0;
                        let finalPrice = 0;
                        
                        if (item.price !== undefined) {
                            const price = parseFloat(item.price);
                            const discount = parseFloat(item.discount_percent || 0);
                            finalPrice = price - (price * discount / 100);

                            // อัปเดตตัวเลขราคาบนหน้าเว็บ
                            $(`#price-final-${bookId}`).text('฿' + numberWithCommas(finalPrice));
                            
                            const $origPrice = $(`#price-original-${bookId}`);
                            if (discount > 0) {
                                $origPrice.text('฿' + numberWithCommas(price)).show();
                            } else {
                                $origPrice.hide();
                            }

                            // คำนวณ Subtotal ต่อชิ้น
                            const subtotal = finalPrice * currentQty;
                            $(`#subtotal-${bookId}`).text('฿' + numberWithCommas(subtotal));
                        }

                        // 2. เช็คสต็อก (Stock Logic)
                        const isSoldOut = item.stock_quantity <= 0;
                        const isNotEnough = currentQty > item.stock_quantity;

                        if (isSoldOut || isNotEnough) {
                            anyOut = true; // เจอของหมด/ไม่พอ
                            
                            if (!$img.hasClass('sold-out')) {
                                $img.addClass('sold-out');
                                $row.css('opacity', '0.5'); 
                                $btnQ.prop('disabled', true);
                                if(isSoldOut) $badge.html('<span class="badge bg-danger mt-1 animate__animated animate__fadeIn">สินค้าหมด</span>');
                                else $badge.html(`<span class="badge bg-warning text-dark mt-1 animate__animated animate__fadeIn">เหลือ ${item.stock_quantity} เล่ม</span>`);
                            }
                        } else {
                            // ถ้าของมีพอ ให้บวกยอดเงินเข้า Grand Total
                            newGrandTotal += (finalPrice * currentQty);

                            if ($img.hasClass('sold-out')) {
                                $img.removeClass('sold-out'); 
                                $badge.empty();
                                $row.css('opacity', '1'); 
                                $btnQ.prop('disabled', false);
                            }
                        }
                    });

                    // 3. อัปเดตยอดสุทธิ (Grand Total)
                    $('#grand-total-display').text('฿' + numberWithCommas(newGrandTotal));

                    // 4. ล็อคปุ่มชำระเงิน
                    const $alert = $('#out-of-stock-alert');
                    const $btn = $('#checkout-btn');
                    
                    if (anyOut) {
                        $alert.removeClass('d-none');
                        if (!$btn.hasClass('disabled')) {
                            $btn.addClass('disabled')
                                .text('สินค้าไม่เพียงพอ')
                                .attr('href', 'javascript:void(0)')
                                .css('pointer-events', 'none')
                                .off('click');
                        }
                    } else {
                        $alert.addClass('d-none');
                        if ($btn.hasClass('disabled')) {
                            $btn.removeClass('disabled')
                                .html('ดำเนินการชำระเงิน <i class="bi bi-arrow-right-circle-fill ms-2"></i>')
                                .attr('href', 'checkout.php')
                                .css('pointer-events', 'auto')
                                .attr('onclick', 'handleCheckout(event)');
                        }
                    }
                })
                .catch(err => console.error("Realtime Error:", err));
        }
        
        setInterval(checkCartStockRealtime, 2000); // เช็คทุก 2 วินาที

        async function updateQtyAjax(id, change, btn) {
            btn.disabled = true;
            const qDisp = document.getElementById(`qty-display-${id}`);
            let newQty = parseInt(qDisp.innerText) + change;
            if (newQty < 1) { btn.disabled = false; return; }
            try {
                const res = await fetch(`cart_action.php?action=update&id=${id}&qty=${newQty}&ajax=1`);
                const data = await res.json();
                if (data.status === 'success') location.reload();
                else if (data.status === 'limit') Swal.fire({ icon: 'warning', title: 'สินค้ามีจำกัด', text: data.message, background: '#1e293b', color: '#fff' });
            } catch (err) { console.error(err); }
            btn.disabled = false;
        }

        function deleteItemAjax(id) {
            Swal.fire({ title: 'ลบสินค้านี้?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'ลบเลย', background: '#1e293b', color: '#fff' }).then(async (result) => { 
                if (result.isConfirmed) { 
                    const res = await fetch(`cart_action.php?action=delete&id=${id}&ajax=1`);
                    const data = await res.json();
                    if (data.status === 'removed' || data.status === 'success') location.reload();
                } 
            });
        }

        function clearCartAjax(e) {
            e.preventDefault();
            Swal.fire({ title: 'ล้างตะกร้า?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', background: '#1e293b', color: '#fff' }).then(async (result) => { 
                if (result.isConfirmed) { 
                    const res = await fetch('cart_action.php?action=clear&ajax=1');
                    const data = await res.json();
                    if (data.status === 'success') location.reload();
                } 
            });
        }

        function handleCheckout(e) {
            if ($('#checkout-btn').hasClass('disabled')) { e.preventDefault(); return false; }
            e.preventDefault(); 
            confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } });
            setTimeout(() => { window.location.href = 'checkout.php'; }, 1000);
        }

        // Starfield
        const canvas = document.getElementById('starfield'), ctx = canvas.getContext('2d');
        let stars = [], width, height;
        function resize() { width = canvas.width = window.innerWidth; height = canvas.height = window.innerHeight; }
        class Star { constructor() { this.x = Math.random()*width; this.y = Math.random()*height; this.size = Math.random()*2; this.speedY = Math.random()*0.5+0.1; this.opacity = Math.random(); this.fadeDir = Math.random()>0.5?0.01:-0.01; } update() { this.y-=this.speedY; if(this.y<0)this.y=height; this.opacity+=this.fadeDir; if(this.opacity>1||this.opacity<0.2)this.fadeDir=-this.fadeDir; } draw() { ctx.fillStyle=`rgba(255,255,255,${this.opacity*0.5})`; ctx.beginPath(); ctx.arc(this.x,this.y,this.size,0,Math.PI*2); ctx.fill(); } }
        function initStars() { stars=[]; for(let i=0;i<60;i++) stars.push(new Star()); }
        function animateStars() { ctx.clearRect(0,0,width,height); stars.forEach(s=>{s.update();s.draw();}); requestAnimationFrame(animateStars); }
        window.addEventListener('resize',()=>{resize();initStars();}); resize(); initStars(); animateStars();
    </script>
</body>
</html>
<?php
session_start();
require_once 'config/db.php';

// --- 1. Maintenance Mode Check ---
try {
    $check_m = $conn->query("SELECT maintenance_mode FROM settings WHERE id = 1")->fetch();
    if (($check_m['maintenance_mode'] ?? 0) == 1 && ($_SESSION['role'] ?? '') != 'admin') {
        header("Location: maintenance.php"); exit;
    }
} catch (Exception $e) {}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$recommended_books = $conn->query("SELECT * FROM books WHERE stock_quantity > 0 ORDER BY book_id DESC LIMIT 10")->fetchAll();

// --- 2. AJAX Load Books ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 'load_books') {
    header('Content-Type: application/json');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 12; 
    $offset = ($page - 1) * $limit;
    
    $where = "WHERE 1=1"; $params = [];
    if (!empty($_GET['cat'])) { $where .= " AND category_id = :cat_id"; $params[':cat_id'] = $_GET['cat']; }
    if (!empty($_GET['search'])) { $where .= " AND (title LIKE :s OR author LIKE :s)"; $params[':s'] = "%".$_GET['search']."%"; }
    
    $sort = $_GET['sort'] ?? 'newest';
    switch ($sort) {
        case 'price_low': $order = "ORDER BY (price - (price * discount_percent / 100)) ASC"; break;
        case 'price_high': $order = "ORDER BY (price - (price * discount_percent / 100)) DESC"; break;
        case 'discount': $order = "ORDER BY discount_percent DESC"; break;
        default: $order = "ORDER BY book_id DESC"; break;
    }

    $books = $conn->prepare("SELECT * FROM books $where $order LIMIT $limit OFFSET $offset");
    $books->execute($params);
    $total_query = $conn->prepare("SELECT COUNT(*) FROM books $where");
    $total_query->execute($params);
    $total_val = (int)$total_query->fetchColumn();

    echo json_encode([
        'books' => $books->fetchAll(PDO::FETCH_ASSOC), 
        'has_more' => ($offset + $limit) < $total_val, 
        'total' => $total_val 
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookStore | Premium Midnight</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root { --bg-dark: #0f172a; --card-bg: rgba(30, 41, 59, 0.7); --primary: #6366f1; --accent: #a855f7; --text-main: #f8fafc; --text-sub: #94a3b8; --glass-border: 1px solid rgba(255, 255, 255, 0.08); }
        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-dark); color: #fff; overflow-x: hidden; min-height: 100vh; position: relative; }
        #starfield { position: fixed; inset: 0; z-index: -1; pointer-events: none; }
        .ambient-light { position: fixed; inset: 0; z-index: -2; background: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%); pointer-events: none; }
        
        #toast-box { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; pointer-events: none; display: flex; flex-direction: column; align-items: center; }
        .premium-toast { background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 32px; padding: 35px; width: 350px; text-align: center; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6); pointer-events: auto; }
        .toast-icon-wrapper { width: 70px; height: 70px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 22px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2.5rem; margin: 0 auto 15px; }
        .toast-progress-bar { position: absolute; bottom: 0; left: 0; height: 4px; background: var(--primary); width: 100%; animation: toastProgress 2.5s linear forwards; }
        @keyframes toastProgress { from { width: 100%; } to { width: 0%; } }

        .price-wrapper { display: flex; flex-direction: column; line-height: 1.2; min-height: 65px; justify-content: flex-end; }
        .price-original { font-size: 0.75rem; color: rgba(148, 163, 184, 0.6); text-decoration: line-through; margin-bottom: 1px; }
        .price-final { font-size: 1.3rem; font-weight: 700; color: #fff; }
        .save-amount { font-size: 0.65rem; color: #4ade80; font-weight: 500; margin-top: 2px; }

        .carousel-wrapper { position: relative; padding: 0 10px; }
        .nav-btn { position: absolute; top: 50%; transform: translateY(-50%); width: 45px; height: 45px; border-radius: 50%; background: var(--primary); color: white; border: none; display: flex; align-items: center; justify-content: center; z-index: 99; cursor: pointer; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5); transition: 0.3s; }
        .nav-btn:hover { background: #4f46e5; scale: 1.1; }
        .nav-btn.prev { left: -15px; } .nav-btn.next { right: -15px; }

        .book-card { background: var(--card-bg); border: var(--glass-border); border-radius: 20px; padding: 12px; height: 100%; transition: 0.4s; cursor: pointer; display: flex; flex-direction: column; overflow: hidden; position: relative; }
        .book-card:hover { transform: translateY(-10px); border-color: var(--primary); }
        .img-container { position: relative; border-radius: 16px; overflow: hidden; aspect-ratio: 2/3; margin-bottom: 15px; }
        .img-container img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
        .book-title { font-size: 0.95rem; font-weight: 600; color: #fff; height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 12px; }

        .sold-out-mask { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.85); display: flex; justify-content: center; align-items: center; z-index: 3; backdrop-filter: blur(2px); }
        .sold-out-label { background: #ef4444; color: white; padding: 8px 20px; border-radius: 50px; font-weight: 800; border: 2px solid white; transform: rotate(-10deg); box-shadow: 0 10px 30px rgba(0,0,0,0.6); letter-spacing: 1px; }
        .book-card.is-sold-out .img-container img { filter: grayscale(100%); opacity: 0.4; } 
        .book-card.is-sold-out { border-color: rgba(239, 68, 68, 0.5) !important; }

        .btn-cart-circle { width: 42px; height: 42px; border-radius: 50%; border: none; background: var(--primary); color: #fff; transition: 0.3s; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3); flex-shrink: 0; }
        .btn-cart-circle:hover:not(.disabled) { background: #4f46e5; transform: scale(1.1); }
        .btn-cart-circle.disabled { width: auto; padding: 0 15px; border-radius: 50px; background: #334155 !important; color: #94a3b8; cursor: not-allowed; box-shadow: none; font-size: 0.75rem; font-weight: bold; pointer-events: none; }

        .scroll-container::-webkit-scrollbar { display: none; }
        .scroll-container { display: flex; gap: 20px; overflow-x: auto; scroll-behavior: smooth; padding-bottom: 15px; }
        .sidebar-glass { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(20px); border: var(--glass-border); border-radius: 24px; padding: 1.5rem; position: sticky; top: 100px; }
        .cat-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-sub); text-decoration: none; border-radius: 12px; transition: 0.3s; margin-bottom: 5px; }
        .cat-link.active { background: rgba(99, 102, 241, 0.15); color: #fff; border-left: 3px solid var(--primary); }
        
        /* 🔥 คลาสสำคัญสำหรับ Real-time Discount */
        .discount-badge-realtime { position: absolute; top: 10px; right: 10px; background: #ef4444; color: white; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; z-index: 2; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>
    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>
    <div id="toast-box"></div>

    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3 d-none d-lg-block">
                <div class="sidebar-glass animate__animated animate__fadeInLeft">
                    <h5 class="mb-4 fw-bold text-white"><i class="bi bi-grid-fill text-primary me-2"></i>หมวดหมู่</h5>
                    <nav class="nav flex-column">
                        <a href="#" class="cat-link active" data-cat=""><i class="bi bi-collection me-2"></i> ทั้งหมด</a>
                        <?php foreach($categories as $cat): ?>
                        <a href="#" class="cat-link" data-cat="<?php echo $cat['category_id']; ?>"><i class="bi bi-dot me-1"></i> <?php echo htmlspecialchars($cat['category_name']); ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </div>

            <div class="col-lg-9">
                <?php if(count($recommended_books) > 0): ?>
                <div id="recommended-section" class="mb-5 animate__animated animate__fadeInDown">
                    <div style="background: rgba(255,255,255,0.02); padding: 2rem; border-radius: 30px; position: relative; border: 1px solid rgba(255,255,255,0.05);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0 fw-bold text-white"><i class="bi bi-stars text-warning"></i> สินค้ามาใหม่แนะนำ</h4>
                            <div id="live-indicator" style="font-size: 0.7rem; color: #38bdf8; display: flex; align-items: center; gap: 5px; opacity: 0.6;"><div class="pulse-dot" style="width: 6px; height: 6px; background: #38bdf8; border-radius: 50%;"></div> Live Stock</div>
                        </div>
                        <div class="carousel-wrapper">
                            <button class="nav-btn prev" onclick="scrollRec(-300)"><i class="bi bi-chevron-left"></i></button>
                            <div class="scroll-container" id="recContainer">
                                <?php foreach ($recommended_books as $book): 
                                    $finalPrice = $book['price'] - ($book['price'] * $book['discount_percent'] / 100); 
                                    $saveAmount = $book['price'] - $finalPrice; 
                                    $isSoldOut = $book['stock_quantity'] <= 0;
                                ?>
                                <div style="min-width: 215px; max-width: 215px;">
                                    <div class="book-card <?php echo $isSoldOut ? 'is-sold-out' : ''; ?>" data-book-id="<?php echo $book['book_id']; ?>" onclick="location.href='product_detail.php?id=<?php echo $book['book_id']; ?>'">
                                        <div class="img-container">
                                            <div class="discount-badge-wrapper">
                                                <?php if($book['discount_percent'] > 0): ?>
                                                    <div class="discount-badge-realtime">-<?php echo floor($book['discount_percent']); ?>%</div>
                                                <?php endif; ?>
                                            </div>
                                            <img src="uploads/<?php echo $book['image']; ?>" onerror="this.src='assets/no-image.png'" class="w-100 h-100 object-fit-cover">
                                            <?php if($isSoldOut): ?><div class="sold-out-mask"><div class="sold-out-label">SOLD OUT</div></div><?php endif; ?>
                                        </div>
                                        <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                        <div class="d-flex justify-content-between align-items-end mt-auto pt-2 border-top border-secondary border-opacity-10">
                                            <div class="price-wrapper">
                                                <?php if($book['discount_percent'] > 0): ?>
                                                    <div class="price-original">฿<?php echo number_format($book['price']); ?></div>
                                                    <div class="price-final">฿<?php echo number_format($finalPrice); ?></div>
                                                    <div class="save-amount small">ประหยัด ฿<?php echo number_format($saveAmount); ?></div>
                                                <?php else: ?>
                                                    <div class="price-final">฿<?php echo number_format($book['price']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if($isSoldOut): ?>
                                                <button class="btn-cart-circle disabled" onclick="event.stopPropagation();">หมด</button>
                                            <?php else: ?>
                                                <button class="btn-cart-circle" onclick="event.stopPropagation(); addToCart(this, <?php echo $book['book_id']; ?>, '<?php echo addslashes($book['title']); ?>')"><i class="bi bi-cart-plus"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="nav-btn next" onclick="scrollRec(300)"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid-toolbar" style="background: rgba(255, 255, 255, 0.02); border: var(--glass-border); padding: 15px 25px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div>พบทั้งหมด <span class="badge bg-primary rounded-pill px-3" id="totalCount">...</span> รายการ</div>
                    <select class="form-select w-auto bg-transparent text-white border-secondary rounded-pill" id="sortSelect">
                        <option value="newest" class="text-dark">✨ มาใหม่ล่าสุด</option>
                        <option value="price_low" class="text-dark">💰 ราคา: ต่ำ - สูง</option>
                        <option value="price_high" class="text-dark">💎 ราคา: สูง - ต่ำ</option>
                        <option value="discount" class="text-dark">📉 ส่วนลดสูงสุด</option>
                    </select>
                </div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 g-md-4" id="booksGrid"></div>
                <div id="scrollLoader" class="text-center py-5 d-none"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
        let state = { page: 1, loading: false, hasMore: true, cat: '', sort: 'newest' }, searchTimer;
        
        function logDebug(isError = false) {
            if($('#debug-box').length === 0) $('body').append(`<div id="debug-box" style="position:fixed; bottom:15px; right:15px; background:rgba(15,23,42,0.9); backdrop-filter:blur(8px); padding:6px 12px; border-radius:12px; font-family:monospace; font-size:12px; z-index:9999; border:1px solid rgba(255,255,255,0.1); box-shadow:0 4px 12px rgba(0,0,0,0.5);"></div>`);
            const time = new Date().toLocaleTimeString();
            const color = isError ? '#f87171' : '#4ade80';
            const icon = isError ? 'bi-exclamation-triangle-fill' : 'bi-check-all';
            $('#debug-box').html(`<div class="d-flex align-items-center gap-2"><i class="bi ${icon}" style="color:${color}"></i><span style="color:${color}; font-weight:600;">${time}</span></div>`);
        }

        function scrollRec(amt) { document.getElementById('recContainer').scrollBy({ left: amt, behavior: 'smooth' }); }
        function numberWithCommas(x) { return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","); }

        function showCartToast(title) {
            $('#toast-box').empty();
            const id = 'toast-' + Date.now();
            $('#toast-box').append(`<div id="${id}" class="premium-toast animate__animated animate__zoomIn"><div class="toast-icon-wrapper"><i class="bi bi-bag-heart-fill"></i></div><div class="toast-content-text"><span class="fw-bold d-block text-white mb-1" style="font-size: 1.2rem;">เพิ่มลงตะกร้าแล้ว!</span><span class="text-white-50 small">" ${title} "</span></div><div class="toast-progress-bar"></div></div>`);
            setTimeout(() => { $(`#${id}`).removeClass('animate__zoomIn').addClass('animate__zoomOut'); setTimeout(() => $(`#${id}`).remove(), 500); }, 2500);
        }

        // --- 🌌 Real-time Stock & Price Update ---
        function updateRealtime() {
            const ids = []; 
            $('.book-card').each(function() { 
                let bid = $(this).attr('data-book-id');
                if(bid) ids.push(bid); 
            });
            if(ids.length === 0) return;
            
            // เรียกไฟล์ get_stock_status.php
            fetch(`get_stock_status.php?ids=${ids.join(',')}&t=${Date.now()}`)
                .then(r => r.json())
                .then(data => {
                    const stocks = data.stocks ? data.stocks : (Array.isArray(data) ? data : []);
                    
                    stocks.forEach(item => {
                        const $card = $(`.book-card[data-book-id="${item.book_id}"]`);
                        const $imgContainer = $card.find('.img-container');
                        const $btn = $card.find('.btn-cart-circle');
                        const $priceWrapper = $card.find('.price-wrapper'); 
                        const $badgeWrapper = $card.find('.discount-badge-wrapper'); 

                        // --- 💰 Update Price & Discount ---
                        // ถ้าแอดมินแก้ราคา Logic นี้จะทำงานทันที
                        if(item.price !== undefined) {
                            const price = parseFloat(item.price);
                            const discount = parseFloat(item.discount_percent);
                            const finalPrice = price - (price * discount / 100);
                            const saveAmount = price - finalPrice;

                            let priceHtml = '';
                            if(discount > 0) {
                                priceHtml = `
                                    <div class="price-original">฿${numberWithCommas(price)}</div>
                                    <div class="price-final">฿${numberWithCommas(finalPrice)}</div>
                                    <div class="save-amount small">ประหยัด ฿${numberWithCommas(saveAmount)}</div>
                                `;
                                $badgeWrapper.html(`<div class="discount-badge-realtime animate__animated animate__fadeIn">-` + Math.floor(discount) + `%</div>`);
                            } else {
                                priceHtml = `<div class="price-final">฿${numberWithCommas(price)}</div>`;
                                $badgeWrapper.empty();
                            }
                            $priceWrapper.html(priceHtml);
                        }

                        // --- 📦 Update Stock ---
                        if(item.stock_quantity <= 0) {
                            $card.addClass('is-sold-out');
                            $imgContainer.find('img').css({'filter': 'grayscale(100%)', 'opacity': '0.4'});
                            if($imgContainer.find('.sold-out-mask').length === 0) {
                                $imgContainer.append('<div class="sold-out-mask animate__animated animate__fadeIn"><div class="sold-out-label">SOLD OUT</div></div>');
                            }
                            if(!$btn.hasClass('disabled')) {
                                $btn.addClass('disabled').text('หมด').attr('disabled', true).css('pointer-events', 'none').removeAttr('onclick');
                            }
                        } else { 
                            $card.removeClass('is-sold-out');
                            $imgContainer.find('img').css({'filter': 'none', 'opacity': '1'});
                            $imgContainer.find('.sold-out-mask').remove();
                            if($btn.hasClass('disabled')) {
                                const title = $card.find('.book-title').text().replace(/'/g, "\\'");
                                $btn.removeClass('disabled').html('<i class="bi bi-cart-plus"></i>').attr('disabled', false).css('pointer-events', 'auto');
                                $btn.attr('onclick', `event.stopPropagation(); addToCart(this, ${item.book_id}, '${title}')`);
                            }
                        }
                    });
                    logDebug(false);
                })
                .catch(err => { console.error(err); logDebug(true); });
        }
        setInterval(updateRealtime, 2500);

        async function fetchBooks(reset = false) {
            if(state.loading || (!state.hasMore && !reset)) return;
            const search = $('#searchInput').val() || '';

            if(search.length > 0 || state.cat !== '') { $('#recommended-section').slideUp(300); } else { $('#recommended-section').slideDown(300); }

            if(reset) { state.page = 1; state.hasMore = true; $('#booksGrid').empty(); }
            state.loading = true; $('#scrollLoader').removeClass('d-none');
            
            try {
                const res = await fetch(`?ajax=load_books&page=${state.page}&cat=${state.cat}&sort=${state.sort}&search=${encodeURIComponent(search)}`);
                const data = await res.json();
                $('#totalCount').text(data.total);

                data.books.forEach(b => {
                    const final = b.price - (b.price * b.discount_percent / 100);
                    const isOut = (b.stock_quantity <= 0);
                    
                    $('#booksGrid').append(`
                        <div class="col animate__animated animate__fadeInUp">
                            <div class="book-card ${isOut ? 'is-sold-out' : ''}" data-book-id="${b.book_id}" onclick="location.href='product_detail.php?id=${b.book_id}'">
                                <div class="img-container">
                                    <div class="discount-badge-wrapper">
                                        ${b.discount_percent > 0 ? `<div class="discount-badge-realtime">-${Math.floor(b.discount_percent)}%</div>` : ''}
                                    </div>
                                    <img src="uploads/${b.image}" onerror="this.src='assets/no-image.png'" class="w-100 h-100 object-fit-cover" style="${isOut ? 'filter:grayscale(100%); opacity:0.4;' : ''}">
                                    ${isOut ? '<div class="sold-out-mask"><div class="sold-out-label">SOLD OUT</div></div>' : ''}
                                </div>
                                <div class="book-title">${b.title}</div>
                                <div class="d-flex justify-content-between align-items-end mt-auto pt-2">
                                    <div class="price-wrapper">
                                        ${b.discount_percent > 0 ? 
                                            `<div class="price-original">฿${numberWithCommas(b.price)}</div>
                                             <div class="price-final">฿${numberWithCommas(final)}</div>` 
                                            : `<div class="price-final">฿${numberWithCommas(b.price)}</div>`
                                        }
                                    </div>
                                    <button class="btn-cart-circle ${isOut ? 'disabled' : ''}" ${isOut ? 'disabled' : ''} onclick="event.stopPropagation(); addToCart(this, ${b.book_id}, '${b.title}')">
                                        ${isOut ? 'หมด' : '<i class="bi bi-cart-plus"></i>'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                });
                state.hasMore = data.has_more; state.page++; 
            } finally { state.loading = false; $('#scrollLoader').addClass('d-none'); updateRealtime(); }
        }

        async function addToCart(btn, id, title) {
            const res = await fetch(`cart_action.php?action=add&id=${id}&ajax=1`);
            const data = await res.json();
            if(data.status === 'success') { confetti({ particleCount: 50, spread: 60, origin: { y: 0.7 } }); showCartToast(title); if(window.updateCartCount) updateCartCount(data.cart_count); }
        }

        $(document).on('input', '#searchInput', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => fetchBooks(true), 600); });
        $(document).ready(() => { fetchBooks(true); $('.cat-link').click(function(e) { e.preventDefault(); $('.cat-link').removeClass('active'); $(this).addClass('active'); state.cat = $(this).data('cat'); fetchBooks(true); }); $('#sortSelect').change(function() { state.sort = $(this).val(); fetchBooks(true); }); $(window).scroll(() => { if ($(window).scrollTop() + $(window).height() > $(document).height() - 200) fetchBooks(); }); });

        const canvas = document.getElementById('starfield'), ctx = canvas.getContext('2d');
        let stars = [], w, h;
        function resize() { w = canvas.width = window.innerWidth; h = canvas.height = window.innerHeight; }
        window.addEventListener('resize', resize); resize();
        for(let i=0; i<60; i++) stars.push({x: Math.random()*w, y: Math.random()*h, s: Math.random()*2});
        function anim() { ctx.clearRect(0,0,w,h); ctx.fillStyle="#fff"; stars.forEach(s => { s.y -= 0.2; if(s.y<0) s.y=h; ctx.beginPath(); ctx.arc(s.x, s.y, s.s, 0, Math.PI * 2); ctx.fill(); }); requestAnimationFrame(anim); }
        anim();
    </script>
</body>
</html>
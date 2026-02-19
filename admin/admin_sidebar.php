<?php
// กำหนดตัวแปรหน้าปัจจุบันเพื่อให้เมนูเป็นสีม่วง
$current_page = $current_page ?? '';
?>
<div class="glass-card p-3 sticky-top" style="top: 100px;">
    <nav class="nav flex-column">
        <a href="dashboard.php" class="sidebar-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> ภาพรวมระบบ
        </a>

        <a href="orders.php" class="sidebar-link <?php echo ($current_page == 'orders') ? 'active' : ''; ?>">
            <i class="bi bi-cart-check"></i> จัดการออเดอร์
            <?php if(isset($pending_orders_count) && $pending_orders_count > 0): ?>
                <span id="sidebarOrderBadge" class="badge-notify ms-auto"><?php echo $pending_orders_count; ?></span>
            <?php elseif(isset($pending_orders) && $pending_orders > 0): ?>
                <span id="sidebarOrderBadge" class="badge-notify ms-auto"><?php echo $pending_orders; ?></span>
            <?php endif; ?>
        </a>

        <a href="books.php" class="sidebar-link <?php echo ($current_page == 'books') ? 'active' : ''; ?>">
            <i class="bi bi-book"></i> คลังหนังสือ
        </a>

        <a href="categories.php" class="sidebar-link <?php echo ($current_page == 'categories') ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i> หมวดหมู่
        </a>
    </nav>
</div>
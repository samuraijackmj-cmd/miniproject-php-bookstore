<style>
    /* จัดระเบียบ Header */
    .admin-header-actions {
        display: flex;
        align-items: center;
        gap: 1.2rem;
    }

    /* ปุ่มแจ้งเตือน (กระดิ่ง) */
    .btn-notify {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #94a3b8; /* text-muted */
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-notify:hover {
        background: rgba(99, 102, 241, 0.15); /* สีม่วงจางๆ */
        color: #818cf8; /* สีม่วงสว่าง */
        border-color: rgba(99, 102, 241, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .btn-notify i {
        font-size: 1.25rem;
        transition: transform 0.3s ease;
    }

    .btn-notify:hover i {
        animation: bellRing 0.5s ease-in-out both; /* กระดิ่งสั่น */
    }

    @keyframes bellRing {
        0%, 100% { transform: rotate(0); }
        20%, 60% { transform: rotate(15deg); }
        40%, 80% { transform: rotate(-15deg); }
    }

    /* จุดแดงแจ้งเตือน */
    .notify-dot {
        position: absolute;
        top: 10px;
        right: 12px;
        width: 10px;
        height: 10px;
        background: #ef4444; /* สีแดง */
        border: 2px solid #0f172a; /* ตัดขอบด้วยสีพื้นหลัง */
        border-radius: 50%;
        animation: pulseRed 2s infinite;
    }

    @keyframes pulseRed {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    /* แคปซูลโปรไฟล์ */
    .profile-pill {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 5px 16px 5px 5px;
        border-radius: 50px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        user-select: none;
    }

    .profile-pill:hover, .profile-pill[aria-expanded="true"] {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    /* รูปวงกลม Avatar */
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #a855f7); /* ม่วงไล่สี */
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
    }

    .profile-info {
        display: flex;
        flex-direction: column;
        line-height: 1.3;
    }

    .profile-name {
        font-weight: 700;
        font-size: 0.95rem;
        color: #f1f5f9;
    }

    .profile-role {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 500;
    }

    /* Dropdown แต่งสวย */
    .dropdown-menu-custom {
        background: rgba(30, 41, 59, 0.95);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 8px;
        margin-top: 12px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        min-width: 220px;
    }

    .dropdown-item-custom {
        color: #cbd5e1;
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 0.9rem;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 2px;
    }

    .dropdown-item-custom:hover {
        background: rgba(99, 102, 241, 0.15);
        color: white;
    }

    .dropdown-item-custom.danger:hover {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }
    
    /* Animation โผล่มา */
    .animate-fade-down {
        animation: fadeDown 0.6s ease-out;
    }
    
    @keyframes fadeDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-5 animate-fade-down">
    <div>
        <h2 class="fw-bold mb-1 d-flex align-items-center gap-3 text-white">
            <i class="bi bi-grid-1x2-fill text-primary"></i> 
            Admin Console
        </h2>
        <p class="text-secondary m-0 ps-1" style="font-size: 0.95rem;">
            จัดการระบบคลังสินค้าและหมวดหมู่หนังสือ
        </p>
    </div>

    <div class="admin-header-actions">
        <div class="btn-notify" data-bs-toggle="tooltip" title="การแจ้งเตือน">
            <i class="bi bi-bell"></i>
            <?php if(isset($total_alerts) && $total_alerts > 0): ?>
                <div class="notify-dot"></div>
            <?php endif; ?>
        </div>

        <div class="dropdown">
            <div class="profile-pill" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-circle">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="profile-info d-none d-md-flex text-start">
                    <span class="profile-name">Administrator</span>
                    <span class="profile-role">System Manager</span>
                </div>
                <i class="bi bi-chevron-down text-secondary ms-2 small"></i>
            </div>
            
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                <li>
                    <div class="px-3 py-2 border-bottom border-secondary border-opacity-10 mb-2">
                        <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Signed in as</small>
                        <div class="fw-bold text-white">admin@bookstore.com</div>
                    </div>
                </li>
                <li>
                    <a class="dropdown-item dropdown-item-custom" href="../index.php">
                        <i class="bi bi-shop-window text-primary"></i> ไปหน้าร้านค้า
                    </a>
                </li>
                <li>
                    <a class="dropdown-item dropdown-item-custom" href="#">
                        <i class="bi bi-gear text-info"></i> ตั้งค่าระบบ
                    </a>
                </li>
                <li><hr class="dropdown-divider border-secondary border-opacity-10 my-1"></li>
                <li>
                    <a class="dropdown-item dropdown-item-custom danger" href="../logout.php" onclick="return confirm('ยืนยันการออกจากระบบ?');">
                        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
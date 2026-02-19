<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปิดปรับปรุงชั่วคราว | Site Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap');
        
        body {
            font-family: 'Kanit', sans-serif;
            background: #0f111a;
            color: #fff;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-image: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
        }

        /* ✅ ปุ่ม Admin ประตูหลัง - แก้ไขให้กดติด 100% */
        .admin-backdoor {
            position: fixed !important;
            top: 25px;
            right: 25px;
            z-index: 99999 !important; /* สูงกว่าทุกเลเยอร์ */
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff !important;
            padding: 10px 20px;
            border-radius: 14px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer !important;
            backdrop-filter: blur(10px);
        }
        .admin-backdoor:hover {
            background: #6366f1;
            border-color: transparent;
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.4);
            transform: translateY(-2px);
        }

        .maintenance-card {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            backdrop-filter: blur(20px);
            box-shadow: 0 30px 70px rgba(0,0,0,0.7);
            max-width: 500px;
            width: 90%;
            position: relative;
            z-index: 10;
        }

        .icon-box {
            font-size: 5rem;
            color: #f59e0b;
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .spinner-custom {
            color: #6366f1;
            width: 3rem;
            height: 3rem;
            margin-bottom: 2rem;
            filter: drop-shadow(0 0 10px rgba(99, 102, 241, 0.3));
        }

        .status-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>

    <div class="admin-backdoor" onclick="window.location.href='login.php';">
        <i class="bi bi-shield-lock-fill"></i> สำหรับผู้ดูแลระบบ
    </div>

    <div class="maintenance-card">
        <div class="icon-box">
            <i class="bi bi-cone-striped"></i>
        </div>
        
        <h2 class="fw-bold mb-3">ปิดปรับปรุงชั่วคราว</h2>
        
        <p class="text-white-50 mb-4">
            ขออภัยในความไม่สะดวก ขณะนี้ระบบกำลังอัปเกรดสต็อกสินค้าและฟีเจอร์ใหม่ๆ เพื่อคุณ
        </p>

        <div class="spinner-border spinner-custom" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>

        <div class="status-text">
            <i class="bi bi-broadcast me-2"></i> กำลังตรวจสอบสถานะระบบแบบ Real-time...
        </div>
    </div>

    <script>
        function checkStatus() {
            // ✅ เพิ่ม t= เพื่อป้องกัน Cache ของเบราว์เซอร์
            fetch('ajax_check_maintenance.php?t=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    // ถ้าสถานะกลับมาเป็น 0 แสดงว่า Admin เปิดเว็บแล้ว ให้เด้งกลับทันที
                    if (data.maintenance === 0) {
                        window.location.href = 'index.php';
                    }
                })
                .catch(err => console.log("System Syncing..."));
        }
        
        // ตรวจสอบทุกๆ 5 วินาที
        setInterval(checkStatus, 5000);
    </script>
</body>
</html>
<?php
session_start();
require_once 'config/db.php';

// 1. ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 2. Logic การบันทึกข้อมูลเมื่อกดปุ่ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // ตรวจสอบค่าว่าง
    if (empty($full_name) || empty($email) || empty($phone) || empty($address)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วนทุกช่อง";
    } else {
        // อัปเดตข้อมูลทุกอย่างในคำสั่งเดียว
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$full_name, $email, $phone, $address, $user_id])) {
            $success = "อัปเดตข้อมูลเรียบร้อยแล้ว!";
            // อัปเดตข้อมูลใน Session
            $_SESSION['full_name'] = $full_name;
            header("Refresh:0"); // รีเฟรชเพื่อแสดงข้อมูลใหม่
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}

// 3. ดึงข้อมูลล่าสุดมาแสดงในฟอร์ม
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการที่อยู่จัดส่ง | BookStore Premium</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        /* --- Theme Variables (Midnight Space) --- */
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --accent: #a855f7;
            --bg-dark: #0f172a;
            --text-white: #ffffff;
            --text-gray: #cbd5e1;
            --glass-bg: rgba(30, 41, 59, 0.6);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-white);
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
        }

        /* --- 🌌 Background Effects --- */
        .ambient-light {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: 
                radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%);
            pointer-events: none;
        }

        #starfield {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            pointer-events: none;
        }

        /* --- 📦 Glass Card --- */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        /* --- 📝 Form Elements --- */
        .form-label { color: var(--text-gray); font-weight: 500; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px; }
        
        .input-group-custom { position: relative; }
        
        .form-control-dark {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: white !important;
            padding: 12px 45px 12px 15px;
            border-radius: 12px;
            transition: 0.3s;
        }
        
        .form-control-dark:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        textarea.form-control-dark {
            min-height: 120px;
            resize: vertical;
        }

        /* ไอคอนในช่อง Input */
        .input-icon {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            color: var(--primary); opacity: 0.6; pointer-events: none; transition: 0.3s;
        }
        textarea + .input-icon { top: 20px; transform: none; } /* ปรับตำแหน่งไอคอนสำหรับ Textarea */
        
        .form-control-dark:focus + .input-icon {
            opacity: 1; transform: translateY(-50%) scale(1.1);
        }
        textarea:focus + .input-icon { transform: scale(1.1); }

        /* --- 🎨 Buttons --- */
        .btn-gradient {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none; color: white; padding: 12px; border-radius: 12px;
            font-weight: 600; transition: 0.3s; width: 100%; margin-top: 1rem;
        }
        .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); color: white; }

        .btn-outline-glass {
            background: transparent; border: 1px solid var(--glass-border);
            color: var(--text-gray); padding: 12px; border-radius: 12px;
            transition: 0.3s; text-decoration: none; display: block;
            text-align: center; width: 100%; margin-top: 10px;
        }
        .btn-outline-glass:hover { border-color: var(--primary); color: white; background: rgba(99, 102, 241, 0.1); }

        /* Header Title */
        .page-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .page-title { font-size: 1.8rem; font-weight: 700; color: #fff; margin: 0; }
        .page-subtitle { color: var(--text-gray); font-size: 0.9rem; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>
    
    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                
                <div class="glass-card animate__animated animate__fadeInUp">
                    
                    <div class="page-header d-flex align-items-center gap-3">
                        <div style="background: rgba(99, 102, 241, 0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-geo-alt-fill fs-3 text-primary"></i>
                        </div>
                        <div>
                            <h1 class="page-title">จัดการข้อมูลที่อยู่</h1>
                            <p class="page-subtitle mb-0">แก้ไขข้อมูลส่วนตัวและการจัดส่งสินค้า</p>
                        </div>
                    </div>

                    <?php if($success): ?>
                        <div class="alert alert-success bg-success bg-opacity-25 text-white border-0 text-center mb-4 rounded-3">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-25 text-white border-0 text-center mb-4 rounded-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-12"><h5 class="text-white mb-3 mt-2"><i class="bi bi-person me-2"></i>ข้อมูลพื้นฐาน</h5></div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ-นามสกุล</label>
                                <div class="input-group-custom">
                                    <input type="text" name="full_name" class="form-control form-control-dark" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    <i class="bi bi-person-fill input-icon"></i>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">อีเมล</label>
                                <div class="input-group-custom">
                                    <input type="email" name="email" class="form-control form-control-dark" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <i class="bi bi-envelope-fill input-icon"></i>
                                </div>
                            </div>

                            <div class="col-12"><h5 class="text-white mb-3 mt-4"><i class="bi bi-truck me-2"></i>ข้อมูลการจัดส่ง</h5></div>

                            <div class="col-12">
                                <label class="form-label">เบอร์โทรศัพท์</label>
                                <div class="input-group-custom">
                                    <input type="tel" name="phone" class="form-control form-control-dark" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="08xxxxxxxx" pattern="[0-9]{10}" required>
                                    <i class="bi bi-telephone-fill input-icon"></i>
                                </div>
                                <div class="form-text text-white-50 ms-1">* กรอกเฉพาะตัวเลข 10 หลัก</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">ที่อยู่จัดส่งสินค้า</label>
                                <div class="input-group-custom">
                                    <textarea name="address" class="form-control form-control-dark" required placeholder="บ้านเลขที่, ถนน, ตำบล/แขวง, อำเภอ/เขต, จังหวัด, รหัสไปรษณีย์"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                    <i class="bi bi-house-door-fill input-icon"></i>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <button type="submit" class="btn btn-gradient btn-save">
                                <i class="bi bi-save-fill me-2"></i> บันทึกการเปลี่ยนแปลง
                            </button>
                            <a href="profile.php" class="btn btn-outline-glass">
                                <i class="bi bi-arrow-left me-2"></i> ย้อนกลับไปหน้าโปรไฟล์
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- 🌌 Starfield Animation (Same as Index) ---
        const canvas = document.getElementById('starfield');
        const ctx = canvas.getContext('2d');
        let stars = [];
        let width, height;

        function resize() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }

        class Star {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.size = Math.random() * 2;
                this.speedY = Math.random() * 0.5 + 0.1;
                this.opacity = Math.random();
                this.fadeDir = Math.random() > 0.5 ? 0.01 : -0.01;
            }
            update() {
                this.y -= this.speedY;
                if (this.y < 0) this.y = height;
                this.opacity += this.fadeDir;
                if (this.opacity > 1 || this.opacity < 0.2) this.fadeDir = -this.fadeDir;
            }
            draw() {
                ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity * 0.5})`;
                ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill();
            }
        }

        function initStars() {
            stars = [];
            for (let i = 0; i < 60; i++) stars.push(new Star());
        }

        function animateStars() {
            ctx.clearRect(0, 0, width, height);
            stars.forEach(star => { star.update(); star.draw(); });
            requestAnimationFrame(animateStars);
        }

        window.addEventListener('resize', () => { resize(); initStars(); });
        resize(); initStars(); animateStars();

        // --- Button Loading State ---
        const form = document.querySelector('form');
        const btnSave = document.querySelector('.btn-save');
        
        if (form) {
            form.addEventListener('submit', function() {
                btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
                btnSave.style.opacity = '0.8';
                btnSave.style.pointerEvents = 'none';
            });
        }

        // --- Auto Format Phone Number ---
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
            });
        }
    </script>
</body>
</html>
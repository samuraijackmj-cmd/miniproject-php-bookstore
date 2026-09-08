<?php
session_start();
require_once 'config/db.php';
require_once 'includes/csrf_helper.php';
csrf_generate();

// ตรวจสอบ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// --- 1. Logic การบันทึกข้อมูลส่วนตัว (ปรับปรุงเพื่อรองรับการเปลี่ยนเมล) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    csrf_verify();
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $image_name = $_POST['old_image']; 

    try {
        // ✅ [เพิ่ม] ตรวจสอบว่าอีเมลใหม่ซ้ำกับคนอื่นในฐานข้อมูลหรือไม่
        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_email->execute([$email, $user_id]);
        
        if ($check_email->rowCount() > 0) {
            $error = "❌ อีเมลนี้ถูกใช้งานโดยผู้ใช้ท่านอื่นแล้ว";
        } else {
            // --- จัดการรูปภาพ (โค้ดเดิมพี่) ---
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $profile_image = $_FILES['profile_image'];
                $ext = strtolower(pathinfo($profile_image['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($ext, $allowed)) {
                    $new_name = "profile_" . $user_id . "_" . time() . "." . $ext;
                    $upload_dir = "uploads/profiles/";
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

                    $target = $upload_dir . $new_name;
                    if (move_uploaded_file($profile_image['tmp_name'], $target)) {
                        $image_name = $new_name;
                        if (!empty($_POST['old_image']) && file_exists($upload_dir . $_POST['old_image']) && $_POST['old_image'] != 'default.png') {
                            @unlink($upload_dir . $_POST['old_image']);
                        }
                    }
                }
            }

            // ✅ อัปเดตข้อมูลรวมถึงอีเมล
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, profile_image = ? WHERE user_id = ?");
            if ($stmt->execute([$full_name, $email, $image_name, $user_id])) {
                $success = "อัปเดตข้อมูลเรียบร้อยแล้ว!";
                $_SESSION['full_name'] = $full_name;
                $_SESSION['profile_image'] = $image_name; 
                // ดึงข้อมูลใหม่เพื่อโชว์ในช่อง Input ทันที
            }
        }
    } catch (PDOException $e) {
        $error = "เกิดข้อผิดพลาดทางเทคนิค: " . $e->getMessage();
    }
}

// ดึงข้อมูล User (เพื่อให้ข้อมูลใน Input เป็นค่าล่าสุดเสมอ)
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน | BookStore Premium</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        /* --- 🌌 Midnight Space Theme (โครงเดิมพี่) --- */
        :root {
            --primary: #6366f1; --primary-light: #818cf8; --accent: #a855f7; --bg-dark: #0f172a;
            --text-white: #ffffff; --text-gray: #cbd5e1; --glass-bg: rgba(30, 41, 59, 0.6);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-dark); color: var(--text-white); overflow-x: hidden; min-height: 100vh; position: relative; }
        .ambient-light { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%), radial-gradient(circle at 85% 30%, rgba(244, 63, 94, 0.1), transparent 25%); pointer-events: none; }
        #starfield { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }
        .glass-card { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 24px; padding: 3rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); position: relative; overflow: hidden; }
        .avatar-container { position: relative; width: 150px; height: 150px; margin: 0 auto 2rem; animation: avatarFloat 3s ease-in-out infinite alternate; }
        @keyframes avatarFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .avatar-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 4px solid rgba(255,255,255,0.1); box-shadow: 0 0 20px rgba(99, 102, 241, 0.3); transition: 0.3s; }
        .btn-upload-icon { position: absolute; bottom: 5px; right: 5px; width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid var(--bg-dark); transition: 0.3s; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }

        /* ✅ แก้จุดที่ 2: ปรับตัวหนังสือในช่อง Input ให้เป็นสีดำ */
        .form-control-dark { 
            background: #ffffff !important; /* เปลี่ยนพื้นหลังเป็นสีขาวเพื่อให้สีดำเด่น */
            border: 1px solid var(--glass-border); 
            color: #000000 !important; /* ตัวหนังสือสีดำ */
            padding: 12px 45px 12px 15px; 
            border-radius: 12px; 
            transition: 0.3s; 
        }
        
        .form-control-dark:focus { 
            background: #ffffff !important; 
            color: #000000 !important; 
            border-color: var(--primary); 
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); 
        }

        /* สำหรับช่องที่ Disabled (Username) ให้เป็นสีดำชัดเจนด้วย */
        .form-control-dark:disabled { 
            background: rgba(255, 255, 255, 0.8) !important; 
            color: #000000 !important; 
            -webkit-text-fill-color: #000000; /* บังคับสีดำสำหรับ Chrome/Safari */
            opacity: 1; 
        }

        .form-label { color: var(--text-gray); font-weight: 500; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px; }
        .input-group-custom { position: relative; }
        .input-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--primary); opacity: 0.6; pointer-events: none; transition: 0.3s; }
        .btn-gradient { background: linear-gradient(135deg, var(--primary), var(--accent)); border: none; color: white; padding: 12px; border-radius: 12px; font-weight: 600; transition: 0.3s; width: 100%; margin-top: 1rem; }
        .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); }
        .btn-outline-glass { background: transparent; border: 1px solid var(--glass-border); color: var(--text-gray); padding: 12px; border-radius: 12px; transition: 0.3s; text-decoration: none; display: block; text-align: center; width: 100%; margin-top: 10px; }
        .btn-outline-glass:hover { border-color: var(--primary); color: white; background: rgba(99, 102, 241, 0.1); }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>
    <div class="ambient-light"></div>
    <canvas id="starfield"></canvas>

    <div class="container py-5">
        <form action="" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    
                    <div class="glass-card animate__animated animate__fadeInUp">
                        <div class="text-center mb-4">
                            <div class="avatar-container">
                                <?php 
                                    $img_path = "uploads/profiles/".$user['profile_image'];
                                    $img = (!empty($user['profile_image']) && file_exists($img_path)) ? $img_path : "assets/default-profile.png";
                                ?>
                                <img src="<?php echo $img; ?>" id="preview" class="avatar-img">
                                <label for="imgInput" class="btn-upload-icon"><i class="bi bi-camera-fill"></i></label>
                                <input type="file" name="profile_image" id="imgInput" hidden accept="image/*">
                                <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($user['profile_image'] ?? ''); ?>">
                            </div>
                            <h3 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <div class="mt-2">
                                <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 rounded-pill px-3 py-1">
                                    Member ID: #<?php echo str_pad($user['user_id'], 5, '0', STR_PAD_LEFT); ?>
                                </span>
                            </div>
                        </div>

                        <?php if($success): ?>
                            <div class="alert alert-success bg-success bg-opacity-25 text-white border-0 text-center mb-4 rounded-3">
                                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger bg-danger bg-opacity-25 text-white border-0 text-center mb-4 rounded-3">
                                <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person-badge"></i> ชื่อผู้ใช้ (Username)</label>
                            <div class="input-group-custom">
                                <input type="text" class="form-control form-control-dark" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <i class="bi bi-lock-fill input-icon"></i>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person-vcard"></i> ชื่อ-นามสกุล</label>
                            <div class="input-group-custom">
                                <input type="text" name="full_name" class="form-control form-control-dark" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                <i class="bi bi-pencil-fill input-icon"></i>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-envelope"></i> อีเมล</label>
                            <div class="input-group-custom">
                                <input type="email" name="email" class="form-control form-control-dark" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <i class="bi bi-at input-icon"></i>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-gradient btn-save">
                            <i class="bi bi-save me-2"></i> บันทึกข้อมูล
                        </button>
                        
                        <a href="manage_address.php" class="btn btn-outline-glass">จัดการที่อยู่จัดส่ง</a>
                        <a href="logout.php" class="btn btn-outline-glass border-danger text-danger mt-3" style="border-color: rgba(239, 68, 68, 0.3) !important;">ออกจากระบบ</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const canvas = document.getElementById('starfield'), ctx = canvas.getContext('2d');
        let stars = [], width, height;
        function resize() { width = canvas.width = window.innerWidth; height = canvas.height = window.innerHeight; }
        class Star {
            constructor() { this.x = Math.random() * width; this.y = Math.random() * height; this.size = Math.random() * 2; this.speedY = Math.random() * 0.5 + 0.1; this.opacity = Math.random(); this.fadeDir = Math.random() > 0.5 ? 0.01 : -0.01; }
            update() { this.y -= this.speedY; if (this.y < 0) this.y = height; this.opacity += this.fadeDir; if (this.opacity > 1 || this.opacity < 0.2) this.fadeDir = -this.fadeDir; }
            draw() { ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity * 0.5})`; ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill(); }
        }
        function initStars() { stars = []; for (let i = 0; i < 60; i++) stars.push(new Star()); }
        function animateStars() { ctx.clearRect(0, 0, width, height); stars.forEach(star => { star.update(); star.draw(); }); requestAnimationFrame(animateStars); }
        window.addEventListener('resize', () => { resize(); initStars(); });
        resize(); initStars(); animateStars();

        document.getElementById('imgInput').onchange = evt => {
            const [file] = document.getElementById('imgInput').files;
            if (file) { document.getElementById('preview').src = URL.createObjectURL(file); }
        }
    </script>
</body>
</html>
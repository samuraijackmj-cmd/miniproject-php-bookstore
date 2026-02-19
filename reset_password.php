<?php
// ✅ 1. ตั้งค่า Timezone ให้ตรงกับไทย (ป้องกันปัญหาลิงก์หมดอายุ)
date_default_timezone_set('Asia/Bangkok'); 

session_start();
require_once 'config/db.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: login.php");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token = :token AND token_expire > NOW()");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        die("<div style='color:white; background:#0f172a; height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:Kanit, sans-serif;'>
                <h2 style='color:#ef4444;'>❌ ลิงก์หมดอายุหรือข้อมูลไม่ถูกต้อง</h2>
                <a href='forgot_password.php' style='color:#6366f1; text-decoration:none; margin-top:20px;'>ขอรับลิงก์ใหม่ที่นี่</a>
            </div>");
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_pass = $_POST['password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass !== $confirm_pass) {
            $error = "รหัสผ่านทั้งสองช่องไม่ตรงกัน";
        } elseif (strlen($new_pass) < 6) {
            $error = "รหัสผ่านควรยาวอย่างน้อย 6 ตัวอักษร";
        } else {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expire = NULL WHERE user_id = ?");
            if ($update->execute([$hashed_password, $user['user_id']])) {
                $message = "✅ เปลี่ยนรหัสผ่านสำเร็จ! กำลังพากลับไปหน้าเข้าสู่ระบบ...";
                echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 3000);</script>";
            }
        }
    }
} catch (PDOException $e) { $error = "เกิดข้อผิดพลาดทางเทคนิค"; }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่ | BookStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- 🌌 Midnight Space Theme (โครงเดิมของพี่) --- */
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap');
        :root { --primary: #6366f1; --accent: #a855f7; --bg-dark: #0f172a; --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; margin: 0; }
        body { font-family: 'Kanit', sans-serif; background: var(--bg-dark); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        #particles-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }
        .blob { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.15; animation: blobMove 25s infinite alternate; }
        .blob-1 { width: 600px; height: 600px; background: var(--primary); top: -15%; left: -15%; }
        .blob-2 { width: 500px; height: 500px; background: var(--accent); bottom: -15%; right: -15%; }
        @keyframes blobMove { 0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); } 33% { transform: translate(40px, -40px) rotate(120deg) scale(1.1); } 66% { transform: translate(-30px, 30px) rotate(240deg) scale(0.9); } }
        
        .login-card {
            background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 32px;
            width: 100%; max-width: 420px; padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: cardFloat 0.8s ease-out; position: relative; overflow: hidden; z-index: 10;
        }
        @keyframes cardFloat { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .logo-box { width: 70px; height: 70px; background: var(--primary-gradient); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 30px; color: white; animation: logoFloat 3s ease-in-out infinite; }
        @keyframes logoFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        h2 { color: white; font-weight: 700; text-align: center; margin-bottom: 8px; }
        .subtitle { color: #94a3b8; text-align: center; font-size: 14px; margin-bottom: 30px; }

        /* ✅ ปรับตัวหนังสือเป็นสีดำ */
        .form-control { 
            background: #ffffff !important; 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 14px; 
            padding: 14px 45px; 
            color: #000000 !important; 
            transition: 0.3s; 
        }

        /* ✅ [แก้จุดสำคัญ] ซ่อนไอคอนลูกตาที่เป็นของ Browser เอง */
        .form-control::-ms-reveal,
        .form-control::-ms-clear {
            display: none;
        }

        .form-control:focus { 
            background: #ffffff !important; 
            border-color: var(--accent); 
            box-shadow: 0 0 0 4px rgba(139, 92, 241, 0.2); 
            outline: none; 
        }
        
        .input-group-custom { position: relative; margin-bottom: 20px; }
        .input-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; transition: 0.3s; z-index: 2; }
        
        /* จัดตำแหน่งไอคอนลูกตาที่เราเขียนเองให้ตรง */
        .toggle-password { 
            position: absolute; 
            right: 16px; 
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            color: #64748b; 
            z-index: 2; 
            transition: 0.3s;
        }
        .toggle-password:hover { color: var(--accent); }

        .btn-login { background: var(--primary-gradient); border: none; border-radius: 14px; padding: 14px; color: white; font-weight: 600; width: 100%; transition: 0.3s; box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4); }
        .btn-login:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(99, 102, 241, 0.6); }

        .error-msg { background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; color: #f87171; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .success-msg { background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; color: #34d399; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <canvas id="particles-canvas"></canvas>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">
        <div class="logo-box"><i class="fas fa-shield-alt"></i></div>
        <h2>ตั้งรหัสผ่านใหม่</h2>
        <p class="subtitle">ระบุรหัสผ่านใหม่สำหรับบัญชีของคุณ</p>

        <?php if($message): ?><div class="success-msg text-center"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="error-msg text-center"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="input-group-custom">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="pass" class="form-control" placeholder="รหัสผ่านใหม่" required>
                <i class="fas fa-eye-slash toggle-password" onclick="toggleEye('pass', this)"></i>
            </div>

            <div class="input-group-custom">
                <i class="fas fa-check-double input-icon"></i>
                <input type="password" name="confirm_password" id="conf" class="form-control" placeholder="ยืนยันรหัสผ่านอีกครั้ง" required>
                <i class="fas fa-eye-slash toggle-password" onclick="toggleEye('conf', this)"></i>
            </div>

            <button type="submit" class="btn btn-login"><span>เปลี่ยนรหัสผ่าน</span></button>
        </form>
    </div>

    <script>
        // ✅ ปรับฟังก์ชันให้สลับไอคอนระหว่าง eye และ eye-slash ให้ถูกต้อง
        function toggleEye(id, icon) {
            const el = document.getElementById(id);
            if (el.type === "password") {
                el.type = "text"; 
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                el.type = "password"; 
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        const canvas = document.getElementById('particles-canvas'), ctx = canvas.getContext('2d');
        function setSize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
        setSize();
        const particles = [];
        class Particle {
            constructor() { this.x = Math.random() * canvas.width; this.y = Math.random() * canvas.height; this.size = Math.random() * 2 + 1; this.speedX = Math.random() * 1 - 0.5; this.speedY = Math.random() * 1 - 0.5; this.opacity = Math.random() * 0.5 + 0.2; }
            update() { this.x += this.speedX; this.y += this.speedY; if (this.x > canvas.width) this.x = 0; if (this.x < 0) this.x = canvas.width; if (this.y > canvas.height) this.y = 0; if (this.y < 0) this.y = canvas.height; }
            draw() { ctx.fillStyle = `rgba(99, 102, 241, ${this.opacity})`; ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill(); }
        }
        for (let i = 0; i < 70; i++) particles.push(new Particle());
        function animate() { ctx.clearRect(0, 0, canvas.width, canvas.height); particles.forEach(p => { p.update(); p.draw(); }); requestAnimationFrame(animate); }
        animate();
        window.addEventListener('resize', setSize);
    </script>
</body>
</html>
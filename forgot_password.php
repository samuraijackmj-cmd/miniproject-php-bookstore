<?php
// ✅ 1. ตั้งค่า Timezone ให้ตรงกับไทย (ป้องกันปัญหาลิงก์หมดอายุ)
date_default_timezone_set('Asia/Bangkok'); 

session_start();
require_once 'config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    try {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expire = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expire = ? WHERE email = ?");
            $update->execute([$token, $expire, $email]);

            $test_link = "reset_password.php?token=" . $token;
            // กลับมาใช้สีม่วงสว่างสำหรับลิงก์เทส
            $message = "ระบบได้รับคำขอแล้ว! <br><br> 
                        <a href='$test_link' class='fw-bold text-decoration-none' style='color: #a855f7;'>
                            [ 🔗 คลิกลิงก์นี้เพื่อตั้งรหัสใหม่ (สำหรับเทส) ]
                        </a>";
            
        } else {
            $error = "ไม่พบอีเมลนี้ในระบบของเรา";
        }
    } catch (PDOException $e) {
        $error = "เกิดข้อผิดพลาดทางเทคนิค";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน | BookStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- 🌌 Midnight Space Theme (สีเดิมพี่ 100%) --- */
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap');
        
        :root { 
            --primary: #6366f1; 
            --accent: #a855f7; 
            --bg-dark: #0f172a; 
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); /* ✅ กลับมาใช้สีม่วงเดิม */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; margin: 0; }
        body { font-family: 'Kanit', sans-serif; background: var(--bg-dark); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        
        #particles-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }
        .blob { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.15; animation: blobMove 25s infinite alternate; }
        .blob-1 { width: 600px; height: 600px; background: var(--primary); top: -15%; left: -15%; }
        .blob-2 { width: 500px; height: 500px; background: var(--accent); bottom: -15%; right: -15%; }
        @keyframes blobMove { 0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); } 33% { transform: translate(40px, -40px) rotate(120deg) scale(1.1); } 66% { transform: translate(-30px, 30px) rotate(240deg) scale(0.9); } }

        .login-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 32px; width: 100%; max-width: 420px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); animation: cardFloat 0.8s ease-out; position: relative; z-index: 10; }
        @keyframes cardFloat { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .logo-box { width: 70px; height: 70px; background: var(--primary-gradient); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 30px; color: white; animation: logoFloat 3s ease-in-out infinite; }
        @keyframes logoFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        h2 { color: white; font-weight: 700; text-align: center; margin-bottom: 8px; }
        .subtitle { color: #94a3b8; text-align: center; font-size: 14px; margin-bottom: 30px; }

        /* ✅ ตัวหนังสือในช่อง Input เป็นสีดำ */
        .form-control { background: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 14px; padding: 14px 45px; color: #000000 !important; transition: 0.3s; }
        .form-control:focus { background: #ffffff !important; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.2); outline: none; }

        .input-group-custom { position: relative; margin-bottom: 25px; }
        .input-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; transition: 0.3s; z-index: 2; }
        .form-control:focus + .input-icon { color: var(--accent); } /* ไอคอนเปลี่ยนเป็นม่วงตอนกด */

        /* ✅ ปุ่มสีม่วง Gradient เดิม */
        .btn-login { background: var(--primary-gradient); border: none; border-radius: 14px; padding: 14px; color: white; font-weight: 600; width: 100%; box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4); transition: 0.3s; }
        .btn-login:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(168, 85, 247, 0.6); }

        /* 🎯 ลิงก์จัดกึ่งกลางเป๊ะ */
        .login-footer { 
            margin-top: 25px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-size: 14px; 
        }
        .login-footer a { color: #64748b; text-decoration: none; font-weight: 500; transition: 0.3s; }
        .login-footer a:hover { color: var(--accent); }

        .error-msg { background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; color: #f87171; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .success-msg { background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; color: #34d399; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

    <canvas id="particles-canvas"></canvas>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">
        <div class="logo-box"><i class="fas fa-key"></i></div>
        <h2>กู้คืนรหัสผ่าน</h2>
        <p class="subtitle">กรอกอีเมลที่คุณใช้สมัครสมาชิก</p>

        <?php if($message): ?><div class="success-msg"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="input-group-custom">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-control" placeholder="อีเมลของคุณ" required>
            </div>
            <button type="submit" class="btn btn-login"><span>ส่งลิงก์รีเซ็ตรหัสผ่าน</span></button>
        </form>

        <div class="login-footer">
            <a href="login.php"><i class="fas fa-chevron-left me-1"></i> กลับไปหน้าเข้าสู่ระบบ</a>
        </div>
    </div>

    <script>
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
        const loginCard = document.querySelector('.login-card');
        loginCard.addEventListener('mousemove', (e) => {
            const rect = loginCard.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            loginCard.style.setProperty('--mouse-x', `${x}%`);
            loginCard.style.setProperty('--mouse-y', `${y}%`);
        });
    </script>
</body>
</html>
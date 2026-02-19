<?php
// ✅ 1. ตั้งค่าพื้นฐานและประกาศตัวแปรกัน Warning (คงเดิม)
date_default_timezone_set('Asia/Bangkok'); 
session_start();
require_once 'config/db.php';

// ถ้าล็อกอิน Admin ค้างไว้อยู่แล้ว ให้ส่งไปหลังบ้านเลย
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin/dashboard.php");
    exit;
}

// ถ้าล็อกอิน User ค้างไว้ ให้ส่งไปหน้าแรก
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = ''; 
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- 🔵 Logic: Login ---
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Logic ย้ายตะกร้า
                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $book_id => $qty) {
                        $check = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?");
                        $check->execute([$user['user_id'], $book_id]);
                        $existing = $check->fetch();
                        if ($existing) {
                            $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?")
                                 ->execute([$existing['quantity'] + $qty, $user['user_id'], $book_id]);
                        } else {
                            $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)")
                                 ->execute([$user['user_id'], $book_id, $qty]);
                        }
                    }
                    unset($_SESSION['cart']);
                }
                
                $target = ($user['role'] == 'admin') ? "admin/dashboard.php" : "index.php";
                header("Location: $target");
                exit;
            } else { $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"; }
        } catch (PDOException $e) { $error = "เกิดข้อผิดพลาดทางเทคนิค"; }
    }

    // --- 🟢 Logic: Register ---
    if (isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = trim($_POST['full_name']);

        if ($password != $confirm_password) {
            $error = "รหัสผ่านไม่ตรงกัน";
        } elseif (strlen($password) < 4) {
            $error = "รหัสผ่านต้องมี 4 ตัวอักษรขึ้นไป";
        } else {
            try {
                $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);
                if ($check->rowCount() > 0) {
                    $error = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'customer')");
                    if ($stmt->execute([$username, $hashed_password, $email, $full_name])) {
                        $success = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                    }
                }
            } catch (PDOException $e) { $error = "สมัครสมาชิกไม่สำเร็จ"; }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookStore | Login & Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- 🌌 Midnight Space UI --- */
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap');
        :root { --primary: #6366f1; --accent: #a855f7; --bg-dark: #0f172a; --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Kanit', sans-serif; background: var(--bg-dark); height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        #particles-canvas { position: fixed; inset: 0; z-index: -1; pointer-events: none; }
        .blob { position: absolute; border-radius: 50%; filter: blur(120px); opacity: 0.15; animation: blobMove 30s infinite alternate; }
        .blob-1 { width: 700px; height: 700px; background: var(--primary); top: -20%; left: -20%; }
        .blob-2 { width: 600px; height: 600px; background: var(--accent); bottom: -20%; right: -20%; }
        @keyframes blobMove { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(50px, -50px) scale(1.1); } }
        .container { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 28px; width: 780px; max-width: 95%; min-height: 520px; position: relative; overflow: hidden; z-index: 10; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .form-container { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; width: 50%; }
        .sign-in-container { left: 0; z-index: 2; }
        .container.right-panel-active .sign-in-container { transform: translateX(100%); opacity: 0; }
        .sign-up-container { left: 0; opacity: 0; z-index: 1; }
        .container.right-panel-active .sign-up-container { transform: translateX(100%); opacity: 1; z-index: 5; animation: show 0.6s; }
        @keyframes show { 0%, 49.99% { opacity: 0; z-index: 1; } 50%, 100% { opacity: 1; z-index: 5; } }
        form { background: transparent; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 30px; height: 100%; text-align: center; }
        .input-group-custom { position: relative; width: 100%; margin: 6px 0; }
        .form-control { background: #ffffff !important; border: none; border-radius: 12px; padding: 10px 12px 10px 40px; color: #000 !important; font-size: 13.5px; width: 100% !important; }
        .form-control::-ms-reveal, .form-control::-ms-clear { display: none; }
        .input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 13px; z-index: 3; }
        .toggle-eye { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #64748b; cursor: pointer; z-index: 3; }
        .btn-main { background: var(--primary-gradient); border: none; border-radius: 12px; padding: 11px 35px; color: white; font-weight: 600; margin-top: 5px; cursor: pointer; transition: 0.3s; font-size: 14px; }
        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4); }
        .btn-ghost { background: transparent; border: 1.5px solid white; border-radius: 12px; padding: 9px 30px; color: white; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 13px; }
        .btn-ghost:hover { background: white; color: var(--bg-dark); }
        .overlay-container { position: absolute; top: 0; left: 50%; width: 50%; height: 100%; overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100; }
        .container.right-panel-active .overlay-container { transform: translateX(-100%); }
        .overlay { background: var(--primary-gradient); color: white; position: relative; left: -100%; height: 100%; width: 200%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        .container.right-panel-active .overlay { transform: translateX(50%); }
        .overlay-panel { position: absolute; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 35px; text-align: center; top: 0; height: 100%; width: 50%; transition: transform 0.6s ease-in-out; }
        .overlay-left { transform: translateX(-20%); }
        .container.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .container.right-panel-active .overlay-right { transform: translateX(20%); }
        .password-strength { height: 3px; background: rgba(255, 255, 255, 0.1); border-radius: 4px; margin-top: 4px; width: 100%; display: none; }
        .password-strength.active { display: block; }
        .strength-bar { height: 100%; width: 0%; transition: 0.4s; }
        
        /* Helper for Forgot Password link */
        .hover-white:hover { color: #fff !important; }
    </style>
</head>
<body>

    <canvas id="particles-canvas"></canvas>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="container" id="main-container">
        <div class="form-container sign-up-container">
            <form method="POST">
                <h3 class="text-white mb-2 fw-bold">สร้างบัญชีใหม่</h3>
                <?php if(!empty($error) && isset($_POST['register'])): ?><div class="text-danger small mb-2"><?php echo $error; ?></div><?php endif; ?>
                <div class="input-group-custom"><i class="fas fa-id-card input-icon"></i><input type="text" name="full_name" class="form-control" placeholder="ชื่อ-นามสกุล" required></div>
                <div class="input-group-custom"><i class="fas fa-user input-icon"></i><input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required></div>
                <div class="input-group-custom"><i class="fas fa-envelope input-icon"></i><input type="email" name="email" class="form-control" placeholder="อีเมล" required></div>
                <div class="input-group-custom"><i class="fas fa-lock input-icon"></i><input type="password" name="password" id="reg-pass" class="form-control" placeholder="รหัสผ่าน" required><i class="fas fa-eye-slash toggle-eye" onclick="toggleEye('reg-pass', this)"></i><div class="password-strength" id="strength-container"><div class="strength-bar" id="strength-bar"></div></div></div>
                <div class="input-group-custom"><i class="fas fa-check-double input-icon"></i><input type="password" name="confirm_password" id="reg-conf" class="form-control" placeholder="ยืนยันรหัสผ่าน" required><i class="fas fa-eye-slash toggle-eye" onclick="toggleEye('reg-conf', this)"></i></div>
                <button type="submit" name="register" class="btn-main">สมัครสมาชิก</button>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form method="POST">
                <div class="mb-3"><i class="fas fa-book-reader fa-3x text-primary"></i></div>
                <h3 class="text-white mb-3 fw-bold">เข้าสู่ระบบ</h3>
                
                <?php if(!empty($error) && isset($_POST['login'])): ?><div class="text-danger small mb-2"><?php echo $error; ?></div><?php endif; ?>
                <?php if($success): ?><div class="text-success small mb-2"><?php echo $success; ?></div><?php endif; ?>
                
                <div class="input-group-custom"><i class="fas fa-user input-icon"></i><input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required></div>
                
                <div class="input-group-custom"><i class="fas fa-lock input-icon"></i><input type="password" name="password" id="login-pass" class="form-control" placeholder="รหัสผ่าน" required><i class="fas fa-eye-slash toggle-eye" onclick="toggleEye('login-pass', this)"></i></div>
                
                <div class="w-100 text-end mt-2 mb-3">
                    <a href="forgot_password.php" class="small text-white-50 text-decoration-none hover-white" style="font-size: 0.85rem; transition: 0.3s;">
                        <i class="fas fa-key me-1"></i> ลืมรหัสผ่าน?
                    </a>
                </div>

                <button type="submit" name="login" class="btn-main w-100">เข้าสู่ระบบ</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left"><h2 class="text-white fw-bold">มีบัญชีแล้ว?</h2><p class="small my-3">มาท่องโลกหนังสือกันต่อเลย</p><button class="btn-ghost" id="signInBtn">ไปหน้าเข้าสู่ระบบ</button></div>
                <div class="overlay-panel overlay-right"><h2 class="text-white fw-bold">สวัสดี!</h2><p class="small my-3">มาเริ่มต้นการเดินทางกับเรา</p><button class="btn-ghost" id="signUpBtn">สมัครสมาชิกใหม่</button></div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('main-container');
        document.getElementById('signUpBtn').addEventListener('click', () => container.classList.add("right-panel-active"));
        document.getElementById('signInBtn').addEventListener('click', () => container.classList.remove("right-panel-active"));
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'signup') { container.classList.add("right-panel-active"); }

        function checkMaintenanceInLogin() {
            fetch('ajax_check_maintenance.php?t=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    if (data.maintenance === 1) {
                        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
                        const userRole = "<?php echo $_SESSION['role'] ?? ''; ?>";
                        if (isLoggedIn && userRole !== 'admin') {
                            window.location.href = 'maintenance.php';
                        }
                    }
                })
                .catch(err => console.log("System Checking..."));
        }
        setInterval(checkMaintenanceInLogin, 5000);

        function toggleEye(id, icon) { const el = document.getElementById(id); if (el.type === "password") { el.type = "text"; icon.classList.replace('fa-eye-slash', 'fa-eye'); } else { el.type = "password"; icon.classList.replace('fa-eye', 'fa-eye-slash'); } }
        const passInput = document.getElementById('reg-pass');
        const bar = document.getElementById('strength-bar');
        passInput.addEventListener('input', () => { const val = passInput.value; document.getElementById('strength-container').classList.toggle('active', val.length > 0); let score = 0; if (val.length > 6) score++; if (/[A-Z]/.test(val)) score++; if (/[0-9]/.test(val)) score++; const colors = ['#ef4444', '#f59e0b', '#10b981']; bar.style.width = (val.length === 0) ? "0%" : (score + 1) * 33 + "%"; bar.style.background = colors[score] || colors[0]; });
        
        const canvas = document.getElementById('particles-canvas'), ctx = canvas.getContext('2d');
        function setSize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
        setSize(); window.addEventListener('resize', setSize);
        const particles = [];
        class Particle { constructor() { this.x = Math.random() * canvas.width; this.y = Math.random() * canvas.height; this.size = Math.random() * 2 + 1; this.speedX = Math.random() * 1 - 0.5; this.speedY = Math.random() * 1 - 0.5; this.opacity = Math.random() * 0.5 + 0.2; } update() { this.x += this.speedX; this.y += this.speedY; if (this.x > canvas.width || this.x < 0) this.speedX *= -1; if (this.y > canvas.height || this.y < 0) this.speedY *= -1; } draw() { ctx.fillStyle = `rgba(99, 102, 241, ${this.opacity})`; ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill(); } }
        for (let i = 0; i < 70; i++) particles.push(new Particle());
        function animate() { ctx.clearRect(0, 0, canvas.width, canvas.height); particles.forEach(p => { p.update(); p.draw(); }); requestAnimationFrame(animate); }
        animate();
    </script>
</body>
</html>
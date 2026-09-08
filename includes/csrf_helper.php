<?php
/**
 * CSRF Protection Helper
 */

/** สร้าง CSRF token ใหม่ถ้ายังไม่มี */
function csrf_generate(): void {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/** คืนค่า hidden input HTML */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
}

/** ตรวจสอบ CSRF token — ถ้าไม่ผ่านให้ die() */
function csrf_verify(): void {
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('<h1>403 Forbidden</h1><p>Invalid security token. Please go back and try again.</p>');
    }
}

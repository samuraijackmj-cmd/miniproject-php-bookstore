<?php
require_once 'config/db.php';
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$stmt = $conn->query("SELECT maintenance_mode FROM settings WHERE id = 1");
$res = $stmt->fetch();
echo json_encode(['maintenance' => (int)($res['maintenance_mode'] ?? 0)]);
exit;
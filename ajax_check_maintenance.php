<?php
require_once 'config/db.php';
header('Content-Type: application/json');
$stmt = $conn->query("SELECT maintenance_mode FROM settings WHERE id = 1");
$res = $stmt->fetch();
echo json_encode(['maintenance' => (int)($res['maintenance_mode'] ?? 0)]);
exit;
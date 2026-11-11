<?php
require_once __DIR__ . '/../includes/auth.php';
$u = current_user(); header('Content-Type: application/json');
if (!$u || $u['role'] !== 'customer') { http_response_code(401); exit('{}'); }
$s = db()->prepare('SELECT balance FROM accounts WHERE user_id=?'); $s->execute([$u['user_id']]);
echo json_encode(['balance' => money($s->fetchColumn())]);

<?php
require_once __DIR__ . '/../config/database.php';

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return 'KES ' . number_format((float)$n, 2); }
function now(): string { return date('Y-m-d H:i:s'); }

function audit(?int $userId, string $action, string $details = ''): void {
    db()->prepare('INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?,?,?,?)')
        ->execute([$userId, $action, $details, now()]);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid request token.'); }
}

function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

/** Unique reference, generated when the form is shown so a re-submit carries the same reference. */
function new_transaction_ref(): string {
    return 'TXN' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3)));
}

function valid_phone(string $p): bool { return (bool)preg_match('/^(\+254|254|0)(7|1)\d{8}$/', $p); }
function normalise_phone(string $p): string {
    $p = preg_replace('/\D/', '', $p);
    if (str_starts_with($p, '0')) $p = '254' . substr($p, 1);
    return '+' . $p;
}
function valid_amount($a): bool { return is_numeric($a) && (float)$a > 0 && (float)$a <= 1000000 && preg_match('/^\d+(\.\d{1,2})?$/', trim((string)$a)); }
function mask_account(string $n): string { return '****' . substr($n, -4); }

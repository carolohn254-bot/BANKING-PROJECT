<?php
require_once __DIR__ . '/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
function base_url(): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $dir = preg_replace('#/(customer|admin|api)$#', '', $dir);
    return rtrim($dir, '/');
}
function redirect(string $path): never { header('Location: ' . base_url() . '/' . ltrim($path, '/')); exit; }

function current_user(): ?array { return $_SESSION['user'] ?? null; }

/** Every protected page calls this first. */
function require_role(string $role): array {
    $u = current_user();
    if (!$u) redirect('index.php');
    if (time() - ($_SESSION['last'] ?? 0) > SESSION_TIMEOUT) {
        audit($u['user_id'], 'SESSION_TIMEOUT', 'Inactive session ended');
        session_unset(); session_destroy(); session_start();
        flash('Your session expired. Please log in again.', 'err');
        redirect('index.php');
    }
    $_SESSION['last'] = time();
    if ($u['role'] !== $role) { http_response_code(403); exit('Access denied.'); }
    // re-check status each request so a suspension takes effect immediately
    $st = db()->prepare('SELECT status FROM users WHERE user_id=?'); $st->execute([$u['user_id']]);
    if ($st->fetchColumn() !== 'active') { session_unset(); redirect('index.php'); }
    return $u;
}

/** @return string|null error message */
function attempt_login(string $username, string $password): ?string {
    $st = db()->prepare('SELECT * FROM users WHERE username=?'); $st->execute([trim($username)]);
    $u = $st->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) {
        audit($u['user_id'] ?? null, 'LOGIN_FAILED', 'Username: ' . substr(trim($username), 0, 50));
        return 'Wrong username or password/PIN.';
    }
    if ($u['status'] !== 'active') { audit($u['user_id'], 'LOGIN_REFUSED', 'Suspended account'); return 'This account is suspended. Please contact the bank.'; }
    session_regenerate_id(true);
    $_SESSION['user'] = ['user_id' => (int)$u['user_id'], 'username' => $u['username'], 'full_name' => $u['full_name'], 'role' => $u['role']];
    $_SESSION['last'] = time();
    audit((int)$u['user_id'], 'LOGIN', 'Successful login');
    return null;
}
function do_logout(): void {
    if ($u = current_user()) audit($u['user_id'], 'LOGOUT', '');
    session_unset(); session_destroy();
}

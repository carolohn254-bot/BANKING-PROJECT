<?php
require_once __DIR__ . '/includes/auth.php';
$u = current_user();
if ($u) redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? ''); $pw = $_POST['password'] ?? '';
    if ($username === '' || $pw === '') $error = 'Enter your username and password or PIN.';
    else { $error = attempt_login($username, $pw); if (!$error) redirect('index.php'); }
}
$f = flash();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - Mobile Banking Notifications</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="login"><div class="logo">&#9679; Real-Time Mobile Banking</div>
<div class="card"><h2>Log in</h2>
<?php if ($error): ?><div class="msg err"><?= e($error) ?></div><?php endif; ?>
<?php if ($f): ?><div class="msg <?= e($f[1]) ?>"><?= e($f[0]) ?></div><?php endif; ?>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<label for="username">Username</label><input id="username" name="username" required maxlength="50" autofocus>
<label for="password">Password or PIN</label><input id="password" name="password" type="password" required>
<div class="btns"><button class="btn" style="width:100%">Log in</button></div></form>
<div class="demo"><b>Demo accounts (simulated data):</b><br>Customer: john / 1234 &middot; mary / 2345 &middot; peter / 3456<br>Administrator: admin / admin123<br>Suspended: sam / 4567</div>
</div></div></body></html>

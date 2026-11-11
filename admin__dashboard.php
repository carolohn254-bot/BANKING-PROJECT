<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php'; require_once __DIR__ . '/../includes/sms.php';
$u = require_role('admin'); $pdo = db(); $today = date('Y-m-d');
$q = fn($sql, $p = []) => (function () use ($pdo, $sql, $p) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); })();
$cust = $q("SELECT COUNT(*) FROM users WHERE role='customer'");
$tx = $q('SELECT COUNT(*) FROM transactions WHERE created_at>=?', ["$today 00:00:00"]);
$by = []; foreach (['sent', 'failed', 'pending'] as $s) $by[$s] = (int)$q('SELECT COUNT(*) FROM notifications WHERE status=?', [$s]);
$fails = $pdo->query("SELECT n.*, t.transaction_ref FROM notifications n JOIN transactions t ON t.transaction_id=n.transaction_id WHERE n.status<>'sent' ORDER BY n.notification_id DESC LIMIT 5")->fetchAll();
$max = max(1, ...array_values($by));
page_start('Administrator dashboard', 'admin', 'dashboard'); ?>
<div class="grid"><div class="stat"><div class="l">Customers</div><div class="v"><?= $cust ?></div></div><div class="stat"><div class="l">Transactions today</div><div class="v"><?= $tx ?></div></div>
<div class="stat"><div class="l">Notifications sent</div><div class="v"><?= $by['sent'] ?></div></div><div class="stat"><div class="l">Failed / pending</div><div class="v"><?= $by['failed'] + $by['pending'] ?></div></div></div>
<div class="two"><div class="card"><h2>Notifications by status</h2><div class="bars" style="margin-bottom:26px"><?php foreach ($by as $k => $v) echo '<div style="height:' . max(3, round($v / $max * 100)) . '%"><span>' . $v . '</span><em>' . $k . '</em></div>'; ?></div></div>
<div class="card"><h2>Recent failures</h2><?php foreach ($fails as $f) echo '<p class="small">#' . $f['notification_id'] . ' ' . e($f['transaction_ref']) . ' ' . badge($f['status']) . '</p>'; if (!$fails) echo '<p class="muted">No failed or pending notifications.</p>'; ?>
<p class="small">SMS gateway: <?= gateway_is_down() ? '<span class="badge b-failed">simulated outage</span>' : '<span class="badge b-sent">online</span>' ?> &mdash; <a href="delivery.php">manage</a></p></div></div>
<?php page_end('admin');

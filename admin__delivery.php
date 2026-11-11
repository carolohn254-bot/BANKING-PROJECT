<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php'; require_once __DIR__ . '/../includes/notify.php';
$u = require_role('admin'); $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'retry') { [$ok, $m] = retry_notification((int)$_POST['id'], $u['user_id']); flash($m, $ok ? 'ok' : 'err'); }
    elseif (($_POST['action'] ?? '') === 'gateway') { $down = $_POST['state'] === 'down'; set_gateway_down($down); audit($u['user_id'], 'GATEWAY', $down ? 'Simulated outage ON' : 'Gateway back online'); flash($down ? 'SMS gateway outage simulated: new SMS notifications will fail.' : 'SMS gateway is online again.'); }
    redirect('admin/delivery.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}
$status = in_array($_GET['status'] ?? '', ['sent', 'failed', 'pending']) ? $_GET['status'] : '';
$cnt = []; foreach ($pdo->query('SELECT status, COUNT(*) c FROM notifications GROUP BY status') as $r) $cnt[$r['status']] = $r['c'];
$st = $pdo->prepare('SELECT n.*, t.transaction_ref, us.full_name FROM notifications n JOIN transactions t ON t.transaction_id=n.transaction_id JOIN accounts a ON a.account_id=t.account_id JOIN users us ON us.user_id=a.user_id' . ($status ? ' WHERE n.status=?' : '') . ' ORDER BY n.notification_id DESC LIMIT 100'); $st->execute($status ? [$status] : []);
page_start('Notification delivery monitor', 'admin', 'delivery'); ?>
<div class="card"><form method="post" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><?= csrf_field() ?><input type="hidden" name="action" value="gateway">
<b>SMS gateway (test facility):</b> <?= gateway_is_down() ? '<span class="badge b-failed">simulated outage</span>' : '<span class="badge b-sent">online</span>' ?>
<input type="hidden" name="state" value="<?= gateway_is_down() ? 'up' : 'down' ?>"><button class="btn sm sec"><?= gateway_is_down() ? 'Bring gateway back online' : 'Simulate outage' ?></button></form></div>
<div class="card"><div class="btns" style="margin:0 0 12px"><a class="btn sm <?= $status ? 'sec' : '' ?>" href="delivery.php">All</a>
<?php foreach (['sent', 'failed', 'pending'] as $s) echo '<a class="btn sm ' . ($status === $s ? '' : 'sec') . '" href="?status=' . $s . '">' . ucfirst($s) . ' (' . ($cnt[$s] ?? 0) . ')</a>'; ?></div>
<div class="tw"><table><tr><th>#</th><th>Customer</th><th>Reference</th><th>Message</th><th>Channel</th><th>Status</th><th>Retries</th><th></th></tr>
<?php foreach ($st as $n): ?><tr><td><?= $n['notification_id'] ?></td><td><?= e($n['full_name']) ?></td><td class="small"><?= e($n['transaction_ref']) ?></td><td class="small"><?= e($n['message']) ?></td><td><?= e($n['channel']) ?></td><td><?= badge($n['status']) ?></td><td><?= $n['retry_count'] ?></td>
<td><?php if ($n['status'] !== 'sent'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="retry"><input type="hidden" name="id" value="<?= $n['notification_id'] ?>"><button class="btn sm">Retry</button></form><?php else: echo '<span class="muted small">-</span>'; endif; ?></td></tr><?php endforeach; ?></table></div></div>
<?php page_end('admin');

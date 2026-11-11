<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin'); $pdo = db();
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01'); $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
$rep = $pdo->prepare("SELECT type, direction, COUNT(*) n, SUM(amount) total FROM transactions WHERE created_at BETWEEN ? AND ? GROUP BY type, direction ORDER BY type"); $rep->execute(["$from 00:00:00", "$to 23:59:59"]); $rows = $rep->fetchAll();
$ns = $pdo->prepare("SELECT n.status, COUNT(*) c FROM notifications n JOIN transactions t ON t.transaction_id=n.transaction_id WHERE t.created_at BETWEEN ? AND ? GROUP BY n.status"); $ns->execute(["$from 00:00:00", "$to 23:59:59"]); $nst = $ns->fetchAll(PDO::FETCH_KEY_PAIR);
if (isset($_GET['export'])) {
    audit($u['user_id'], 'REPORT_EXPORT', "$from to $to");
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="report_' . $from . '_' . $to . '.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['Type', 'Direction', 'Count', 'Total KES']); foreach ($rows as $r) fputcsv($o, [$r['type'], $r['direction'], $r['n'], $r['total']]);
    fputcsv($o, []); foreach ($nst as $k => $v) fputcsv($o, ["Notifications $k", $v]); exit;
}
$logs = $pdo->query('SELECT l.*, us.username FROM audit_logs l LEFT JOIN users us ON us.user_id=l.user_id ORDER BY l.log_id DESC LIMIT 60')->fetchAll();
page_start('Reports and audit log', 'admin', 'reports'); ?>
<div class="card"><form class="filters"><div><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div><div><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div><div><button class="btn">Generate report</button></div><div><button class="btn sec" name="export" value="1">Export CSV</button></div></form>
<div class="two"><div class="tw"><table><tr><th>Type</th><th>Direction</th><th class="right">Count</th><th class="right">Total (KES)</th></tr><?php foreach ($rows as $r) echo '<tr><td>' . e(ucfirst($r['type'])) . '</td><td>' . e($r['direction']) . '</td><td class="right">' . $r['n'] . '</td><td class="right">' . e(number_format($r['total'], 2)) . '</td></tr>'; if (!$rows) echo '<tr><td colspan="4" class="muted">No transactions in this period.</td></tr>'; ?></table></div>
<div class="tw"><table><tr><th>Notification status</th><th class="right">Count</th></tr><?php foreach (['sent', 'failed', 'pending'] as $s) echo '<tr><td>' . badge($s) . '</td><td class="right">' . ($nst[$s] ?? 0) . '</td></tr>'; ?></table></div></div></div>
<div class="card"><h2>Audit log (latest 60)</h2><div class="tw"><table><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr><?php foreach ($logs as $l) echo '<tr><td class="small">' . e($l['created_at']) . '</td><td>' . e($l['username'] ?? '-') . '</td><td>' . e($l['action']) . '</td><td class="small">' . e($l['details']) . '</td></tr>'; ?></table></div></div>
<?php page_end('admin');

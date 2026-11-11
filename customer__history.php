<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('customer'); $pdo = db();
$a = $pdo->prepare('SELECT account_id FROM accounts WHERE user_id=?'); $a->execute([$u['user_id']]); $aid = (int)$a->fetchColumn();
$from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? ''; $type = $_GET['type'] ?? '';
$w = 'account_id=?'; $p = [$aid];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $w .= ' AND created_at>=?'; $p[] = "$from 00:00:00"; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $w .= ' AND created_at<=?'; $p[] = "$to 23:59:59"; }
if (in_array($type, ['deposit', 'withdrawal', 'transfer'])) { $w .= ' AND type=?'; $p[] = $type; }
$per = 10; $page = max(1, (int)($_GET['p'] ?? 1));
$c = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE $w"); $c->execute($p); $total = (int)$c->fetchColumn(); $pages = max(1, (int)ceil($total / $per));
$st = $pdo->prepare("SELECT * FROM transactions WHERE $w ORDER BY transaction_id DESC LIMIT $per OFFSET " . (($page - 1) * $per)); $st->execute($p);
page_start('Transaction history', 'customer', 'history'); ?>
<div class="card"><form class="filters"><div><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div><div><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
<div><label>Type</label><select name="type"><option value="">All</option><?php foreach (['deposit','withdrawal','transfer'] as $t) echo '<option value="' . $t . '"' . ($t === $type ? ' selected' : '') . '>' . ucfirst($t) . '</option>'; ?></select></div><div><button class="btn">Filter</button></div></form>
<div class="tw"><table><tr><th>Date &amp; time</th><th>Reference</th><th>Type</th><th class="right">Amount</th><th class="right">Balance after</th></tr>
<?php foreach ($st as $t): ?><tr><td><?= e($t['created_at']) ?></td><td class="small"><?= e($t['transaction_ref']) ?></td><td><?= e(ucfirst($t['type'])) ?> <?= badge($t['direction']) ?></td><td class="right"><?= e(number_format($t['amount'], 2)) ?></td><td class="right"><?= e(number_format($t['balance_after'], 2)) ?></td></tr>
<?php endforeach; if (!$total) echo '<tr><td colspan="5" class="muted">No transactions match.</td></tr>'; ?></table></div>
<?= pager($page, $pages, ['from' => $from, 'to' => $to, 'type' => $type]) ?></div>
<?php page_end('customer');

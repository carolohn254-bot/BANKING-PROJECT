<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin'); $pdo = db();
$from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? ''; $acc = trim($_GET['account'] ?? ''); $ref = trim($_GET['ref'] ?? '');
$w = '1=1'; $p = [];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $w .= ' AND t.created_at>=?'; $p[] = "$from 00:00:00"; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $w .= ' AND t.created_at<=?'; $p[] = "$to 23:59:59"; }
if ($acc !== '') { $w .= ' AND a.account_number=?'; $p[] = $acc; }
if ($ref !== '') { $w .= ' AND t.transaction_ref LIKE ?'; $p[] = "%$ref%"; }
$per = 15; $page = max(1, (int)($_GET['p'] ?? 1));
$c = $pdo->prepare("SELECT COUNT(*) FROM transactions t JOIN accounts a ON a.account_id=t.account_id WHERE $w"); $c->execute($p); $pages = max(1, (int)ceil($c->fetchColumn() / $per));
$st = $pdo->prepare("SELECT t.*, a.account_number, n.status AS nstatus FROM transactions t JOIN accounts a ON a.account_id=t.account_id LEFT JOIN notifications n ON n.transaction_id=t.transaction_id WHERE $w ORDER BY t.transaction_id DESC LIMIT $per OFFSET " . (($page - 1) * $per)); $st->execute($p);
page_start('Monitor transactions', 'admin', 'transactions'); ?>
<div class="card"><form class="filters"><div><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div><div><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div><div><label>Account</label><input name="account" value="<?= e($acc) ?>"></div><div><label>Reference</label><input name="ref" value="<?= e($ref) ?>"></div><div><button class="btn">Filter</button></div></form>
<div class="tw"><table><tr><th>Date</th><th>Reference</th><th>Account</th><th>Type</th><th class="right">Amount</th><th class="right">Balance after</th><th>Notification</th></tr>
<?php foreach ($st as $t): ?><tr><td><?= e($t['created_at']) ?></td><td class="small"><?= e($t['transaction_ref']) ?></td><td><?= e($t['account_number']) ?></td><td><?= e(ucfirst($t['type'])) ?> <?= badge($t['direction']) ?></td><td class="right"><?= e(number_format($t['amount'], 2)) ?></td><td class="right"><?= e(number_format($t['balance_after'], 2)) ?></td><td><?= $t['nstatus'] ? badge($t['nstatus']) : '<span class="muted">none</span>' ?></td></tr><?php endforeach; ?></table></div>
<?= pager($page, $pages, ['from' => $from, 'to' => $to, 'account' => $acc, 'ref' => $ref]) ?></div>
<?php page_end('admin');

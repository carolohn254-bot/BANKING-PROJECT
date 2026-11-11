<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('customer'); $pdo = db();
$tx = $pdo->prepare('SELECT t.transaction_id, t.transaction_ref, t.type, t.amount, t.created_at FROM transactions t JOIN accounts a ON a.account_id=t.account_id WHERE a.user_id=? ORDER BY t.transaction_id DESC LIMIT 30'); $tx->execute([$u['user_id']]); $txs = $tx->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $tid = (int)($_POST['transaction_id'] ?? 0); $issue = $_POST['issue_type'] ?? ''; $d = trim($_POST['description'] ?? '');
    $mine = array_column($txs, 'transaction_id');
    if (!in_array($issue, ['missing', 'delayed', 'duplicate', 'incorrect'])) flash('Choose the type of problem.', 'err');
    elseif ($tid && !in_array($tid, array_map('intval', $mine))) flash('Choose one of your own transactions.', 'err');
    elseif (mb_strlen($d) < 5 || mb_strlen($d) > 1000) flash('Describe the problem (5 to 1000 characters).', 'err');
    else { $pdo->prepare("INSERT INTO complaints (user_id, transaction_id, issue_type, description, status, created_at) VALUES (?,?,?,?, 'open', ?)")->execute([$u['user_id'], $tid ?: null, $issue, $d, now()]); audit($u['user_id'], 'COMPLAINT', "Issue: $issue"); flash('Your complaint has been sent to the bank. We will respond soon.'); }
    redirect('customer/complaint.php');
}
$my = $pdo->prepare('SELECT * FROM complaints WHERE user_id=? ORDER BY complaint_id DESC'); $my->execute([$u['user_id']]);
page_start('Report a notification problem', 'customer', 'complaint'); ?>
<div class="two"><div class="card"><form method="post"><?= csrf_field() ?>
<label>Transaction concerned</label><select name="transaction_id"><option value="0">- not sure / none -</option><?php foreach ($txs as $t) echo '<option value="' . $t['transaction_id'] . '"' . (($_GET['tx'] ?? '') == $t['transaction_id'] ? ' selected' : '') . '>' . e($t['transaction_ref'] . ' - ' . ucfirst($t['type']) . ' ' . number_format($t['amount'], 2)) . '</option>'; ?></select>
<label>Type of problem</label><select name="issue_type"><option value="missing">Missing notification</option><option value="delayed">Delayed notification</option><option value="duplicate">Duplicate notification</option><option value="incorrect">Incorrect balance</option></select>
<label>Describe the problem</label><textarea name="description" rows="4" required maxlength="1000"></textarea>
<div class="btns"><button class="btn">Submit complaint</button></div></form></div>
<div class="card"><h2>My complaints</h2><?php $any = false; foreach ($my as $c): $any = true; ?><div style="border-bottom:1px solid var(--line);padding:8px 0"><b><?= e(ucfirst($c['issue_type'])) ?></b> <?= badge($c['status']) ?><br><span class="small"><?= e($c['description']) ?></span>
<?php if ($c['admin_response']): ?><div class="sms small" style="margin-top:6px"><b>Bank response:</b> <?= e($c['admin_response']) ?></div><?php endif; ?></div><?php endforeach; if (!$any) echo '<p class="muted">None yet.</p>'; ?></div></div>
<?php page_end('customer');

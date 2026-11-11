<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin'); $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)$_POST['id']; $s = $_POST['status'] ?? ''; $r = trim($_POST['response'] ?? '');
    if (!in_array($s, ['open', 'in_progress', 'resolved'])) flash('Invalid status.', 'err');
    elseif ($s === 'resolved' && $r === '') flash('Write a response before resolving.', 'err');
    else { $pdo->prepare('UPDATE complaints SET status=?, admin_response=? WHERE complaint_id=?')->execute([$s, $r !== '' ? $r : null, $id]); audit($u['user_id'], 'COMPLAINT_UPDATED', "Complaint #$id -> $s"); flash('Complaint updated.'); }
    redirect('admin/complaints.php?id=' . $id);
}
$all = $pdo->query('SELECT c.*, us.full_name FROM complaints c JOIN users us ON us.user_id=c.user_id ORDER BY c.complaint_id DESC')->fetchAll();
$sel = null; $id = (int)($_GET['id'] ?? ($all[0]['complaint_id'] ?? 0)); foreach ($all as $c) if ($c['complaint_id'] == $id) $sel = $c;
$tx = null; if ($sel && $sel['transaction_id']) { $s = $pdo->prepare('SELECT t.*, n.message, n.status ns FROM transactions t LEFT JOIN notifications n ON n.transaction_id=t.transaction_id WHERE t.transaction_id=?'); $s->execute([$sel['transaction_id']]); $tx = $s->fetch(); }
page_start('Notification complaints', 'admin', 'complaints'); ?>
<div class="two"><div class="card"><h2>Complaints</h2><?php foreach ($all as $c) echo '<p style="margin:0;padding:8px 0;border-bottom:1px solid var(--line)"><a href="?id=' . $c['complaint_id'] . '">#' . $c['complaint_id'] . ' ' . e($c['full_name']) . ' - ' . e($c['issue_type']) . '</a> ' . badge($c['status']) . '</p>'; if (!$all) echo '<p class="muted">No complaints.</p>'; ?></div>
<div class="card"><?php if ($sel): ?><h2>Complaint #<?= $sel['complaint_id'] ?> <?= badge($sel['status']) ?></h2><p class="small muted"><?= e($sel['full_name']) ?> &middot; <?= e($sel['created_at']) ?> &middot; <?= e(ucfirst($sel['issue_type'])) ?></p><p><?= e($sel['description']) ?></p>
<?php if ($tx): ?><div class="sms small"><b><?= e($tx['transaction_ref']) ?></b> (<?= e($tx['type']) ?>, <?= e(number_format($tx['amount'], 2)) ?>)<br>Notification: <?= $tx['ns'] ? badge($tx['ns']) . ' ' . e($tx['message']) : 'none recorded' ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $sel['complaint_id'] ?>"><label>Response to customer</label><textarea name="response" rows="3" maxlength="1000"><?= e($sel['admin_response']) ?></textarea>
<label>Status</label><select name="status"><?php foreach (['open', 'in_progress', 'resolved'] as $s) echo '<option value="' . $s . '"' . ($s === $sel['status'] ? ' selected' : '') . '>' . ucfirst(str_replace('_', ' ', $s)) . '</option>'; ?></select><div class="btns"><button class="btn">Save</button></div></form>
<?php else: echo '<p class="muted">Select a complaint.</p>'; endif; ?></div></div>
<?php page_end('admin');

<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('customer'); $pdo = db();
$a = $pdo->prepare('SELECT * FROM accounts WHERE user_id=?'); $a->execute([$u['user_id']]); $acc = $a->fetch();
$tx = $pdo->prepare('SELECT * FROM transactions WHERE account_id=? ORDER BY transaction_id DESC LIMIT 5'); $tx->execute([$acc['account_id']]);
$nt = $pdo->prepare('SELECT n.*, t.transaction_ref FROM notifications n JOIN transactions t ON t.transaction_id=n.transaction_id WHERE t.account_id=? ORDER BY n.notification_id DESC LIMIT 4'); $nt->execute([$acc['account_id']]);
page_start('Dashboard', 'customer', 'dashboard'); ?>
<div class="balance"><div class="l"><span class="live"></span>Current balance (updates live)</div><div class="v" id="bal"><?= e(money($acc['balance'])) ?></div><div class="a">Account <?= e(mask_account($acc['account_number'])) ?> &middot; <?= e($u['full_name']) ?></div></div>
<div class="btns" style="margin-bottom:18px"><a class="btn" href="transact.php?type=deposit">Deposit</a><a class="btn" href="transact.php?type=withdrawal">Withdraw</a><a class="btn" href="transact.php?type=transfer">Transfer</a></div>
<div class="two"><div class="card"><h2>Recent transactions</h2><div class="tw"><table><tr><th>Date</th><th>Type</th><th class="right">Amount</th><th class="right">Balance</th></tr>
<?php foreach ($tx as $t): ?><tr><td><?= e(date('d M H:i', strtotime($t['created_at']))) ?></td><td><?= e(ucfirst($t['type'])) ?> <?= badge($t['direction']) ?></td><td class="right"><?= e(number_format($t['amount'], 2)) ?></td><td class="right"><?= e(number_format($t['balance_after'], 2)) ?></td></tr>
<?php endforeach; if (!$tx->rowCount() && !isset($t)) echo '<tr><td colspan="4" class="muted">No transactions yet.</td></tr>'; ?></table></div></div>
<div class="card"><h2>Latest notifications</h2>
<?php $any = false; foreach ($nt as $n): $any = true; ?><div class="sms" style="margin-bottom:8px"><?= e($n['message']) ?><br><span class="small muted"><?= e($n['channel']) ?> &middot; <?= badge($n['status']) ?></span></div>
<?php endforeach; if (!$any) echo '<p class="muted">No notifications yet.</p>'; ?>
<a href="notifications.php" class="small">View all &rarr;</a></div></div>
<script>
// "Real-time": poll the balance every 4 seconds so the page reflects changes (e.g. an incoming transfer) without a refresh.
setInterval(function(){fetch('../api/balance.php',{credentials:'same-origin'}).then(r=>r.ok?r.json():null).then(d=>{if(d)document.getElementById('bal').textContent=d.balance}).catch(()=>{})},4000);
</script>
<?php page_end('customer');

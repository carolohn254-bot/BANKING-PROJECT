<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('customer');
$st = db()->prepare('SELECT n.*, t.transaction_ref FROM notifications n JOIN transactions t ON t.transaction_id=n.transaction_id JOIN accounts a ON a.account_id=t.account_id WHERE a.user_id=? ORDER BY n.notification_id DESC LIMIT 100'); $st->execute([$u['user_id']]);
page_start('Notification history', 'customer', 'notifications'); ?>
<div class="card"><div class="tw"><table><tr><th>Sent</th><th>Message</th><th>Channel</th><th>Status</th></tr>
<?php $any = false; foreach ($st as $n): $any = true; ?><tr><td class="small"><?= e($n['sent_at'] ?: '-') ?></td><td><?= e($n['message']) ?></td><td><?= e($n['channel']) ?></td><td><?= badge($n['status']) ?></td></tr>
<?php endforeach; if (!$any) echo '<tr><td colspan="4" class="muted">No notifications yet.</td></tr>'; ?></table></div>
<p class="small">Missing, late, repeated or wrong message? <a class="btn sm sec" href="complaint.php">Report a problem</a></p></div>
<?php page_end('customer');

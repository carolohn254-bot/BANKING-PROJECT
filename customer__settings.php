<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('customer'); $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $pref = $_POST['pref'] ?? ''; $ph = trim($_POST['phone'] ?? '');
    if (!in_array($pref, ['sms', 'push', 'both'])) flash('Choose how you want to receive alerts.', 'err');
    elseif (!valid_phone($ph)) flash('Enter a valid Kenyan mobile number, e.g. 0712345678.', 'err');
    else { $pdo->prepare('UPDATE users SET notify_preference=?, phone_number=? WHERE user_id=?')->execute([$pref, normalise_phone($ph), $u['user_id']]); audit($u['user_id'], 'SETTINGS', "Notifications: $pref"); flash('Settings saved. They will be used for your next notification.'); }
    redirect('customer/settings.php');
}
$r = $pdo->prepare('SELECT * FROM users WHERE user_id=?'); $r->execute([$u['user_id']]); $me = $r->fetch();
page_start('Notification settings', 'customer', 'settings'); ?>
<div class="two"><div class="card"><form method="post"><?= csrf_field() ?>
<label>Receive alerts by</label><select name="pref"><?php foreach (['sms' => 'SMS', 'push' => 'Push notification', 'both' => 'SMS and push'] as $k => $v) echo '<option value="' . $k . '"' . ($k === $me['notify_preference'] ? ' selected' : '') . '>' . $v . '</option>'; ?></select>
<label>Phone number for SMS</label><input name="phone" value="<?= e($me['phone_number']) ?>" required>
<div class="btns"><button class="btn">Save settings</button></div></form></div>
<div class="card"><h2>Sample message</h2><div class="sms">Deposit of KES 5,000.00 to a/c ****0001 on 13/10/26 12:30. New balance KES 30,000.00. Ref TXN20261013123000AB12CD.</div><p class="small muted">This is what a notification looks like.</p></div></div>
<?php page_end('customer');

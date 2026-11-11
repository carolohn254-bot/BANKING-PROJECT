<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin'); $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $act = $_POST['action'] ?? '';
    if ($act === 'toggle') {
        $id = (int)$_POST['user_id']; $s = $pdo->prepare("SELECT status FROM users WHERE user_id=? AND role='customer'"); $s->execute([$id]); $cur = $s->fetchColumn();
        if ($cur) { $new = $cur === 'active' ? 'suspended' : 'active'; $pdo->prepare('UPDATE users SET status=? WHERE user_id=?')->execute([$new, $id]); audit($u['user_id'], 'CUSTOMER_' . strtoupper($new), "User #$id"); flash("Customer is now $new."); }
    } elseif ($act === 'add' || $act === 'edit') {
        $name = trim($_POST['full_name'] ?? ''); $ph = trim($_POST['phone'] ?? ''); $un = trim($_POST['username'] ?? ''); $pin = $_POST['pin'] ?? '';
        $err = null;
        if ($name === '' || mb_strlen($name) > 100) $err = 'Enter the full name.';
        elseif (!valid_phone($ph)) $err = 'Enter a valid Kenyan phone number.';
        elseif ($act === 'add' && (!preg_match('/^[a-z0-9_]{3,30}$/', $un) || !preg_match('/^\d{4,6}$/', $pin))) $err = 'Username: 3-30 lowercase letters/digits. PIN: 4-6 digits.';
        elseif ($act === 'edit' && $pin !== '' && !preg_match('/^\d{4,6}$/', $pin)) $err = 'PIN must be 4-6 digits.';
        if ($err) flash($err, 'err');
        elseif ($act === 'add') {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO users (username, full_name, phone_number, password_hash, role, notify_preference, status) VALUES (?,?,?,?, 'customer', 'sms', 'active')")->execute([$un, $name, normalise_phone($ph), password_hash($pin, PASSWORD_DEFAULT)]);
                $uid = (int)$pdo->lastInsertId(); $accno = '01' . str_pad((string)(10000000 + $uid), 8, '0', STR_PAD_LEFT);
                $pdo->prepare('INSERT INTO accounts (user_id, account_number, balance) VALUES (?,?,0)')->execute([$uid, $accno]); $pdo->commit();
                audit($u['user_id'], 'CUSTOMER_ADDED', "$un, account $accno"); flash("Customer added. Account number $accno.");
            } catch (PDOException $e) { $pdo->rollBack(); flash('That username already exists.', 'err'); }
        } else {
            $id = (int)$_POST['user_id'];
            $pdo->prepare("UPDATE users SET full_name=?, phone_number=? WHERE user_id=? AND role='customer'")->execute([$name, normalise_phone($ph), $id]);
            if ($pin !== '') $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=? AND role='customer'")->execute([password_hash($pin, PASSWORD_DEFAULT), $id]);
            audit($u['user_id'], 'CUSTOMER_EDITED', "User #$id"); flash('Customer updated.');
        }
    }
    redirect('admin/customers.php');
}
$search = trim($_GET['q'] ?? ''); $edit = (int)($_GET['edit'] ?? 0);
$st = $pdo->prepare("SELECT u.*, a.account_number, a.balance FROM users u LEFT JOIN accounts a ON a.user_id=u.user_id WHERE u.role='customer' AND (u.full_name LIKE ? OR u.username LIKE ? OR a.account_number LIKE ?) ORDER BY u.user_id"); $l = "%$search%"; $st->execute([$l, $l, $l]); $rows = $st->fetchAll();
$ed = null; foreach ($rows as $r) if ($r['user_id'] == $edit) $ed = $r;
page_start('Manage customers', 'admin', 'customers'); ?>
<div class="card"><form class="filters"><div style="flex:3"><label>Search</label><input name="q" value="<?= e($search) ?>" placeholder="Name, username or account number"></div><div><button class="btn">Search</button></div></form>
<div class="tw"><table><tr><th>Name</th><th>Username</th><th>Account</th><th class="right">Balance</th><th>Phone</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['full_name']) ?></td><td><?= e($r['username']) ?></td><td><?= e($r['account_number']) ?></td><td class="right"><?= e(number_format($r['balance'], 2)) ?></td><td><?= e($r['phone_number']) ?></td><td><?= badge($r['status']) ?></td>
<td><a class="btn sm sec" href="?edit=<?= $r['user_id'] ?>">Edit</a> <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= $r['user_id'] ?>"><button class="btn sm <?= $r['status'] === 'active' ? 'danger' : '' ?>"><?= $r['status'] === 'active' ? 'Suspend' : 'Activate' ?></button></form></td></tr><?php endforeach; ?></table></div></div>
<div class="card" style="max-width:560px"><h2><?= $ed ? 'Edit customer' : 'Add customer' ?></h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="<?= $ed ? 'edit' : 'add' ?>"><?php if ($ed) echo '<input type="hidden" name="user_id" value="' . $ed['user_id'] . '">'; ?>
<label>Full name</label><input name="full_name" required value="<?= e($ed['full_name'] ?? '') ?>"><label>Phone</label><input name="phone" required value="<?= e($ed['phone_number'] ?? '') ?>" placeholder="0712345678">
<?php if (!$ed): ?><label>Username</label><input name="username" required placeholder="lowercase letters/digits"><?php endif; ?>
<label><?= $ed ? 'New PIN (leave blank to keep)' : 'PIN (4-6 digits)' ?></label><input name="pin" type="password" <?= $ed ? '' : 'required' ?>>
<div class="btns"><button class="btn"><?= $ed ? 'Save changes' : 'Add customer' ?></button><?php if ($ed) echo '<a class="btn sec" href="customers.php">Cancel</a>'; ?></div></form></div>
<?php page_end('admin');

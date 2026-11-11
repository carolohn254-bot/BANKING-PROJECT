<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/layout.php'; require_once __DIR__ . '/../includes/transaction.php';
$u = require_role('customer');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$ok, $m] = process_transaction($u['user_id'], $_POST['type'] ?? '', $_POST['amount'] ?? '', $_POST['ref'] ?? '', $_POST['pin'] ?? '', $_POST['to_account'] ?? '');
    flash($m, $ok ? 'ok' : 'err');
    redirect($ok ? 'customer/dashboard.php' : 'customer/transact.php');   // redirect so a page refresh cannot re-submit
}
$type = in_array($_GET['type'] ?? '', ['deposit', 'withdrawal', 'transfer']) ? $_GET['type'] : 'deposit';
$ref = new_transaction_ref();
page_start('Make a transaction', 'customer', 'transact'); ?>
<div class="card" style="max-width:520px"><form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($ref) ?>">
<label>Transaction type</label><select name="type" id="type"><?php foreach (['deposit','withdrawal','transfer'] as $t) echo '<option value="' . $t . '"' . ($t === $type ? ' selected' : '') . '>' . ucfirst($t) . '</option>'; ?></select>
<div id="toBox"><label>Receiving account number</label><input name="to_account" inputmode="numeric" maxlength="20" placeholder="e.g. 0100000002"></div>
<label>Amount (KES)</label><input name="amount" required inputmode="decimal" placeholder="0.00">
<label>Confirm with your PIN</label><input name="pin" type="password" required>
<p class="small muted">After the transaction your new balance is sent to you as a notification.</p>
<div class="btns"><button class="btn" id="go">Confirm transaction</button><a class="btn sec" href="dashboard.php">Cancel</a></div></form></div>
<script>
var t=document.getElementById('type'),b=document.getElementById('toBox');function s(){b.style.display=t.value==='transfer'?'block':'none'}t.onchange=s;s();
document.querySelector('form').addEventListener('submit',function(){var g=document.getElementById('go');setTimeout(function(){g.disabled=true;g.textContent='Processing...'},0)});
</script>
<?php page_end('customer');

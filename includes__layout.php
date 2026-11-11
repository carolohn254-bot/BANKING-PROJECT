<?php
function page_start(string $title, string $role, string $active): void {
    $u = current_user(); $b = base_url();
    $cust = ['dashboard'=>'Dashboard','transact'=>'Make a transaction','history'=>'Transactions','notifications'=>'Notifications','settings'=>'Settings','complaint'=>'Report a problem'];
    $adm  = ['dashboard'=>'Dashboard','customers'=>'Customers','transactions'=>'Transactions','delivery'=>'Notification delivery','complaints'=>'Complaints','reports'=>'Reports & audit log'];
    $links = $role === 'admin' ? $adm : $cust; $dir = $role === 'admin' ? 'admin' : 'customer';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . ' - Mobile Banking Notifications</title><link rel="stylesheet" href="' . $b . '/assets/style.css"></head><body>';
    echo '<div class="topbar"><div class="brand">&#9679; Real-Time Mobile Banking</div>';
    if ($role === 'customer') {
        echo '<nav>'; foreach ($links as $k => $v) echo '<a class="' . ($k === $active ? 'on' : '') . '" href="' . "$b/$dir/$k.php" . '">' . e($v) . '</a>'; echo '</nav>';
    } else echo '<nav></nav>';
    echo '<div class="who">' . e($u['full_name']) . ' (' . e($u['role']) . ') <a href="' . $b . '/logout.php">Log out</a></div></div>';
    if ($role === 'admin') { echo '<div class="shell"><div class="side">'; foreach ($links as $k => $v) echo '<a class="' . ($k === $active ? 'on' : '') . '" href="' . "$b/$dir/$k.php" . '">' . e($v) . '</a>'; echo '</div>'; }
    echo '<main><h1>' . e($title) . '</h1>';
    if ($f = flash()) echo '<div class="msg ' . e($f[1]) . '">' . e($f[0]) . '</div>';
}
function page_end(string $role): void { echo '</main>' . ($role === 'admin' ? '</div>' : '') . '</body></html>'; }
function badge(string $s): string { return '<span class="badge b-' . e($s) . '">' . e(str_replace('_', ' ', $s)) . '</span>'; }
function pager(int $page, int $pages, array $q): string {
    if ($pages <= 1) return ''; $h = '<div class="pager">';
    for ($i = 1; $i <= $pages; $i++) { $q['p'] = $i; $h .= $i === $page ? "<span class=\"on\">$i</span>" : '<a href="?' . e(http_build_query($q)) . "\">$i</a>"; }
    return $h . '</div>';
}

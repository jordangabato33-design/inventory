<?php
// includes/header.php — Shared HTML header + sidebar
require_once __DIR__ . '/auth.php';
requireLogin();

$user          = currentUser();
$csrf          = generateCsrfToken();
$folder        = basename(dirname($_SERVER['PHP_SELF']));

require_once __DIR__ . '/../config/env.php';
$pusherKey     = $_ENV['PUSHER_APP_KEY']     ?? '';
$pusherCluster = $_ENV['PUSHER_APP_CLUSTER'] ?? 'ap1';

function navLink(string $href, string $icon, string $label, string $folder, string $match): string {
    $active = str_contains($folder, $match) ? 'active' : '';
    return "<a href='$href' class='nav-item $active'><span class='nav-icon'>$icon</span>$label</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvenTrack | Computer Hardware Inventory</title>
    <link rel="stylesheet" href="/inventory/assets/css/style.css">
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>
<body>
<div class="layout">

<!-- =================== SIDEBAR =================== -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">IT</div>
    <div>
      <div class="brand-name">InvenTrack</div>
      <div class="brand-sub">Hardware Inventory</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group-label">Main</div>
    <?= navLink('/inventory/index.php', '🏠', 'Dashboard', $folder, 'inventory') ?>

    <div class="nav-group-label">Inventory</div>
    <?= navLink('/inventory/modules/products/index.php',     '🖥️', 'Products',     $folder, 'products') ?>
    <?= navLink('/inventory/modules/stock-in/index.php',     '📥', 'Stock-In',     $folder, 'stock-in') ?>
    <?= navLink('/inventory/modules/stock-out/index.php',    '📤', 'Stock-Out',    $folder, 'stock-out') ?>
    <?= navLink('/inventory/modules/transactions/index.php', '📋', 'Transactions', $folder, 'transactions') ?>

    <?php if (isAdmin()): ?>
    <div class="nav-group-label">Admin</div>
    <?= navLink('/inventory/modules/users/index.php', '👥', 'Users', $folder, 'users') ?>
    <?php endif; ?>

    <div class="nav-group-label">Account</div>
    <?= navLink('/inventory/modules/auth/profile.php', '⚙️', 'My Profile', $folder, 'auth') ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
      <div>
        <div class="user-name"><?= safe($user['name']) ?></div>
        <span class="user-role"><?= safe($user['role']) ?></span>
      </div>
    </div>
    <a href="/inventory/modules/auth/logout.php" class="btn-logout">🚪 Sign Out</a>
  </div>
</aside>

<!-- =================== MAIN =================== -->
<main class="main-content">
  <div class="topbar">
    <div>
      <div class="topbar-title" id="page-topbar-title">Dashboard</div>
      <div class="topbar-sub">Computer Hardware &amp; Parts Inventory System</div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <span class="live-pill" id="live-status">
        <span class="live-dot"></span> Connecting...
      </span>
    </div>
  </div>

  <div class="page-body">
    <?= flashMessage() ?>

<!-- ========= PUSHER REAL-TIME INITIALIZATION ========= -->
<!-- Keys passed from PHP .env — NEVER hardcoded in JS -->
<script>
const PUSHER_KEY     = '<?= safe($pusherKey) ?>';
const PUSHER_CLUSTER = '<?= safe($pusherCluster) ?>';

const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
const channel = pusher.subscribe('inventory-channel');

// Connection status
pusher.connection.bind('connected', () => {
    const el = document.getElementById('live-status');
    if (el) el.innerHTML = '<span class="live-dot"></span> Live';
});

pusher.connection.bind('disconnected', () => {
    const el = document.getElementById('live-status');
    if (el) el.innerHTML = '<span class="live-dot" style="background:var(--red)"></span> Offline';
});

// Toast helper
function showToast(msg, type = 'success') {
    let c = document.getElementById('toast-container');
    if (!c) {
        c = document.createElement('div');
        c.id = 'toast-container';
        document.body.appendChild(c);
    }
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => {
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 400);
    }, 4000);
}

// Real-time stock update handler
channel.bind('stock-updated', function(data) {

    // 1. Update quantity on products page
    const qtyEl = document.getElementById('qty-' + data.product_id);
    if (qtyEl) {
        qtyEl.textContent = data.new_quantity;
        const row = qtyEl.closest('tr');
        if (row) {
            row.classList.remove('row-highlight');
            void row.offsetWidth;
            row.classList.add('row-highlight');
        }
        const badge = document.getElementById('badge-' + data.product_id);
        if (badge) {
            if (data.new_quantity == 0) {
                badge.className = 'badge badge-empty';
                badge.textContent = 'Out of Stock';
            } else if (data.is_low) {
                badge.className = 'badge badge-low';
                badge.textContent = 'Low Stock';
            } else {
                badge.className = 'badge badge-ok';
                badge.textContent = 'In Stock';
            }
        }
    }

    // 2. Reload dashboard stats via AJAX — always accurate
        fetch('/inventory/modules/dashboard/stats.php')
    .then(r => r.json())
    .then(stats => {
        const inEl  = document.getElementById('dash-in-today');
        const outEl = document.getElementById('dash-out-today');
        if (inEl  && stats.stock_in_today  !== undefined) inEl.textContent  = stats.stock_in_today;
        if (outEl && stats.stock_out_today !== undefined) outEl.textContent = stats.stock_out_today;
    })
    .catch(() => {}); // silent fail

    // 3. Prepend row to recent transactions table
    const tbody = document.getElementById('recent-tbody');
    if (tbody) {
        const tr = document.createElement('tr');
        tr.classList.add('row-highlight');
        tr.innerHTML = `
            <td><span class="code-chip">${data.product_code || ''}</span></td>
            <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;">${data.product}</td>
            <td><span class="badge ${data.type === 'Stock-In' ? 'badge-in' : 'badge-out'}">${data.type}</span></td>
            <td style="font-weight:700;color:${data.type === 'Stock-In' ? 'var(--green)' : 'var(--red)'}">
                ${data.type === 'Stock-In' ? '+' : '-'}${data.quantity}
            </td>
            <td style="color:var(--text-sub);font-size:12px;">${data.by_user}</td>
            <td style="color:var(--text-muted);font-size:11px;">Just now</td>
        `;
        tbody.insertBefore(tr, tbody.firstChild);
        if (tbody.rows.length > 8) tbody.deleteRow(tbody.rows.length - 1);
    }

    // 4. Toast notification
    showToast(
        `${data.type}: ${data.product} — ${data.type === 'Stock-In' ? '+' : '-'}${data.quantity} unit${data.quantity > 1 ? 's' : ''}`,
        data.type === 'Stock-In' ? 'success' : 'error'
    );

}); // end channel.bind
</script>
<!-- END PUSHER INIT -->
<?php
// modules/transactions/index.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db     = getDB();
$filter = $_GET['type']   ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT * FROM (
        SELECT 'Stock-In' AS type, si.id, p.product_code, p.name AS product,
               p.brand, si.quantity, si.supplier AS reference,
               si.remarks, si.date_received AS txn_date,
               u.name AS by_user, si.created_at
        FROM stock_in si
        JOIN products p ON si.product_id = p.id
        JOIN users u ON si.created_by = u.id
        UNION ALL
        SELECT 'Stock-Out', so.id, p.product_code, p.name,
               p.brand, so.quantity, so.reason,
               so.remarks, so.date_released,
               u.name, so.created_at
        FROM stock_out so
        JOIN products p ON so.product_id = p.id
        JOIN users u ON so.created_by = u.id
    ) AS t
    " . ($filter === 'in' ? "WHERE type='Stock-In'" : ($filter === 'out' ? "WHERE type='Stock-Out'" : "")) . "
    ORDER BY created_at DESC LIMIT 200
";

$rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

// PHP-side search filter
if ($search) {
    $rows = array_filter($rows, fn($r) =>
        stripos($r['product'], $search) !== false ||
        stripos($r['product_code'], $search) !== false ||
        stripos($r['brand'], $search) !== false ||
        stripos($r['by_user'], $search) !== false
    );
}

// ✅ Fix: Convert created_at from UTC to Philippine Time (UTC+8) for display
function formatPHTime(string $utcDatetime): string {
    $dt = new DateTime($utcDatetime, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
    return $dt->format('M d, Y h:i A');
}

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Transactions';</script>

<div class="page-header">
  <div><h1 class="page-title">📋 Transaction History</h1><p class="page-sub">All stock movements log</p></div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-body" style="padding:14px 20px;">
    <form method="GET" class="search-row">
      <div style="display:flex;gap:6px;">
        <a href="?type=all"  class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-ghost' ?>">All</a>
        <a href="?type=in"   class="btn btn-sm <?= $filter==='in' ?'btn-success':'btn-ghost' ?>">Stock-In</a>
        <a href="?type=out"  class="btn btn-sm <?= $filter==='out'?'btn-danger' :'btn-ghost' ?>">Stock-Out</a>
      </div>
      <input type="hidden" name="type" value="<?= safe($filter) ?>">
      <div class="search-input-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" placeholder="Search product, brand, user..." value="<?= safe($search) ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <?php if ($search): ?>
        <a href="?type=<?= safe($filter) ?>" class="btn btn-ghost btn-sm">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Records</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($rows) ?> entries</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Type</th><th>Code</th><th>Product</th><th>Brand</th><th>Qty</th><th>Reference</th><th>Date</th><th>Recorded At</th><th>By</th></tr>
      </thead>
      <tbody>
        <?php if ($rows): ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><span class="badge <?= $r['type']==='Stock-In'?'badge-in':'badge-out' ?>"><?= safe($r['type']) ?></span></td>
            <td><span class="code-chip"><?= safe($r['product_code']) ?></span></td>
            <td style="font-weight:600;"><?= safe($r['product']) ?></td>
            <td style="color:var(--text-sub);"><?= safe($r['brand']) ?></td>
            <td style="font-weight:700;color:<?= $r['type']==='Stock-In'?'var(--green)':'var(--red)' ?>">
              <?= $r['type']==='Stock-In'?'+':'-' ?><?= (int)$r['quantity'] ?>
            </td>
            <td style="color:var(--text-sub);"><?= safe($r['reference'] ?? '—') ?></td>
            <td><?= safe($r['txn_date']) ?></td>
            <td style="color:var(--text-muted);font-size:12px;"><?= formatPHTime($r['created_at']) ?></td>
            <td style="color:var(--text-muted);font-size:12px;"><?= safe($r['by_user']) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">📋</div><p>No transactions found.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
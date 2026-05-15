<?php
// index.php — Dashboard
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/env.php';
requireLogin();

$db   = getDB();
$user = currentUser();

// Dashboard summary
$summaryRow = $db->query("
    SELECT
        (SELECT COUNT(*) FROM products) AS total_products,
        (SELECT COALESCE(SUM(quantity),0) FROM stock_in  WHERE DATE(date_received)=CURDATE()) AS stock_in_today,
        (SELECT COALESCE(SUM(quantity),0) FROM stock_out WHERE DATE(date_released)=CURDATE()) AS stock_out_today,
        (SELECT COUNT(*) FROM products WHERE quantity <= low_stock_threshold) AS low_stock_count,
        (SELECT COUNT(*) FROM products WHERE quantity = 0) AS out_of_stock_count
")->fetch_assoc();

// Recent 8 transactions
$recent = $db->query("
    SELECT 'Stock-In' AS type, si.quantity, p.name AS product, p.product_code, si.created_at, u.name AS by_user
    FROM stock_in si JOIN products p ON si.product_id=p.id JOIN users u ON si.created_by=u.id
    UNION ALL
    SELECT 'Stock-Out', so.quantity, p.name, p.product_code, so.created_at, u.name
    FROM stock_out so JOIN products p ON so.product_id=p.id JOIN users u ON so.created_by=u.id
    ORDER BY created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Low stock
$lowStock = $db->query("
    SELECT id, product_code, name, brand, quantity, low_stock_threshold
    FROM products WHERE quantity <= low_stock_threshold ORDER BY quantity ASC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Category breakdown
$catStats = $db->query("
    SELECT category, COUNT(*) AS total, SUM(quantity) AS total_stock
    FROM products GROUP BY category ORDER BY total_stock DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Convert created_at UTC → Philippine Time (UTC+8)
function formatPHTime(string $utcDatetime): string {
    $dt = new DateTime($utcDatetime, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
    return $dt->format('M d h:i A');
}

include __DIR__ . '/includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Dashboard';</script>

<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-sub">Welcome back, <strong><?= safe($user['name']) ?></strong> — Hardware Inventory Overview</p>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="/inventory/modules/stock-in/create.php"  class="btn btn-success">📥 Stock-In</a>
    <a href="/inventory/modules/stock-out/create.php" class="btn btn-danger">📤 Stock-Out</a>
    <?php if (isAdmin()): ?>
    <a href="/inventory/modules/products/create.php"  class="btn btn-primary">+ Product</a>
    <?php endif; ?>
  </div>
</div>

<!-- STAT CARDS -->
<div class="stat-grid">
  <div class="stat-card blue">
    <div class="stat-icon">🖥️</div>
    <div class="stat-label">Total Products</div>
    <div class="stat-value"><?= (int)($summaryRow['total_products'] ?? 0) ?></div>
    <div class="stat-sub">Items in catalog</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon">📥</div>
    <div class="stat-label">Stock-In Today</div>
    <div class="stat-value" id="dash-in-today"><?= (int)($summaryRow['stock_in_today'] ?? 0) ?></div>
    <div class="stat-sub">Units received</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon">📤</div>
    <div class="stat-label">Stock-Out Today</div>
    <div class="stat-value" id="dash-out-today"><?= (int)($summaryRow['stock_out_today'] ?? 0) ?></div>
    <div class="stat-sub">Units released</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon">⚠️</div>
    <div class="stat-label">Low Stock</div>
    <div class="stat-value"><?= (int)($summaryRow['low_stock_count'] ?? 0) ?></div>
    <div class="stat-sub">Need restocking</div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon">🚫</div>
    <div class="stat-label">Out of Stock</div>
    <div class="stat-value"><?= (int)($summaryRow['out_of_stock_count'] ?? 0) ?></div>
    <div class="stat-sub">Zero quantity</div>
  </div>
</div>

<!-- TWO COLUMNS -->
<div class="two-col">

  <!-- Recent Transactions -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📋 Recent Transactions</span>
      <a href="/inventory/modules/transactions/index.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Code</th><th>Product</th><th>Type</th><th>Qty</th><th>By</th><th>Time</th></tr>
        </thead>
        <tbody id="recent-tbody">
          <?php if ($recent): ?>
            <?php foreach ($recent as $r): ?>
            <tr>
              <td><span class="code-chip"><?= safe($r['product_code']) ?></span></td>
              <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;"><?= safe($r['product']) ?></td>
              <td><span class="badge <?= $r['type']==='Stock-In'?'badge-in':'badge-out' ?>"><?= safe($r['type']) ?></span></td>
              <td style="font-weight:700;color:<?= $r['type']==='Stock-In'?'var(--green)':'var(--red)' ?>">
                <?= $r['type']==='Stock-In'?'+':'-' ?><?= (int)$r['quantity'] ?>
              </td>
              <td style="color:var(--text-sub);font-size:12px;"><?= safe($r['by_user']) ?></td>
              <td style="color:var(--text-muted);font-size:11px;"><?= formatPHTime($r['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6">
              <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <p>No transactions yet.</p>
              </div>
            </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Low Stock Alerts -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">⚠️ Low Stock Alerts</span>
      <span class="badge badge-low"><?= count($lowStock) ?> items</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Code</th><th>Product</th><th>Brand</th><th>Stock</th></tr>
        </thead>
        <tbody>
          <?php if ($lowStock): ?>
            <?php foreach ($lowStock as $ls): ?>
            <tr>
              <td><span class="code-chip"><?= safe($ls['product_code']) ?></span></td>
              <td style="font-size:12px;"><?= safe($ls['name']) ?></td>
              <td style="color:var(--text-muted);font-size:12px;"><?= safe($ls['brand']) ?></td>
              <td>
                <?php if ((int)$ls['quantity'] === 0): ?>
                  <span class="badge badge-empty">Out</span>
                <?php else: ?>
                  <span style="color:var(--orange);font-weight:700;"><?= (int)$ls['quantity'] ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4">
              <div class="empty-state">
                <div class="empty-state-icon">✅</div>
                <p>All stocks sufficient!</p>
              </div>
            </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- CATEGORY BREAKDOWN -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📊 Stock by Category</span>
    <a href="/inventory/modules/products/index.php" class="btn btn-ghost btn-sm">View All Products</a>
  </div>
  <div class="card-body">
    <?php if ($catStats):
      $maxStock = max(array_column($catStats, 'total_stock') ?: [1]);
      foreach ($catStats as $cat):
        $pct   = $maxStock > 0 ? round(($cat['total_stock'] / $maxStock) * 100) : 0;
        $color = $pct > 60 ? 'var(--green)' : ($pct > 25 ? 'var(--orange)' : 'var(--red)');
    ?>
      <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
          <span style="font-size:13px;font-weight:600;"><?= safe($cat['category']) ?></span>
          <span style="font-size:12px;color:var(--text-muted);">
            <?= (int)$cat['total'] ?> product<?= $cat['total']>1?'s':'' ?> &nbsp;·&nbsp;
            <strong style="color:<?= $color ?>"><?= (int)$cat['total_stock'] ?> units</strong>
          </span>
        </div>
        <div class="stock-bar-bg" style="height:7px;">
          <div class="stock-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;height:100%;border-radius:4px;"></div>
        </div>
      </div>
    <?php endforeach; else: ?>
      <p style="color:var(--text-muted);text-align:center;padding:20px;">No products yet.</p>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
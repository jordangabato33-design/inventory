<?php
// modules/products/view.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) redirect('/inventory/modules/products/index.php', 'Product not found.', 'error');

// Transaction history via stored procedure
$stmt = $db->prepare("CALL GetProductHistory(?)");
$stmt->bind_param('i', $id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
while ($db->next_result()) { $r = $db->store_result(); $r && $r->free(); }

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Product Detail';</script>

<div class="page-header">
  <div>
    <h1 class="page-title"><?= safe($p['name']) ?></h1>
    <p class="page-sub"><span class="code-chip"><?= safe($p['product_code']) ?></span> &nbsp;<?= safe($p['brand']) ?> · <?= safe($p['category']) ?></p>
  </div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>

<div class="two-col">
  <div class="card">
    <div class="card-header"><span class="card-title">Product Details</span></div>
    <div class="card-body">
      <table style="width:100%;">
        <tr><td style="color:var(--text-muted);padding:8px 0;width:140px;">Code</td><td><span class="code-chip"><?= safe($p['product_code']) ?></span></td></tr>
        <tr><td style="color:var(--text-muted);padding:8px 0;">Name</td><td style="font-weight:600;"><?= safe($p['name']) ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:8px 0;">Brand</td><td><?= safe($p['brand']) ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:8px 0;">Category</td><td><?= safe($p['category']) ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:8px 0;">Unit</td><td><?= safe($p['unit']) ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:8px 0;">Price</td><td class="price-text"><?= peso($p['price']) ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:8px 0;">Stock</td>
            <td style="font-size:22px;font-weight:700;color:<?= $p['quantity']==0?'var(--red)':($p['quantity']<=$p['low_stock_threshold']?'var(--orange)':'var(--green)') ?>">
              <?= (int)$p['quantity'] ?> <?= safe($p['unit']) ?>
            </td>
        </tr>
        <tr><td style="color:var(--text-muted);padding:8px 0;">Status</td>
            <td>
              <?php if ($p['quantity']==0): ?>
                <span class="badge badge-empty">Out of Stock</span>
              <?php elseif ($p['quantity']<=$p['low_stock_threshold']): ?>
                <span class="badge badge-low">Low Stock</span>
              <?php else: ?>
                <span class="badge badge-ok">In Stock</span>
              <?php endif; ?>
            </td>
        </tr>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Quick Actions</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
      <a href="/inventory/modules/stock-in/create.php?pid=<?= $p['id'] ?>" class="btn btn-success">📥 Record Stock-In</a>
      <a href="/inventory/modules/stock-out/create.php?pid=<?= $p['id'] ?>" class="btn btn-danger">📤 Record Stock-Out</a>
      <?php if (isAdmin()): ?>
        <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-warning">✏️ Edit Product</a>
        <a href="delete.php?id=<?= $p['id'] ?>"
           class="btn btn-danger"
           onclick="return confirm('Delete this product? This cannot be undone.')">🗑️ Delete Product</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Transaction History -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📋 Transaction History</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($history) ?> records (via Stored Procedure)</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Type</th><th>Qty</th><th>Reference</th><th>Remarks</th><th>Date</th><th>Handled By</th></tr>
      </thead>
      <tbody>
        <?php if ($history): ?>
          <?php foreach ($history as $h): ?>
          <tr>
            <td><span class="badge <?= $h['transaction_type']==='Stock-In'?'badge-in':'badge-out' ?>"><?= safe($h['transaction_type']) ?></span></td>
            <td style="font-weight:700;color:<?= $h['transaction_type']==='Stock-In'?'var(--green)':'var(--red)' ?>">
              <?= $h['transaction_type']==='Stock-In'?'+':'-' ?><?= (int)$h['quantity'] ?>
            </td>
            <td><?= safe($h['reference'] ?? '—') ?></td>
            <td><?= safe($h['remarks']   ?? '—') ?></td>
            <td><?= safe($h['transaction_date']) ?></td>
            <td><?= safe($h['handled_by']) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px;">No transactions yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

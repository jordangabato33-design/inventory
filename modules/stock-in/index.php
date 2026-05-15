<?php
// modules/stock-in/index.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = getDB();
$rows = $db->query("
    SELECT si.*, p.name AS product_name, p.product_code, p.brand,
           u.name AS recorded_by
    FROM stock_in si
    JOIN products p ON si.product_id = p.id
    JOIN users u ON si.created_by = u.id
    ORDER BY si.created_at DESC LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Stock-In';</script>

<div class="page-header">
  <div><h1 class="page-title">📥 Stock-In</h1><p class="page-sub">Incoming hardware records</p></div>
  <a href="create.php" class="btn btn-success">+ Record Stock-In</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Code</th><th>Product</th><th>Brand</th><th>Qty Added</th><th>Supplier</th><th>Date Received</th><th>Recorded By</th><th>Remarks</th></tr>
      </thead>
      <tbody>
        <?php if ($rows): ?>
          <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td style="color:var(--text-muted);"><?= $i+1 ?></td>
            <td><span class="code-chip"><?= safe($r['product_code']) ?></span></td>
            <td style="font-weight:600;"><?= safe($r['product_name']) ?></td>
            <td style="color:var(--text-sub);"><?= safe($r['brand']) ?></td>
            <td style="font-weight:700;color:var(--green);">+<?= (int)$r['quantity'] ?></td>
            <td><?= safe($r['supplier'] ?? '—') ?></td>
            <td><?= safe($r['date_received']) ?></td>
            <td style="color:var(--text-sub);"><?= safe($r['recorded_by']) ?></td>
            <td style="color:var(--text-muted);font-size:12px;"><?= safe($r['remarks'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">📥</div><p>No stock-in records yet.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

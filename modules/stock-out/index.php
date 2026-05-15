<?php
// modules/stock-out/index.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db   = getDB();
$rows = $db->query("
    SELECT so.*, p.name AS product_name, p.product_code, p.brand,
           u.name AS recorded_by
    FROM stock_out so
    JOIN products p ON so.product_id = p.id
    JOIN users u ON so.created_by = u.id
    ORDER BY so.created_at DESC LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Stock-Out';</script>

<div class="page-header">
  <div><h1 class="page-title">📤 Stock-Out</h1><p class="page-sub">Outgoing hardware records</p></div>
  <a href="create.php" class="btn btn-danger">+ Record Stock-Out</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Code</th><th>Product</th><th>Brand</th><th>Qty Released</th><th>Reason</th><th>Date Released</th><th>Recorded By</th><th>Remarks</th></tr>
      </thead>
      <tbody>
        <?php if ($rows): ?>
          <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td style="color:var(--text-muted);"><?= $i+1 ?></td>
            <td><span class="code-chip"><?= safe($r['product_code']) ?></span></td>
            <td style="font-weight:600;"><?= safe($r['product_name']) ?></td>
            <td style="color:var(--text-sub);"><?= safe($r['brand']) ?></td>
            <td style="font-weight:700;color:var(--red);">-<?= (int)$r['quantity'] ?></td>
            <td><?= safe($r['reason'] ?? '—') ?></td>
            <td><?= safe($r['date_released']) ?></td>
            <td style="color:var(--text-sub);"><?= safe($r['recorded_by']) ?></td>
            <td style="color:var(--text-muted);font-size:12px;"><?= safe($r['remarks'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">📤</div><p>No stock-out records yet.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

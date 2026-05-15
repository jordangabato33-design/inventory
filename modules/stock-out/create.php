<?php
// modules/stock-out/create.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/pusher.php';
requireLogin();

$db       = getDB();
$errors   = [];
$preId    = (int)($_GET['pid'] ?? 0);
$products = $db->query("SELECT id, product_code, name, brand, quantity, unit FROM products ORDER BY product_code ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $product_id   = (int)($_POST['product_id']   ?? 0);
    $quantity     = (int)($_POST['quantity']     ?? 0);
    $reason       = trim($_POST['reason']        ?? '');
    $remarks      = trim($_POST['remarks']       ?? '');
    $date_released = trim($_POST['date_released'] ?? '');
    $user         = currentUser();

    if ($product_id <= 0)       $errors[] = 'Please select a product.';
    if ($quantity   <= 0)       $errors[] = 'Quantity must be greater than 0.';
    if (empty($date_released))  $errors[] = 'Date released is required.';
    if (!DateTime::createFromFormat('Y-m-d', $date_released)) $errors[] = 'Invalid date format.';

    // CRITICAL: Prevent negative stock
    if ($product_id > 0 && $quantity > 0 && empty($errors)) {
        $chk = $db->prepare("SELECT name, product_code, quantity, unit, low_stock_threshold FROM products WHERE id = ? LIMIT 1");
        $chk->bind_param('i', $product_id);
        $chk->execute();
        $current = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$current) {
            $errors[] = 'Selected product does not exist.';
        } elseif ($current['quantity'] <= 0) {
            $errors[] = "❌ {$current['name']} is out of stock! Cannot release.";
        } elseif ($quantity > $current['quantity']) {
            $errors[] = "❌ Insufficient stock! Available: {$current['quantity']} {$current['unit']}. You tried to release: {$quantity}.";
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO stock_out (product_id,quantity,reason,remarks,date_released,created_by) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('iisssi', $product_id, $quantity, $reason, $remarks, $date_released, $user['id']);

        if ($stmt->execute()) {
            // Get updated quantity after trigger
            $pStmt = $db->prepare("SELECT name, product_code, quantity, low_stock_threshold FROM products WHERE id = ? LIMIT 1");
            $pStmt->bind_param('i', $product_id);
            $pStmt->execute();
            $updated = $pStmt->get_result()->fetch_assoc();
            $pStmt->close();

            // Broadcast via Pusher
            try {
                getPusher()->trigger('inventory-channel', 'stock-updated', [
                    'product_id'   => $product_id,
                    'product_code' => $updated['product_code'],
                    'product'      => $updated['name'],
                    'new_quantity' => $updated['quantity'],
                    'quantity'     => $quantity,
                    'type'         => 'Stock-Out',
                    'by_user'      => $user['name'],
                    'is_low'       => $updated['quantity'] <= $updated['low_stock_threshold'],
                    'transaction_date' => $date_released,
                ]);
            } catch (Exception $e) {
                error_log('Pusher error: ' . $e->getMessage());
            }

            $stmt->close();
            redirect('/inventory/modules/stock-out/index.php', "Stock-Out recorded: -{$quantity} units of {$updated['name']}");
        } else {
            $errors[] = 'Database error. Please try again.';
            $stmt->close();
        }
    }
}

$reasons = ['Sold','Damaged','Defective','Expired Warranty','Internal Use','Transferred','Returned to Supplier','Lost','Other'];

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Record Stock-Out';</script>

<div class="page-header">
  <div><h1 class="page-title">📤 Record Stock-Out</h1><p class="page-sub">Release hardware from inventory</p></div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">⚠️ <?= implode('<br>', array_map('safe', $errors)) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><span class="card-title">Stock-Out Form</span></div>
  <div class="card-body">
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= safe(generateCsrfToken()) ?>">

      <div class="form-grid">
        <div class="form-group full">
          <label>Product *</label>
          <select name="product_id" id="product_select" required onchange="showStock(this)">
            <option value="">-- Select Product --</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= $p['id'] ?>"
                data-qty="<?= (int)$p['quantity'] ?>"
                data-unit="<?= safe($p['unit']) ?>"
                <?= ((int)($_POST['product_id'] ?? $preId) === $p['id']) ? 'selected' : '' ?>>
                [<?= safe($p['product_code']) ?>] <?= safe($p['name']) ?> — <?= safe($p['brand']) ?> (<?= (int)$p['quantity'] ?> in stock)
              </option>
            <?php endforeach; ?>
          </select>
          <div id="stock-info" class="form-hint" style="margin-top:8px;"></div>
        </div>

        <div class="form-group">
          <label>Quantity to Release *</label>
          <input type="number" name="quantity" id="qty_input"
                 value="<?= (int)($_POST['quantity'] ?? '') ?>"
                 min="1" placeholder="e.g. 2" required>
          <div class="form-hint" id="qty-hint"></div>
        </div>

        <div class="form-group">
          <label>Reason</label>
          <select name="reason">
            <option value="">-- Select Reason --</option>
            <?php foreach ($reasons as $r): ?>
              <option value="<?= $r ?>" <?= (($_POST['reason'] ?? '') === $r) ? 'selected' : '' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Date Released *</label>
          <input type="date" name="date_released"
                 value="<?= safe($_POST['date_released'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="form-group full">
          <label>Remarks</label>
          <textarea name="remarks" placeholder="Optional notes..."><?= safe($_POST['remarks'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-danger">📤 Submit Stock-Out</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
function showStock(sel) {
    const opt    = sel.options[sel.selectedIndex];
    const qty    = opt.dataset.qty !== undefined ? parseInt(opt.dataset.qty) : null;
    const unit   = opt.dataset.unit || '';
    const info   = document.getElementById('stock-info');
    const hint   = document.getElementById('qty-hint');
    const input  = document.getElementById('qty_input');

    if (qty !== null && sel.value) {
        const color = qty === 0 ? 'var(--red)' : qty <= 3 ? 'var(--orange)' : 'var(--green)';
        info.innerHTML = `Available stock: <strong style="color:${color}">${qty} ${unit}</strong>`;
        input.max = qty;
        if (qty === 0) hint.innerHTML = '<span style="color:var(--red)">⚠️ Out of stock — cannot release</span>';
        else hint.innerHTML = `Max you can release: <strong>${qty}</strong>`;
    } else {
        info.innerHTML = '';
        hint.innerHTML = '';
        input.removeAttribute('max');
    }
}
window.addEventListener('load', () => {
    const sel = document.getElementById('product_select');
    if (sel && sel.value) showStock(sel);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

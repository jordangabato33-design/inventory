<?php
// modules/stock-in/create.php
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

    $product_id    = (int)($_POST['product_id']    ?? 0);
    $quantity      = (int)($_POST['quantity']      ?? 0);
    $supplier      = trim($_POST['supplier']       ?? '');
    $remarks       = trim($_POST['remarks']        ?? '');
    $date_received = trim($_POST['date_received']  ?? '');
    $user          = currentUser();

    if ($product_id <= 0)        $errors[] = 'Please select a product.';
    if ($quantity   <= 0)        $errors[] = 'Quantity must be greater than 0.';
    if (empty($date_received))   $errors[] = 'Date received is required.';
    if (!DateTime::createFromFormat('Y-m-d', $date_received)) $errors[] = 'Invalid date format.';

    if (empty($errors)) {
        // INSERT — MySQL Trigger auto-increases products.quantity
        $stmt = $db->prepare("INSERT INTO stock_in (product_id,quantity,supplier,remarks,date_received,created_by) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('iisssi', $product_id, $quantity, $supplier, $remarks, $date_received, $user['id']);

        if ($stmt->execute()) {
            // Get updated product info after trigger ran
            $pStmt = $db->prepare("SELECT name, product_code, quantity, low_stock_threshold FROM products WHERE id = ? LIMIT 1");
            $pStmt->bind_param('i', $product_id);
            $pStmt->execute();
            $updated = $pStmt->get_result()->fetch_assoc();
            $pStmt->close();

            // Broadcast to Pusher — keys come from .env not JS
            try {
                getPusher()->trigger('inventory-channel', 'stock-updated', [
                    'product_id'   => $product_id,
                    'product_code' => $updated['product_code'],
                    'product'      => $updated['name'],
                    'new_quantity' => $updated['quantity'],
                    'quantity'     => $quantity,
                    'type'         => 'Stock-In',
                    'by_user'      => $user['name'],
                    'is_low'       => $updated['quantity'] <= $updated['low_stock_threshold'],
                    'transaction_date' => $date_received,
                ]);
            } catch (Exception $e) {
                error_log('Pusher error: ' . $e->getMessage());
            }

            $stmt->close();
            redirect('/inventory/modules/stock-in/index.php', "Stock-In recorded: +{$quantity} units of {$updated['name']}");
        } else {
            $errors[] = 'Database error. Please try again.';
            $stmt->close();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Record Stock-In';</script>

<div class="page-header">
  <div><h1 class="page-title">📥 Record Stock-In</h1><p class="page-sub">Add incoming hardware to inventory</p></div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">⚠️ <?= implode('<br>', array_map('safe', $errors)) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><span class="card-title">Stock-In Form</span></div>
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
          <label>Quantity to Add *</label>
          <input type="number" name="quantity" id="qty_input"
                 value="<?= (int)($_POST['quantity'] ?? '') ?>"
                 min="1" placeholder="e.g. 10" required>
        </div>

        <div class="form-group">
          <label>Supplier</label>
          <input type="text" name="supplier"
                 value="<?= safe($_POST['supplier'] ?? '') ?>"
                 placeholder="e.g. TechZone Distributors" maxlength="150">
        </div>

        <div class="form-group">
          <label>Date Received *</label>
          <input type="date" name="date_received"
                 value="<?= safe($_POST['date_received'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="form-group full">
          <label>Remarks</label>
          <textarea name="remarks" placeholder="Optional notes about this delivery..."><?= safe($_POST['remarks'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-success">📥 Submit Stock-In</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
function showStock(sel) {
    const opt  = sel.options[sel.selectedIndex];
    const qty  = opt.dataset.qty;
    const unit = opt.dataset.unit;
    const info = document.getElementById('stock-info');
    if (qty !== undefined && sel.value) {
        const color = parseInt(qty) === 0 ? 'var(--red)' : parseInt(qty) <= 3 ? 'var(--orange)' : 'var(--green)';
        info.innerHTML = `Current stock: <strong style="color:${color}">${qty} ${unit}</strong>`;
    } else {
        info.innerHTML = '';
    }
}
window.addEventListener('load', () => {
    const sel = document.getElementById('product_select');
    if (sel && sel.value) showStock(sel);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

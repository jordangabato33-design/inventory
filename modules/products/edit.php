<?php
// modules/products/edit.php — Admin only
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) redirect('/inventory/modules/products/index.php', 'Product not found.', 'error');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name      = trim($_POST['name']      ?? '');
    $brand     = trim($_POST['brand']     ?? '');
    $category  = trim($_POST['category']  ?? '');
    $unit      = trim($_POST['unit']      ?? 'Piece');
    $price     = (float)($_POST['price']  ?? 0);
    $threshold = (int)($_POST['low_stock_threshold'] ?? 3);

    if (empty($name))     $errors[] = 'Product name is required.';
    if (empty($brand))    $errors[] = 'Brand is required.';
    if (empty($category)) $errors[] = 'Category is required.';
    if ($price < 0)       $errors[] = 'Price cannot be negative.';

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE products SET name=?,brand=?,category=?,unit=?,price=?,low_stock_threshold=? WHERE id=?");
        $stmt->bind_param('ssssdii', $name, $brand, $category, $unit, $price, $threshold, $id);
        if ($stmt->execute()) {
            redirect('/inventory/modules/products/index.php', "Product '$name' updated successfully!");
        } else {
            $errors[] = 'Database error.';
        }
        $stmt->close();
    }
}

$categories = ['Laptop','Monitor','Printer','Mouse','Keyboard','Speaker','Headphone','GPU','CPU','RAM','Storage','Motherboard','PSU','Cooling','Casing','Networking','Accessories','Peripherals','Other'];

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Edit Product';</script>

<div class="page-header">
  <div><h1 class="page-title">Edit Product</h1><p class="page-sub"><?= safe($p['product_code']) ?> — <?= safe($p['name']) ?></p></div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">⚠️ <?= implode('<br>', array_map('safe', $errors)) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><span class="card-title">Edit Information</span></div>
  <div class="card-body">
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= safe(generateCsrfToken()) ?>">

      <div class="form-grid">
        <div class="form-group">
          <label>Product Code</label>
          <input type="text" value="<?= safe($p['product_code']) ?>" disabled
                 style="opacity:0.5;cursor:not-allowed;">
          <div class="form-hint">Product code cannot be changed</div>
        </div>

        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" value="<?= safe($_POST['name'] ?? $p['name']) ?>" required maxlength="150">
        </div>

        <div class="form-group">
          <label>Brand *</label>
          <input type="text" name="brand" value="<?= safe($_POST['brand'] ?? $p['brand']) ?>" required maxlength="100">
        </div>

        <div class="form-group">
          <label>Category *</label>
          <select name="category" required>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c ?>" <?= (($_POST['category'] ?? $p['category']) === $c) ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Unit</label>
          <select name="unit">
            <?php foreach (['Piece','Unit','Set','Box','Pack'] as $u): ?>
              <option value="<?= $u ?>" <?= (($_POST['unit'] ?? $p['unit']) === $u) ? 'selected' : '' ?>><?= $u ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Price (₱)</label>
          <input type="number" name="price" value="<?= htmlspecialchars($_POST['price'] ?? $p['price']) ?>" min="0" step="0.01">
        </div>

        <div class="form-group">
          <label>Current Stock</label>
          <input type="text" value="<?= (int)$p['quantity'] ?> <?= safe($p['unit']) ?>" disabled style="opacity:0.5;">
          <div class="form-hint">Use Stock-In / Stock-Out to change quantity</div>
        </div>

        <div class="form-group">
          <label>Low Stock Threshold</label>
          <input type="number" name="low_stock_threshold" value="<?= (int)($_POST['low_stock_threshold'] ?? $p['low_stock_threshold']) ?>" min="0">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Update Product</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php
// modules/products/create.php — Admin only
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$db     = getDB();
$errors = [];

// Auto-generate next product code
$lastCode = $db->query("SELECT product_code FROM products ORDER BY id DESC LIMIT 1")->fetch_assoc();
$nextNum  = $lastCode ? (int)substr($lastCode['product_code'], 3) + 1 : 1;
$nextCode = 'EC-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $code      = strtoupper(trim($_POST['product_code'] ?? ''));
    $name      = trim($_POST['name']      ?? '');
    $brand     = trim($_POST['brand']     ?? '');
    $category  = trim($_POST['category']  ?? '');
    $unit      = trim($_POST['unit']      ?? 'Piece');
    $price     = (float)($_POST['price']  ?? 0);
    $quantity  = (int)($_POST['quantity'] ?? 0);
    $threshold = (int)($_POST['low_stock_threshold'] ?? 3);

    // Validate
    if (empty($code))      $errors[] = 'Product code is required.';
    if (empty($name))      $errors[] = 'Product name is required.';
    if (empty($brand))     $errors[] = 'Brand is required.';
    if (empty($category))  $errors[] = 'Category is required.';
    if ($price < 0)        $errors[] = 'Price cannot be negative.';
    if ($quantity < 0)     $errors[] = 'Initial quantity cannot be negative.';

    // Check code uniqueness
    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM products WHERE product_code = ? LIMIT 1");
        $chk->bind_param('s', $code);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors[] = 'Product code already exists.';
        $chk->close();
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO products (product_code,name,brand,category,unit,price,quantity,low_stock_threshold) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssdii', $code, $name, $brand, $category, $unit, $price, $quantity, $threshold);
        if ($stmt->execute()) {
            redirect('/inventory/modules/products/index.php', "Product '$name' added successfully!");
        } else {
            $errors[] = 'Database error. Please try again.';
        }
        $stmt->close();
    }
}

$categories = ['Laptop','Monitor','Printer','Mouse','Keyboard','Speaker','Headphone','GPU','CPU','RAM','Storage','Motherboard','PSU','Cooling','Casing','Networking','Accessories','Peripherals','Other'];

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Add Product';</script>

<div class="page-header">
  <div><h1 class="page-title">Add Product</h1><p class="page-sub">Register a new hardware item</p></div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">⚠️ <?= implode('<br>', array_map('safe', $errors)) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><span class="card-title">Product Information</span></div>
  <div class="card-body">
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= safe(generateCsrfToken()) ?>">

      <div class="form-grid">
        <div class="form-group">
          <label>Product Code *</label>
          <input type="text" name="product_code"
                 value="<?= safe($_POST['product_code'] ?? $nextCode) ?>"
                 placeholder="EC-001" maxlength="20" required>
          <div class="form-hint">Auto-suggested: <?= safe($nextCode) ?></div>
        </div>

        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name"
                 value="<?= safe($_POST['name'] ?? '') ?>"
                 placeholder="e.g. RTX 4060 8GB GPU" maxlength="150" required>
        </div>

        <div class="form-group">
          <label>Brand *</label>
          <input type="text" name="brand"
                 value="<?= safe($_POST['brand'] ?? '') ?>"
                 placeholder="e.g. ASUS, MSI, Intel" maxlength="100" required>
        </div>

        <div class="form-group">
          <label>Category *</label>
          <select name="category" required>
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c ?>" <?= (($_POST['category'] ?? '') === $c) ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Unit</label>
          <select name="unit">
            <?php foreach (['Piece','Unit','Set','Box','Pack'] as $u): ?>
              <option value="<?= $u ?>" <?= (($_POST['unit'] ?? 'Piece') === $u) ? 'selected' : '' ?>><?= $u ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Price (₱) *</label>
          <input type="number" name="price"
                 value="<?= htmlspecialchars($_POST['price'] ?? '0') ?>"
                 min="0" step="0.01" placeholder="0.00" required>
        </div>

        <div class="form-group">
          <label>Initial Quantity</label>
          <input type="number" name="quantity"
                 value="<?= (int)($_POST['quantity'] ?? 0) ?>"
                 min="0" placeholder="0">
        </div>

        <div class="form-group">
          <label>Low Stock Threshold</label>
          <input type="number" name="low_stock_threshold"
                 value="<?= (int)($_POST['low_stock_threshold'] ?? 3) ?>"
                 min="0" placeholder="3">
          <div class="form-hint">Alert when stock reaches this number</div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Save Product</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

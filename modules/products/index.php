<?php
// modules/products/index.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db     = getDB();
$search = trim($_GET['search'] ?? '');
$cat    = trim($_GET['category'] ?? '');

// Build query with optional filters
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(p.name LIKE ? OR p.brand LIKE ? OR p.product_code LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like]);
    $types   .= 'sss';
}
if ($cat !== '') {
    $where[]  = 'p.category = ?';
    $params[] = $cat;
    $types   .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql      = "SELECT * FROM products p $whereSQL ORDER BY p.product_code ASC";

if ($params) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $products = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Get categories for filter dropdown
$cats = $db->query("SELECT DISTINCT category FROM products ORDER BY category")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Products';</script>

<div class="page-header">
  <div>
    <h1 class="page-title">🖥️ Products</h1>
    <p class="page-sub">Computer hardware &amp; parts catalog</p>
  </div>
  <?php if (isAdmin()): ?>
    <a href="create.php" class="btn btn-primary">+ Add Product</a>
  <?php endif; ?>
</div>

<!-- SEARCH & FILTER -->
<div class="card" style="margin-bottom:16px;">
  <div class="card-body" style="padding:14px 20px;">
    <form method="GET" class="search-row">
      <div class="search-input-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" placeholder="Search by name, brand, or code..."
               value="<?= safe($search) ?>">
      </div>
      <select name="category" style="width:180px;">
        <option value="">All Categories</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= safe($c['category']) ?>" <?= $cat===$c['category']?'selected':'' ?>>
            <?= safe($c['category']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <?php if ($search || $cat): ?>
        <a href="index.php" class="btn btn-ghost btn-sm">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- PRODUCTS TABLE -->
<div class="card">
  <div class="card-header">
    <span class="card-title">All Products (<?= count($products) ?>)</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Code</th>
          <th>Product Name</th>
          <th>Brand</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($products): ?>
          <?php foreach ($products as $p):
            $pct   = $p['low_stock_threshold'] > 0
                   ? min(100, round(($p['quantity'] / ($p['low_stock_threshold'] * 3)) * 100))
                   : 100;
            $barCl = $p['quantity'] == 0 ? 'bar-red' : ($p['quantity'] <= $p['low_stock_threshold'] ? 'bar-orange' : 'bar-green');
          ?>
          <tr id="product-row-<?= $p['id'] ?>">
            <td><span class="code-chip"><?= safe($p['product_code']) ?></span></td>
            <td>
              <div style="font-weight:600;"><?= safe($p['name']) ?></div>
              <div class="brand-chip"><?= safe($p['unit']) ?></div>
            </td>
            <td style="color:var(--text-sub);"><?= safe($p['brand']) ?></td>
            <td>
              <span style="background:var(--bg-input);color:var(--text-sub);font-size:11px;padding:2px 8px;border-radius:4px;">
                <?= safe($p['category']) ?>
              </span>
            </td>
            <td class="price-text"><?= peso($p['price']) ?></td>
            <td>
              <div class="stock-bar-wrap">
                <span id="qty-<?= $p['id'] ?>" style="font-weight:700;min-width:28px;">
                  <?= (int)$p['quantity'] ?>
                </span>
                <div class="stock-bar-bg">
                  <div class="stock-bar-fill <?= $barCl ?>" style="width:<?= $pct ?>%"></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($p['quantity'] == 0): ?>
                <span class="badge badge-empty" id="badge-<?= $p['id'] ?>">Out of Stock</span>
              <?php elseif ($p['quantity'] <= $p['low_stock_threshold']): ?>
                <span class="badge badge-low" id="badge-<?= $p['id'] ?>">Low Stock</span>
              <?php else: ?>
                <span class="badge badge-ok" id="badge-<?= $p['id'] ?>">In Stock</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="view.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                <a href="/inventory/modules/stock-in/create.php?pid=<?= $p['id'] ?>" class="btn btn-success btn-sm">+In</a>
                <a href="/inventory/modules/stock-out/create.php?pid=<?= $p['id'] ?>" class="btn btn-danger btn-sm">-Out</a>
                <?php if (isAdmin()): ?>
                  <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                  <a href="delete.php?id=<?= $p['id'] ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Delete <?= safe($p['name']) ?>? This cannot be undone.')">Del</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-state-icon">🖥️</div>
                <p>No products found. <?= isAdmin() ? '<a href="create.php" style="color:var(--accent)">Add one now</a>' : '' ?></p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

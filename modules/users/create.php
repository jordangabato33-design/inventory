<?php
// modules/users/create.php — Admin only
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$db = getDB(); $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role']     ?? 'staff');

    if (empty($name))                    $errors[] = 'Full name is required.';
    if (empty($username))                $errors[] = 'Username is required.';
    if (empty($password))                $errors[] = 'Password is required.';
    if (strlen($password) < 6)           $errors[] = 'Password must be at least 6 characters.';
    if (!in_array($role,['admin','staff'])) $errors[] = 'Invalid role selected.';

    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $chk->bind_param('s', $username);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors[] = 'Username already taken.';
        $chk->close();
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt   = $db->prepare("INSERT INTO users (name,username,password,role) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss', $name, $username, $hashed, $role);
        if ($stmt->execute()) redirect('/inventory/modules/users/index.php', "User '$name' created!");
        else $errors[] = 'Database error.';
        $stmt->close();
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Add User';</script>
<div class="page-header">
  <div><h1 class="page-title">Add User</h1><p class="page-sub">Create a new system account</p></div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>
<?php if ($errors): ?><div class="alert alert-error">⚠️ <?= implode('<br>', array_map('safe', $errors)) ?></div><?php endif; ?>
<div class="card"><div class="card-header"><span class="card-title">Account Information</span></div><div class="card-body">
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= safe(generateCsrfToken()) ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" value="<?= safe($_POST['name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz" maxlength="100" required>
      </div>
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" value="<?= safe($_POST['username'] ?? '') ?>" placeholder="e.g. juandc" maxlength="100" required>
      </div>
      <div class="form-group">
        <label>Password * <small style="color:var(--text-muted)">(min 6 chars, stored with Bcrypt)</small></label>
        <input type="password" name="password" placeholder="Enter secure password" required>
      </div>
      <div class="form-group">
        <label>Role *</label>
        <select name="role">
          <option value="staff" <?= (($_POST['role']??'staff')==='staff')?'selected':'' ?>>Staff — Limited Access</option>
          <option value="admin" <?= (($_POST['role']??'')==='admin')?'selected':'' ?>>Admin — Full Access</option>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">💾 Create User</button>
      <a href="index.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

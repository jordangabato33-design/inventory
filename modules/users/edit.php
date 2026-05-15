<?php
// modules/users/edit.php — Admin only
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT id,name,username,role FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id); $stmt->execute();
$u = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$u) redirect('/inventory/modules/users/index.php','User not found.','error');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $role     = trim($_POST['role']     ?? 'staff');
    $password = trim($_POST['password'] ?? '');

    if (empty($name))     $errors[] = 'Name is required.';
    if (empty($username)) $errors[] = 'Username is required.';
    if (!empty($password) && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM users WHERE username=? AND id!=? LIMIT 1");
        $chk->bind_param('si', $username, $id); $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors[] = 'Username already taken.';
        $chk->close();
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET name=?,username=?,role=?,password=? WHERE id=?");
            $stmt->bind_param('ssssi', $name, $username, $role, $hashed, $id);
        } else {
            $stmt = $db->prepare("UPDATE users SET name=?,username=?,role=? WHERE id=?");
            $stmt->bind_param('sssi', $name, $username, $role, $id);
        }
        if ($stmt->execute()) redirect('/inventory/modules/users/index.php', "User '$name' updated!");
        else $errors[] = 'Database error.';
        $stmt->close();
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'Edit User';</script>
<div class="page-header">
  <div><h1 class="page-title">Edit User</h1><p class="page-sub">Editing: <?= safe($u['name']) ?></p></div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>
<?php if ($errors): ?><div class="alert alert-error">⚠️ <?= implode('<br>', array_map('safe', $errors)) ?></div><?php endif; ?>
<div class="card"><div class="card-header"><span class="card-title">Update Account</span></div><div class="card-body">
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= safe(generateCsrfToken()) ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" value="<?= safe($_POST['name'] ?? $u['name']) ?>" required maxlength="100">
      </div>
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" value="<?= safe($_POST['username'] ?? $u['username']) ?>" required maxlength="100">
      </div>
      <div class="form-group">
        <label>New Password <small style="color:var(--text-muted)">(leave blank to keep current)</small></label>
        <input type="password" name="password" placeholder="Enter new password or leave blank">
      </div>
      <div class="form-group">
        <label>Role *</label>
        <select name="role">
          <option value="staff" <?= (($_POST['role']??$u['role'])==='staff')?'selected':'' ?>>Staff</option>
          <option value="admin" <?= (($_POST['role']??$u['role'])==='admin')?'selected':'' ?>>Admin</option>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">💾 Update User</button>
      <a href="index.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

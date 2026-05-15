<?php
// modules/users/index.php — Admin only
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$db    = getDB();
$users = $db->query("SELECT id,name,username,role,created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'User Management';</script>

<div class="page-header">
  <div><h1 class="page-title">👥 Users</h1><p class="page-sub">Manage system accounts</p></div>
  <a href="create.php" class="btn btn-primary">+ Add User</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Name</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $i => $u): ?>
        <tr>
          <td style="color:var(--text-muted);"><?= $i+1 ?></td>
          <td style="font-weight:600;"><?= safe($u['name']) ?></td>
          <td style="color:var(--text-sub);"><?= safe($u['username']) ?></td>
          <td><span class="badge <?= $u['role']==='admin'?'badge-admin':'badge-staff' ?>"><?= safe($u['role']) ?></span></td>
          <td style="color:var(--text-muted);font-size:12px;"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
              <?php if ($u['id'] !== currentUser()['id']): ?>
                <a href="delete.php?id=<?= $u['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete user <?= safe($u['name']) ?>?')">Delete</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">ℹ️ Role Permissions</span></div>
  <div class="card-body">
    <div class="two-col">
      <div style="background:var(--bg-card2);border-radius:8px;padding:16px;border:1px solid var(--border);">
        <div style="font-weight:700;color:#a855f7;margin-bottom:8px;">👑 Admin</div>
        <ul style="color:var(--text-sub);font-size:13px;list-style:none;display:flex;flex-direction:column;gap:4px;">
          <li>✅ Full CRUD on products</li>
          <li>✅ Record Stock-In &amp; Stock-Out</li>
          <li>✅ View all transactions</li>
          <li>✅ Manage users</li>
          <li>✅ Delete products &amp; users</li>
        </ul>
      </div>
      <div style="background:var(--bg-card2);border-radius:8px;padding:16px;border:1px solid var(--border);">
        <div style="font-weight:700;color:var(--accent);margin-bottom:8px;">👤 Staff</div>
        <ul style="color:var(--text-sub);font-size:13px;list-style:none;display:flex;flex-direction:column;gap:4px;">
          <li>✅ View products</li>
          <li>✅ Record Stock-In &amp; Stock-Out</li>
          <li>✅ View transactions</li>
          <li>❌ Cannot delete products</li>
          <li>❌ Cannot manage users</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

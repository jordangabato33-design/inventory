<?php
// modules/auth/profile.php
// Any logged-in user can change their own password

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $current  = trim($_POST['current_password']  ?? '');
    $new      = trim($_POST['new_password']      ?? '');
    $confirm  = trim($_POST['confirm_password']  ?? '');

    // Validate
    if (empty($current))        $errors[] = 'Current password is required.';
    if (empty($new))            $errors[] = 'New password is required.';
    if (strlen($new) < 6)      $errors[] = 'New password must be at least 6 characters.';
    if ($new !== $confirm)     $errors[] = 'New passwords do not match.';

    if (empty($errors)) {
        // Fetch stored password hash
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($current, $row['password'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            // Hash new password with Bcrypt
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            $upd    = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param('si', $hashed, $user['id']);
            if ($upd->execute()) {
                $success = true;
            } else {
                $errors[] = 'Database error. Please try again.';
            }
            $upd->close();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<script>document.getElementById('page-topbar-title').textContent = 'My Profile';</script>

<div class="page-header">
  <div><h1 class="page-title">My Profile</h1><p class="page-sub">Manage your account settings</p></div>
</div>

<div class="two-col">

  <!-- Account Info -->
  <div class="card">
    <div class="card-header"><span class="card-title">👤 Account Information</span></div>
    <div class="card-body">
      <table style="width:100%;">
        <tr>
          <td style="color:var(--text-muted);padding:10px 0;width:120px;">Name</td>
          <td style="font-weight:600;"><?= safe($user['name']) ?></td>
        </tr>
        <tr>
          <td style="color:var(--text-muted);padding:10px 0;">Role</td>
          <td>
            <span class="badge <?= $user['role']==='admin'?'badge-admin':'badge-staff' ?>">
              <?= safe($user['role']) ?>
            </span>
          </td>
        </tr>
        <tr>
          <td style="color:var(--text-muted);padding:10px 0;">Password</td>
          <td style="color:var(--text-muted);">●●●●●●●● (Bcrypt hashed)</td>
        </tr>
      </table>

      <div style="margin-top:20px;padding:14px;background:var(--bg-card2);border-radius:8px;border:1px solid var(--border);">
        <div style="font-size:12px;color:var(--text-muted);line-height:1.8;">
          🔒 Your password is securely stored using <strong>Bcrypt</strong> one-way hashing.<br>
          Even administrators cannot see your actual password.
        </div>
      </div>
    </div>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="card-header"><span class="card-title">🔑 Change Password</span></div>
    <div class="card-body">

      <?php if ($success): ?>
        <div class="alert alert-success">✅ Password changed successfully!</div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="alert alert-error">⚠️ <?= implode('<br>', array_map('safe', $errors)) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= safe(generateCsrfToken()) ?>">

        <div class="form-group" style="margin-bottom:14px;">
          <label>Current Password *</label>
          <input type="password" name="current_password"
                 placeholder="Enter your current password" required>
        </div>

        <div class="form-group" style="margin-bottom:14px;">
          <label>New Password *</label>
          <input type="password" name="new_password"
                 placeholder="Min. 6 characters" required>
        </div>

        <div class="form-group" style="margin-bottom:20px;">
          <label>Confirm New Password *</label>
          <input type="password" name="confirm_password"
                 placeholder="Repeat new password" required>
        </div>

        <button type="submit" class="btn btn-primary">🔒 Update Password</button>
      </form>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

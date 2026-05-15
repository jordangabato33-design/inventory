<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /inventory/index.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if (empty($token) || empty($expected) || !hash_equals($expected, $token)) {
        // just regenerate and show form again
        $error = 'Session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter your username and password.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, name, username, password, role FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];
                unset($_SESSION['csrf_token']);
                header('Location: /inventory/index.php'); exit;
            } else {
                $error = 'Invalid username or password. Please try again.';
            }
        }
    }
}

// Always generate fresh token for the form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Login | InvenTrack</title>
    <link rel="stylesheet" href="/inventory/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      <div class="login-logo">IT</div>
      <div class="login-title">InvenTrack</div>
      <div class="login-sub">Computer Hardware Inventory System</div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="form-group" style="margin-bottom:14px;">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="Enter your username"
               maxlength="100" required autocomplete="username">
      </div>

      <div class="form-group" style="margin-bottom:20px;">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Enter your password"
               maxlength="100" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary"
              style="width:100%;justify-content:center;padding:11px;">
        Sign In →
      </button>
    </form>

    <p style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:20px;">
      Default: <b>admin</b> / password &nbsp;|&nbsp; <b>staff</b> / password
    </p>
  </div>
</div>
</body>
</html>
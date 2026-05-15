<?php
// modules/users/delete.php — Admin only
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$db   = getDB();
$id   = (int)($_GET['id'] ?? 0);
$self = currentUser();

if ($id <= 0 || $id === (int)$self['id']) {
    redirect('/inventory/modules/users/index.php', 'Cannot delete this account.', 'error');
}

$stmt = $db->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

redirect('/inventory/modules/users/index.php', 'User deleted successfully.');

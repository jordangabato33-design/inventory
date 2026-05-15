<?php
// modules/products/delete.php — Admin only
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Get name first for the message
    $stmt = $db->prepare("SELECT name FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($p) {
        $del = $db->prepare("DELETE FROM products WHERE id = ?");
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
        redirect('/inventory/modules/products/index.php', "Product '{$p['name']}' deleted.");
    }
}

redirect('/inventory/modules/products/index.php', 'Product not found.', 'error');

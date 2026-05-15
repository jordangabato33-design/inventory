<?php
// includes/auth.php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /inventory/modules/auth/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /inventory/index.php?error=access_denied');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function currentUser(): array {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name']    ?? 'Unknown',
        'role' => $_SESSION['role']    ?? 'staff',
    ];
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void {
    if (empty($_POST['csrf_token'])) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
    if (!isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
    // Regenerate after use
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function safe(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url, string $msg = '', string $type = 'success'): never {
    if ($msg) $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    header("Location: $url");
    exit;
}

function flashMessage(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $styles = [
        'success' => 'background:#052e16;color:#22c55e;border:1px solid rgba(34,197,94,0.2)',
        'error'   => 'background:#2d0a0a;color:#ef4444;border:1px solid rgba(239,68,68,0.2)',
        'info'    => 'background:rgba(79,110,247,0.08);color:#4f6ef7;border:1px solid rgba(79,110,247,0.2)',
    ];
    $style = $styles[$f['type']] ?? $styles['info'];
    return '<div class="flash" style="' . $style . ';padding:12px 16px;border-radius:6px;margin-bottom:18px;font-size:13px;">'
         . safe($f['msg']) . '</div>';
}

function peso(mixed $v): string {
    return '₱' . number_format((float)$v, 2);
}
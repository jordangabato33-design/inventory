<?php
// modules/auth/logout.php
require_once __DIR__ . '/../../includes/auth.php';
$_SESSION = [];
session_destroy();
header('Location: /inventory/modules/auth/login.php');
exit;

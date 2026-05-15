<?php
// config/env.php — Loads .env file into $_ENV
function loadEnv(string $path): void {
    if (!file_exists($path)) die('<b style="color:red">ERROR:</b> .env file missing. Copy .env.example to .env and fill in your credentials.');
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}
loadEnv(__DIR__ . '/../.env');

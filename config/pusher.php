<?php
// config/pusher.php — Returns configured Pusher instance from .env keys
// IMPORTANT: Keys come from .env — never hardcode in JS files
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

function getPusher(): Pusher\Pusher {
    return new Pusher\Pusher(
        $_ENV['PUSHER_APP_KEY'],
        $_ENV['PUSHER_APP_SECRET'],
        $_ENV['PUSHER_APP_ID'],
        ['cluster' => $_ENV['PUSHER_APP_CLUSTER'], 'useTLS' => true]
    );
}

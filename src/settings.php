<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

// Default configuration
/** @var array<string, array|string|bool> $settings */
$settings = [
    'database' => [
        'dsn' => 'sqlite::memory:'
    ]
];

// Load local settings here:
if (file_exists(GOSSAMER_SERVER_ROOT . '/local/settings.php')) {
    /** @var array $local */
    $local = require GOSSAMER_SERVER_ROOT . '/local/settings.php';
    $settings = $local + $settings;
}
return $settings;

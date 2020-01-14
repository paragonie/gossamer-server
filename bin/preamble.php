<?php
declare(strict_types=1);


define('GOSSAMER_SERVER_ROOT', dirname(__DIR__));

require_once GOSSAMER_SERVER_ROOT . '/vendor/autoload.php';

/** @var array $settings */
$settings = require_once GOSSAMER_SERVER_ROOT . '/src/settings.php';

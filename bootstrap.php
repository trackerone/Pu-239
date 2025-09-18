<?php
declare(strict_types=1);

use PU239\Config\ConfigRepository;

require_once __DIR__ . '/include/runtime_safe.php';
$container = require __DIR__ . '/include/app.php';
require_once __DIR__ . '/include/bootstrap_pdo.php';
require_once __DIR__ . '/include/config_compat.php';
require_once __DIR__ . '/include/session_bootstrap.php';

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);
}

/** @var ConfigRepository $configRepository */
$configRepository = $container->get(ConfigRepository::class);
$paths = $configRepository->get('paths', []);
$cacheDir = (string) ($paths['cache'] ?? (__DIR__ . '/storage/cache'));
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
if (!is_writable($cacheDir)) {
    error_log("cache not writable: {$cacheDir}");
}

require_once ($paths['classes'] ?? (__DIR__ . '/include/class/')) . 'class_check.php';

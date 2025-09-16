<?php
declare(strict_types=1);
require_once __DIR__ . '/include/runtime_safe.php';
require_once __DIR__ . '/include/bootstrap_pdo.php';

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);
}

global $site_config;
$cacheDir = $site_config['paths']['cache'] ?? (__DIR__ . '/storage/cache');
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
if (!is_writable($cacheDir)) {
    error_log("cache not writable: {$cacheDir}");
}

require_once CLASS_DIR . 'class_check.php';

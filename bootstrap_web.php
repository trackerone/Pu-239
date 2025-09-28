<?php
declare(strict_types=1);

use PU239\Config\ConfigRepository;

require_once __DIR__ . '/bootstrap_core.php';
// Web specifics (optional): session_start() if needed, headers defaults, timezone.
// Finally include app definitions that expect container:
if (file_exists(__DIR__ . '/include/app.php')) {
    require_once __DIR__ . '/include/app.php';
}
require_once __DIR__ . '/include/bootstrap_pdo.php';
require_once __DIR__ . '/include/config_compat.php';
require_once __DIR__ . '/include/helpers/cookies.php';
require_once __DIR__ . '/include/helpers/http.php';
require_once __DIR__ . '/include/session_bootstrap.php';

if (!function_exists('pu239_send_security_headers')) {
    // >>>>>> PU239:headers-2
    function pu239_send_security_headers(): void
    {
        static $sent = false;
        if ($sent || headers_sent()) {
            return;
        }
        // >>>>>> PU239:headers-1
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer-when-downgrade');
        header('X-Frame-Options: DENY');
        // TODO(2025): consider enabling a baseline CSP with nonces (script-src 'self' 'nonce-<generated>')
        $sent = true;
    }
}

pu239_send_security_headers();

$cacheDir = __DIR__ . '/storage/cache';
$paths = [];
if (isset($container) && $container->has(ConfigRepository::class)) {
    /** @var ConfigRepository $configRepository */
    $configRepository = $container->get(ConfigRepository::class);
    $paths = $configRepository->get('paths', []);
    $cacheDir = (string) ($paths['cache'] ?? $cacheDir);
}
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
if (!is_writable($cacheDir)) {
    error_log("cache not writable: {$cacheDir}");
}

$classesDir = is_string($paths['classes'] ?? null) && $paths['classes'] !== ''
    ? rtrim((string) $paths['classes'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
    : __DIR__ . '/include/class/';
$classCheck = $classesDir . 'class_check.php';
if (is_file($classCheck)) {
    require_once $classCheck;
}

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);
}

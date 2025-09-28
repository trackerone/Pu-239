<?php
declare(strict_types=1);

use Delight\Cookie\Session;
use PU239\Config\ConfigRepository;

if (!isset($container) || !$container->has(ConfigRepository::class)) {
    return;
}

/** @var ConfigRepository $configRepository */
$configRepository = $container->get(ConfigRepository::class);
/** @var array<string, mixed> $sessionConfig */
$sessionConfig = $configRepository->get('session', []);

$iniMap = [
    'cookie_secure' => 'session.cookie_secure',
    'cookie_httponly' => 'session.cookie_httponly',
    'use_cookies' => 'session.use_cookies',
    'use_only_cookies' => 'session.use_only_cookies',
    'use_strict_mode' => 'session.use_strict_mode',
    'use_trans_sid' => 'session.use_trans_sid',
    'lazy_write' => 'session.lazy_write',
    'gc_maxlifetime' => 'session.gc_maxlifetime',
    'cookie_domain' => 'session.cookie_domain',
    'cookie_path' => 'session.cookie_path',
    'sid_length' => 'session.sid_length',
];

foreach ($iniMap as $configKey => $iniKey) {
    if (array_key_exists($configKey, $sessionConfig)) {
        $value = $sessionConfig[$configKey];
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }
        ini_set($iniKey, (string) $value);
    }
}

if (isset($sessionConfig['ini']) && is_array($sessionConfig['ini'])) {
    foreach ($sessionConfig['ini'] as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }
        ini_set((string) $key, (string) $value);
    }
}

if (isset($sessionConfig['name']) && is_string($sessionConfig['name']) && $sessionConfig['name'] !== '') {
    session_name($sessionConfig['name']);
}

if (isset($sessionConfig['handler']) && is_string($sessionConfig['handler']) && $sessionConfig['handler'] !== '') {
    ini_set('session.save_handler', $sessionConfig['handler']);
}

$startMode = is_string($sessionConfig['start_mode'] ?? null) ? $sessionConfig['start_mode'] : 'Strict';

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_lifetime', '0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    Session::start($startMode);
}

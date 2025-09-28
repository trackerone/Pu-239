<?php
declare(strict_types=1);

/**
 * set_app_cookie sets a cookie with security defaults.
 */
function set_app_cookie(
    string $name,
    string $value,
    array $opts = []
): void {
    $ttl = (int) ($opts['ttl'] ?? 0);
    $expires = $ttl > 0 ? time() + $ttl : 0;
    $path = $opts['path'] ?? '/';
    $domain = $opts['domain'] ?? '';
    $secure = $opts['secure'] ?? true;
    $httponly = $opts['httponly'] ?? true;
    $samesite = $opts['samesite'] ?? 'Lax';

    // PHP 7.3+ array syntax for setcookie
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => $path,
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite,
    ]);
}

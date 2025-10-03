<?php
declare(strict_types=1);

namespace PU239\Security;

use JsonException;
use Redis;
use RedisException;

final class RateLimiter
{
    private const PREFIX = 'pu239:ratelimit:';
    private const FS_GC_THRESHOLD = 50;

    /**
     * Enforce the rate limit for the current request.
     *
     * @return array{0: bool, 1: int} [allowed, retryAfterSeconds]
     */
    public static function check(?int $limit = null, ?int $window = null): array
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'POST') {
            return [true, 0];
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $path = self::currentPath();

        if (self::isAllowlisted($ip, $path)) {
            return [true, 0];
        }

        $limit = $limit ?? self::defaultLimit();
        $window = $window ?? self::defaultWindow();
        $window = max(1, $window);

        $key = self::cacheKey($ip, $path, $limit, $window);
        $now = time();

        $driver = self::driver();
        if ($driver === 'redis') {
            return self::checkRedis($key, $limit, $window);
        }
        if ($driver === 'fs') {
            return self::checkFilesystem($key, $limit, $window, $now);
        }

        return self::checkApcu($key, $limit, $window, $now);
    }

    private static function defaultLimit(): int
    {
        $limit = getenv('RATE_DEFAULT_LIMIT');
        $limit = is_string($limit) && $limit !== '' ? (int) $limit : 10;

        return max(1, $limit);
    }

    private static function defaultWindow(): int
    {
        $window = getenv('RATE_DEFAULT_WINDOW');
        $window = is_string($window) && $window !== '' ? (int) $window : 60;

        return max(1, $window);
    }

    public static function loginDefaults(): array
    {
        $limit = getenv('RATE_LOGIN_LIMIT');
        $window = getenv('RATE_LOGIN_WINDOW');

        $limit = is_string($limit) && $limit !== '' ? (int) $limit : 5;
        $window = is_string($window) && $window !== '' ? (int) $window : 60;

        return [max(1, $limit), max(1, $window)];
    }

    private static function cacheKey(string $ip, string $path, int $limit, int $window): string
    {
        $hash = hash('sha256', implode('|', [$ip, $path, $limit, $window]));

        return self::PREFIX . $hash;
    }

    private static function driver(): string
    {
        static $driver;
        if ($driver !== null) {
            return $driver;
        }

        $candidate = strtolower((string) getenv('RATE_DRIVER'));
        if ($candidate === '') {
            $candidate = 'apcu';
        }

        if ($candidate === 'redis' && self::redisClient() === null) {
            $candidate = self::apcuAvailable() ? 'apcu' : 'fs';
        } elseif ($candidate === 'apcu' && !self::apcuAvailable()) {
            $candidate = self::redisClient() !== null ? 'redis' : 'fs';
        }

        if ($candidate !== 'redis' && $candidate !== 'apcu' && $candidate !== 'fs') {
            $candidate = self::apcuAvailable() ? 'apcu' : 'fs';
        }

        if ($candidate === 'fs') {
            self::ensureFilesystemStorage();
        }

        $driver = $candidate;

        return $driver;
    }

    private static function checkRedis(string $key, int $limit, int $window): array
    {
        $redis = self::redisClient();
        if ($redis === null) {
            return self::checkApcu($key, $limit, $window, time());
        }

        try {
            $count = (int) $redis->incr($key);
            if ($count === 1) {
                $redis->expire($key, $window);
            }

            $ttl = (int) $redis->ttl($key);
            if ($ttl < 0) {
                $redis->expire($key, $window);
                $ttl = $window;
            }

            if ($count > $limit) {
                return [false, max(1, $ttl)];
            }

            return [true, max(0, $ttl)];
        } catch (RedisException $e) {
            error_log('RateLimiter redis failure: ' . $e->getMessage());

            return self::checkApcu($key, $limit, $window, time());
        }
    }

    private static function checkApcu(string $key, int $limit, int $window, int $now): array
    {
        if (!self::apcuAvailable()) {
            return self::checkFilesystem($key, $limit, $window, $now);
        }

        $data = apcu_fetch($key);
        if (!is_array($data) || !isset($data['count'], $data['expires_at']) || $data['expires_at'] < $now) {
            $data = [
                'count' => 0,
                'expires_at' => $now + $window,
            ];
        }

        if ($data['count'] >= $limit) {
            $retryAfter = max(1, $data['expires_at'] - $now);
            apcu_store($key, $data, $window);

            return [false, $retryAfter];
        }

        $data['count']++;
        apcu_store($key, $data, $window);

        return [true, max(0, $data['expires_at'] - $now)];
    }

    private static function checkFilesystem(string $key, int $limit, int $window, int $now): array
    {
        $dir = self::filesystemStorage();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . DIRECTORY_SEPARATOR . $key . '.json';
        $data = null;
        if (is_file($file)) {
            $json = file_get_contents($file);
            if (is_string($json) && $json !== '') {
                try {
                    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $data = $decoded;
                    }
                } catch (JsonException) {
                    $data = null;
                }
            }
        }

        if (!is_array($data) || !isset($data['count'], $data['expires_at']) || $data['expires_at'] < $now) {
            $data = [
                'count' => 0,
                'expires_at' => $now + $window,
            ];
        }

        if ($data['count'] >= $limit) {
            $retryAfter = max(1, $data['expires_at'] - $now);
            self::writeFilesystem($file, $data);

            return [false, $retryAfter];
        }

        $data['count']++;
        self::writeFilesystem($file, $data);
        self::maybePruneFilesystem($dir, $now);

        return [true, max(0, $data['expires_at'] - $now)];
    }

    private static function writeFilesystem(string $file, array $data): void
    {
        try {
            file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR), LOCK_EX);
        } catch (JsonException) {
            // Fallback to best-effort encoding without exceptions
            file_put_contents($file, json_encode($data), LOCK_EX);
        }

        if (isset($data['expires_at']) && is_int($data['expires_at'])) {
            @touch($file, $data['expires_at']);
        }
    }

    private static function maybePruneFilesystem(string $dir, int $now): void
    {
        static $pruned = false;
        if ($pruned) {
            return;
        }

        if (mt_rand(1, self::FS_GC_THRESHOLD) !== 1) {
            return;
        }

        $pruned = true;
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.json');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $expiresAt = @filemtime($file);
            if ($expiresAt !== false && $expiresAt < $now) {
                @unlink($file);
            }
        }
    }

    private static function ensureFilesystemStorage(): void
    {
        $dir = self::filesystemStorage();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    private static function filesystemStorage(): string
    {
        $root = defined('ROOT_DIR') ? (string) ROOT_DIR : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;

        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'ratelimit';
    }

    private static function currentPath(): string
    {
        $path = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['REQUEST_URI'] ?? '');
        $path = is_string($path) ? $path : '';

        return $path === '' ? '/' : $path;
    }

    private static function isAllowlisted(string $ip, string $path): bool
    {
        foreach (self::allowIps() as $allowedIp) {
            if ($allowedIp !== '' && strcasecmp($allowedIp, $ip) === 0) {
                return true;
            }
        }

        $normalizedPath = '/' . ltrim($path, '/');
        foreach (self::allowPaths() as $prefix) {
            if ($prefix === '') {
                continue;
            }
            $normalizedPrefix = '/' . ltrim($prefix, '/');
            if (str_starts_with($normalizedPath, $normalizedPrefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function allowIps(): array
    {
        static $ips;
        if ($ips !== null) {
            return $ips;
        }

        $raw = getenv('RATE_ALLOW_IPS');
        if (!is_string($raw) || trim($raw) === '') {
            $ips = [];

            return $ips;
        }

        $parts = array_map(static fn(string $value): string => trim($value), explode(',', $raw));
        $ips = array_values(array_filter($parts, static fn(string $value): bool => $value !== ''));

        return $ips;
    }

    /**
     * @return list<string>
     */
    private static function allowPaths(): array
    {
        static $paths;
        if ($paths !== null) {
            return $paths;
        }

        $raw = getenv('RATE_ALLOW_PATHS');
        if (!is_string($raw) || trim($raw) === '') {
            $paths = [];

            return $paths;
        }

        $parts = array_map(static fn(string $value): string => trim($value), explode(',', $raw));
        $paths = array_values(array_filter($parts, static fn(string $value): bool => $value !== ''));

        return $paths;
    }

    private static function apcuAvailable(): bool
    {
        if (!function_exists('apcu_fetch')) {
            return false;
        }

        $enabled = (bool) ini_get('apc.enabled');
        if (PHP_SAPI === 'cli') {
            $enabled = $enabled && (bool) ini_get('apc.enable_cli');
        }

        return $enabled;
    }

    private static function redisClient(): ?Redis
    {
        static $client;
        static $attempted = false;
        if ($client instanceof Redis) {
            return $client;
        }
        if ($attempted) {
            return null;
        }
        $attempted = true;

        if (!class_exists(Redis::class)) {
            return null;
        }

        $dsn = getenv('REDIS_DSN');
        $host = '127.0.0.1';
        $port = 6379;
        $password = null;
        $database = null;
        $timeout = 1.0;

        if (is_string($dsn) && $dsn !== '') {
            $parts = parse_url($dsn);
            if ($parts !== false) {
                $host = $parts['host'] ?? $host;
                $port = isset($parts['port']) ? (int) $parts['port'] : $port;
                if (isset($parts['user']) || isset($parts['pass'])) {
                    $authUser = $parts['user'] ?? '';
                    $authPass = $parts['pass'] ?? '';
                    $password = $authPass !== '' ? $authPass : ($authUser !== '' ? $authUser : null);
                }
                if (!empty($parts['path'])) {
                    $dbPath = ltrim($parts['path'], '/');
                    if ($dbPath !== '') {
                        $database = (int) $dbPath;
                    }
                }
                if (!empty($parts['query'])) {
                    parse_str($parts['query'], $query);
                    if (isset($query['db'])) {
                        $database = (int) $query['db'];
                    }
                    if (isset($query['timeout'])) {
                        $timeout = (float) $query['timeout'];
                    }
                }
            }
        } else {
            $envHost = getenv('REDIS_HOST');
            $envPort = getenv('REDIS_PORT');
            if (is_string($envHost) && $envHost !== '') {
                $host = $envHost;
            }
            if (is_string($envPort) && $envPort !== '') {
                $port = (int) $envPort;
            }
        }

        try {
            $redis = new Redis();
            $redis->connect($host, $port, $timeout);
            if ($password !== null && $password !== '') {
                $redis->auth($password);
            }
            if ($database !== null) {
                $redis->select($database);
            }
            $client = $redis;
        } catch (RedisException $e) {
            error_log('RateLimiter redis connect failure: ' . $e->getMessage());
            $client = null;
        }

        return $client;
    }
}

<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use PU239\Config\ConfigLoader;
use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use Throwable;

require_once __DIR__ . '/runtime_safe.php';

if (isset($container) && $container instanceof ContainerInterface) {
    return $container;
}

$baseDir = dirname(__DIR__);
$configDir = $baseDir . DIRECTORY_SEPARATOR . 'config';
$environment = getenv('APP_ENV') ?: 'local';

$loader = new ConfigLoader();
$configRepository = $loader->load($configDir, $environment);

$timezone = $configRepository->get('app.timezone', 'UTC');
if (is_string($timezone) && $timezone !== '') {
    date_default_timezone_set($timezone);
}

$builder = new ContainerBuilder();
if ($configRepository->get('app.production', false)) {
    $diCache = $configRepository->get('paths.di_cache');
    $cachePath = is_string($diCache) && $diCache !== '' ? $diCache : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-di';
    if (!is_dir($cachePath)) {
        @mkdir($cachePath, 0755, true);
    }
    $builder->enableCompilation($cachePath);
}

$cacheConfig = $configRepository->get('cache', []);
$databaseConfig = $configRepository->get('database', []);
$pathsConfig = $configRepository->get('paths', []);
$storageConfig = $configRepository->get('storage', []);
$webserverConfig = $configRepository->get('webserver', []);
$languageConfig = $configRepository->get('language', []);
$mailConfig = $configRepository->get('mail', []);

$baseUrl = rtrim((string) $configRepository->get('app.url', 'https://pu239.example'), '/');
$cacheDir = (string) ($pathsConfig['cache'] ?? ($baseDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR));
$logsSqlDir = (string) ($pathsConfig['logs_sqlerror'] ?? ($baseDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'sqlerr' . DIRECTORY_SEPARATOR));

$envCompatibility = [
    'mail' => [
        'smtp_enable' => (bool) ($mailConfig['smtp']['enabled'] ?? false),
        'smtp_host' => (string) ($mailConfig['smtp']['host'] ?? 'smtp.gmail.com'),
        'smtp_auth' => (bool) ($mailConfig['smtp']['auth'] ?? true),
        'smtp_username' => (string) ($mailConfig['smtp']['username'] ?? 'username@example.com'),
        'smtp_password' => (string) ($mailConfig['smtp']['password'] ?? ''),
        'smtp_secure' => (string) ($mailConfig['smtp']['secure'] ?? 'tls'),
        'smtp_port' => (int) ($mailConfig['smtp']['port'] ?? 587),
    ],
    'db' => [
        'type' => 'mysql',
        'host' => (string) ($databaseConfig['host'] ?? '127.0.0.1'),
        'port' => (int) ($databaseConfig['port'] ?? 3306),
        'socket' => (string) ($databaseConfig['socket'] ?? '/var/run/mysqld/mysqld.sock'),
        'database' => (string) ($databaseConfig['database'] ?? 'pu239'),
        'username' => (string) ($databaseConfig['user'] ?? 'root'),
        'password' => (string) ($databaseConfig['pass'] ?? ''),
        'charset' => (string) ($databaseConfig['charset'] ?? 'utf8mb4'),
        'use_socket' => (bool) ($databaseConfig['use_socket'] ?? false),
        'query_limit' => (int) ($databaseConfig['query_limit'] ?? 65536),
        'attributes' => $databaseConfig['options'] ?? [],
        'debug' => (bool) ($databaseConfig['debug'] ?? false),
    ],
    'cache' => [
        'driver' => (string) ($cacheConfig['default']['driver'] ?? 'memory'),
        'prefix' => (string) ($cacheConfig['default']['prefix'] ?? 'pu239_'),
    ],
    'peer_cache' => [
        'driver' => (string) ($cacheConfig['peer']['driver'] ?? 'memcached'),
        'prefix' => (string) ($cacheConfig['peer']['prefix'] ?? 'Peers_'),
    ],
    'redis' => $cacheConfig['redis'] ?? [],
    'files' => [
        'path' => (string) ($storageConfig['filesystem']['path'] ?? ($cacheDir . 'files')),
    ],
    'memcached' => $cacheConfig['memcached'] ?? [],
    'paths' => [
        'flood_file' => $cacheDir . 'floodlimits.txt',
        'happyhour' => $cacheDir . 'happyhour.cache',
        'sql_error_log' => $logsSqlDir . 'sql_err_' . date('Y_m_d') . '.log',
        'baseurl' => $baseUrl,
        'images_baseurl' => './images' . DIRECTORY_SEPARATOR,
        'nfos_baseurl' => './images' . DIRECTORY_SEPARATOR . 'nfo' . DIRECTORY_SEPARATOR,
        'chat_images_baseurl' => $baseUrl . '/images' . DIRECTORY_SEPARATOR,
        'log_viewer' => [
            '/var/log/apache2/',
            '/var/log/nginx/',
            '/var/log/mysql/',
        ],
    ],
    'bucket' => $storageConfig['bucket'] ?? [],
    'language' => [
        'imdb' => (string) ($languageConfig['imdb'] ?? 'en-US'),
    ],
    'webserver' => [
        'username' => (string) ($webserverConfig['user'] ?? 'www-data'),
    ],
    'available_languages' => $languageConfig['available'] ?? ['en_US'],
];

$builder->addDefinitions([
    ConfigRepository::class => $configRepository,
    'config.repository' => $configRepository,
    'env' => $envCompatibility,
    'smilies' => $configRepository->get('smilies', []),
    'subtitles' => $configRepository->get('subtitles', []),
    'where' => $configRepository->get('where', []),
]);

$definitionsFile = $configDir . DIRECTORY_SEPARATOR . 'definitions.php';
if (is_file($definitionsFile)) {
    $builder->addDefinitions($definitionsFile);
}

$builder->useAutowiring(true);
$builder->useAnnotations(false);

try {
    $container = $builder->build();
} catch (Throwable $e) {
    app_halt("try 'composer install', then check that definitions.php matches src directory");
}

return $container;

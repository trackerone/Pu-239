<?php
declare(strict_types=1);

use PU239\Config\ConfigRepository;
use Psr\Log\LoggerInterface;

if (!isset($container) || !$container->has(ConfigRepository::class)) {
    return;
}

/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$logger = null;
if ($container->has(LoggerInterface::class)) {
    $logger = $container->get(LoggerInterface::class);
}

$logDeprecation = static function (string $name) use ($logger): void {
    $message = sprintf('[config_compat] Constant %s is deprecated and will be removed in a future release.', $name);
    if ($logger instanceof LoggerInterface) {
        $logger->warning($message);
    } else {
        error_log($message);
    }
};

$define = static function (string $name, mixed $value) use ($logDeprecation): void {
    if (!defined($name)) {
        define($name, $value);
        $logDeprecation($name);
    }
};

$paths = $config->get('paths', []);
$system = $config->get('system', []);
$permissions = $config->get('permissions.masks', []);
$classes = $config->get('classes.levels', []);

$define('TIME_NOW', time());
$define('ROOT_DIR', (string) ($paths['root'] ?? dirname(__DIR__) . DIRECTORY_SEPARATOR));
$define('INCL_DIR', (string) ($paths['include'] ?? (ROOT_DIR . 'include' . DIRECTORY_SEPARATOR)));
$define('ADMIN_DIR', (string) ($paths['admin'] ?? (ROOT_DIR . 'admin' . DIRECTORY_SEPARATOR)));
$define('BIN_DIR', (string) ($paths['bin'] ?? (ROOT_DIR . 'bin' . DIRECTORY_SEPARATOR)));
$define('SCRIPTS_DIR', (string) ($paths['scripts'] ?? (ROOT_DIR . 'scripts' . DIRECTORY_SEPARATOR)));
$define('FORUM_DIR', (string) ($paths['forums'] ?? (ROOT_DIR . 'forums' . DIRECTORY_SEPARATOR)));
$define('CHAT_DIR', (string) ($paths['chat'] ?? (ROOT_DIR . 'chat' . DIRECTORY_SEPARATOR)));
$define('PM_DIR', (string) ($paths['messages'] ?? (ROOT_DIR . 'messages' . DIRECTORY_SEPARATOR)));
$define('CACHE_DIR', (string) ($paths['cache'] ?? (ROOT_DIR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR)));
$define('LANG_DIR', (string) ($paths['lang'] ?? (ROOT_DIR . 'lang' . DIRECTORY_SEPARATOR)));
$define('TEMPLATE_DIR', (string) ($paths['templates'] ?? (ROOT_DIR . 'templates' . DIRECTORY_SEPARATOR)));
$define('BLOCK_DIR', (string) ($paths['blocks'] ?? (ROOT_DIR . 'blocks' . DIRECTORY_SEPARATOR)));
$define('CLASS_DIR', (string) ($paths['classes'] ?? (INCL_DIR . 'class' . DIRECTORY_SEPARATOR)));
$define('CLEAN_DIR', (string) ($paths['cleanup'] ?? (ROOT_DIR . 'cleanup' . DIRECTORY_SEPARATOR)));
$define('PUBLIC_DIR', (string) ($paths['public'] ?? (ROOT_DIR . 'public' . DIRECTORY_SEPARATOR)));
$define('CONFIG_DIR', (string) ($paths['config'] ?? (ROOT_DIR . 'config' . DIRECTORY_SEPARATOR)));
$define('IMAGES_DIR', (string) ($paths['images'] ?? (PUBLIC_DIR . 'images' . DIRECTORY_SEPARATOR)));
$define('PROXY_IMAGES_DIR', (string) ($paths['proxy_images'] ?? (IMAGES_DIR . 'proxy' . DIRECTORY_SEPARATOR)));
$define('VENDOR_DIR', (string) ($paths['vendor'] ?? (ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR)));
$define('NODE_DIR', (string) ($paths['node_modules'] ?? (ROOT_DIR . 'node_modules' . DIRECTORY_SEPARATOR)));
$define('DATABASE_DIR', (string) ($paths['database'] ?? (ROOT_DIR . 'database' . DIRECTORY_SEPARATOR)));
$define('BITBUCKET_DIR', (string) ($paths['bucket'] ?? (ROOT_DIR . 'bucket' . DIRECTORY_SEPARATOR)));
$define('LOGS_DIR', (string) ($paths['logs'] ?? (ROOT_DIR . 'logs' . DIRECTORY_SEPARATOR)));
$define('SQLERROR_LOGS_DIR', (string) ($paths['logs_sqlerror'] ?? (LOGS_DIR . 'sqlerr' . DIRECTORY_SEPARATOR)));
$define('PHPERROR_LOGS_DIR', (string) ($paths['logs_phperror'] ?? (LOGS_DIR . 'phperr' . DIRECTORY_SEPARATOR)));
$define('RADIANCE_LOGS_DIR', (string) ($paths['logs_radiance'] ?? (LOGS_DIR . 'radiance' . DIRECTORY_SEPARATOR)));
$define('XBT_LOGS_DIR', (string) ($paths['logs_xbt'] ?? (LOGS_DIR . 'xbt' . DIRECTORY_SEPARATOR)));
$define('PLUGINS_DIR', (string) ($paths['plugins'] ?? (ROOT_DIR . 'plugins' . DIRECTORY_SEPARATOR)));
$define('PARTIALS_DIR', (string) ($paths['partials'] ?? (ROOT_DIR . 'partials' . DIRECTORY_SEPARATOR)));
$define('TORRENTS_DIR', (string) ($paths['torrents'] ?? (ROOT_DIR . 'torrents' . DIRECTORY_SEPARATOR)));
$define('USER_TORRENTS_DIR', (string) ($paths['torrents_users'] ?? (TORRENTS_DIR . 'users' . DIRECTORY_SEPARATOR)));
$define('BACKUPS_DIR', (string) ($paths['backups'] ?? (ROOT_DIR . 'backups' . DIRECTORY_SEPARATOR)));
$define('AJAX_CHAT_PATH', (string) ($paths['ajax_chat'] ?? (ROOT_DIR . 'chat' . DIRECTORY_SEPARATOR)));
$define('IMDB_CACHE_DIR', (string) ($paths['imdb_cache'] ?? (CACHE_DIR . 'imdb' . DIRECTORY_SEPARATOR)));
$define('URL_CACHE_DIR', (string) ($paths['url_cache'] ?? (CACHE_DIR . 'url' . DIRECTORY_SEPARATOR)));
$define('UPLOADSUB_DIR', (string) ($paths['upload_subtitles'] ?? (ROOT_DIR . 'uploadsub' . DIRECTORY_SEPARATOR)));
$define('NFO_DIR', (string) ($paths['nfo'] ?? (IMAGES_DIR . 'nfo' . DIRECTORY_SEPARATOR)));
$define('ATTACHMENT_DIR', (string) ($paths['attachments'] ?? (ROOT_DIR . 'uploads' . DIRECTORY_SEPARATOR)));
$define('LOCALES_DIR', (string) ($paths['locale'] ?? (ROOT_DIR . 'locale' . DIRECTORY_SEPARATOR)));
$define('DI_CACHE_DIR', (string) ($paths['di_cache'] ?? (DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'shm' . DIRECTORY_SEPARATOR . 'php-di' . DIRECTORY_SEPARATOR)));
$define('PRODUCTION', (bool) $config->get('app.production', false));

$define('MYSQLDUMP', (string) ($system['mysqldump'] ?? '/usr/bin/mysqldump'));
$define('GZIP', (string) ($system['gzip'] ?? '/bin/gzip'));

foreach ($permissions as $name => $value) {
    $define($name, $value);
}
foreach ($classes as $name => $value) {
    $define($name, $value);
}

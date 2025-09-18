<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$root = rtrim(realpath(__DIR__ . '/..') ?: dirname(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$join = static function (string ...$segments) use ($root): string {
    $path = $root;
    if ($segments !== []) {
        $path .= implode(DIRECTORY_SEPARATOR, $segments) . DIRECTORY_SEPARATOR;
    }

    return $path;
};

$storageCache = $join('storage', 'cache');

return [
    'paths' => [
        'root' => $root,
        'include' => $join('include'),
        'admin' => $join('admin'),
        'bin' => $join('bin'),
        'scripts' => $join('scripts'),
        'forums' => $join('forums'),
        'chat' => $join('chat'),
        'messages' => $join('messages'),
        'cache' => $storageCache,
        'lang' => $join('lang'),
        'templates' => $join('templates'),
        'blocks' => $join('blocks'),
        'classes' => $join('include', 'class'),
        'cleanup' => $join('cleanup'),
        'public' => $join('public'),
        'config' => $join('config'),
        'images' => $join('public', 'images'),
        'proxy_images' => $join('public', 'images', 'proxy'),
        'vendor' => $join('vendor'),
        'node_modules' => $join('node_modules'),
        'database' => $join('database'),
        'bucket' => $join('bucket'),
        'logs' => $join('logs'),
        'logs_sqlerror' => $join('logs', 'sqlerr'),
        'logs_phperror' => $join('logs', 'phperr'),
        'logs_radiance' => $join('logs', 'radiance'),
        'logs_xbt' => $join('logs', 'xbt'),
        'plugins' => $join('plugins'),
        'partials' => $join('partials'),
        'torrents' => $join('torrents'),
        'torrents_users' => $join('torrents', 'users'),
        'backups' => $join('backups'),
        'ajax_chat' => $join('chat'),
        'imdb_cache' => $storageCache . 'imdb' . DIRECTORY_SEPARATOR,
        'url_cache' => $storageCache . 'url' . DIRECTORY_SEPARATOR,
        'upload_subtitles' => $join('uploadsub'),
        'nfo' => $join('public', 'images', 'nfo'),
        'attachments' => $join('uploads'),
        'locale' => $join('locale'),
        'di_cache' => getenv('DI_CACHE_DIR') ?: DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'shm' . DIRECTORY_SEPARATOR . 'php-di' . DIRECTORY_SEPARATOR,
    ],
];

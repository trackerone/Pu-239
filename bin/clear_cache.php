<?php
require_once __DIR__ . '/runtime_safe.php';


declare(strict_types = 1);

global $site_config;

require_once __DIR__ . '/../include/bittorrent.php';
require_once BIN_DIR . 'functions.php';
$database = '';
clear_di_cache();
cleanup(get_webserver_user());
if (!empty($argv[1]) && !is_array($argv[1])) {
    $cache->delete($argv[1]);
    app_halt("Cache: {$argv[1]} cleared\n");
} else {
    if ($site_config['cache']['driver'] === 'file' && file_exists($site_config['files']['path'])) {
        passthru("sudo rm -r {$site_config['files']['path']}");
    } else {
        $cache->flushDB();
        if ($site_config['cache']['driver'] === 'redis') {
            $database = " [DB:{$site_config['redis']['database']}]";
        }
    }
    app_halt(ucfirst($site_config['cache']['driver']) . " Cache{$database} was flushed\n");
}

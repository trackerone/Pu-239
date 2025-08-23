<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

define('TIME_NOW', time());
define('ROOT_DIR', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define('INCL_DIR', ROOT_DIR . 'include' . DIRECTORY_SEPARATOR);
define('ADMIN_DIR', ROOT_DIR . 'admin' . DIRECTORY_SEPARATOR);
define('BIN_DIR', ROOT_DIR . 'bin' . DIRECTORY_SEPARATOR);
define('SCRIPTS_DIR', ROOT_DIR . 'scripts' . DIRECTORY_SEPARATOR);
define('FORUM_DIR', ROOT_DIR . 'forums' . DIRECTORY_SEPARATOR);
define('CHAT_DIR', ROOT_DIR . 'chat' . DIRECTORY_SEPARATOR);
define('PM_DIR', ROOT_DIR . 'messages' . DIRECTORY_SEPARATOR);
define('CACHE_DIR', ROOT_DIR . 'cache' . DIRECTORY_SEPARATOR);
define('LANG_DIR', ROOT_DIR . 'lang' . DIRECTORY_SEPARATOR);
define('TEMPLATE_DIR', ROOT_DIR . 'templates' . DIRECTORY_SEPARATOR);
define('BLOCK_DIR', ROOT_DIR . 'blocks' . DIRECTORY_SEPARATOR);
define('CLASS_DIR', INCL_DIR . 'class' . DIRECTORY_SEPARATOR);
define('CLEAN_DIR', ROOT_DIR . 'cleanup' . DIRECTORY_SEPARATOR);
define('PUBLIC_DIR', ROOT_DIR . 'public' . DIRECTORY_SEPARATOR);
define('CONFIG_DIR', ROOT_DIR . 'config' . DIRECTORY_SEPARATOR);
define('IMAGES_DIR', PUBLIC_DIR . 'images' . DIRECTORY_SEPARATOR);
define('PROXY_IMAGES_DIR', IMAGES_DIR . 'proxy' . DIRECTORY_SEPARATOR);
define('VENDOR_DIR', ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR);
define('NODE_DIR', ROOT_DIR . 'node_modules' . DIRECTORY_SEPARATOR);
define('DATABASE_DIR', ROOT_DIR . 'database' . DIRECTORY_SEPARATOR);
define('BITBUCKET_DIR', ROOT_DIR . 'bucket' . DIRECTORY_SEPARATOR);
define('LOGS_DIR', ROOT_DIR . 'logs' . DIRECTORY_SEPARATOR);
define('SQLERROR_LOGS_DIR', LOGS_DIR . 'sqlerr' . DIRECTORY_SEPARATOR);
define('PHPERROR_LOGS_DIR', LOGS_DIR . 'phperr' . DIRECTORY_SEPARATOR);
define('RADIANCE_LOGS_DIR', LOGS_DIR . 'radiance' . DIRECTORY_SEPARATOR);
define('XBT_LOGS_DIR', LOGS_DIR . 'xbt' . DIRECTORY_SEPARATOR);
define('PLUGINS_DIR', ROOT_DIR . 'plugins' . DIRECTORY_SEPARATOR);
define('PARTIALS_DIR', ROOT_DIR . 'partials' . DIRECTORY_SEPARATOR);
define('TORRENTS_DIR', ROOT_DIR . 'torrents' . DIRECTORY_SEPARATOR);
define('USER_TORRENTS_DIR', TORRENTS_DIR . 'users' . DIRECTORY_SEPARATOR);
define('BACKUPS_DIR', ROOT_DIR . 'backups' . DIRECTORY_SEPARATOR);
define('AJAX_CHAT_PATH', ROOT_DIR . 'chat' . DIRECTORY_SEPARATOR);
define('IMDB_CACHE_DIR', CACHE_DIR . 'imdb' . DIRECTORY_SEPARATOR);
define('URL_CACHE_DIR', CACHE_DIR . 'url' . DIRECTORY_SEPARATOR);
define('UPLOADSUB_DIR', ROOT_DIR . 'uploadsub' . DIRECTORY_SEPARATOR);
define('NFO_DIR', IMAGES_DIR . 'nfo' . DIRECTORY_SEPARATOR);
define('ATTACHMENT_DIR', ROOT_DIR . 'uploads' . DIRECTORY_SEPARATOR);
define('LOCALES_DIR', ROOT_DIR . 'locale' . DIRECTORY_SEPARATOR);
define('DI_CACHE_DIR', DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'shm' . DIRECTORY_SEPARATOR . 'php-di' . DIRECTORY_SEPARATOR);
define('PRODUCTION', false);

define('MYSQLDUMP', '/usr/bin/mysqldump');
define('GZIP', '/bin/gzip');
const PERMS_NO_IP = 0x1; // 1
const PERMS_BYPASS_BAN = 0x2; // 2
const UNLOCK_MORE_MOODS = 0x4; // 4
const PERMS_STEALTH = 0x8; // 8

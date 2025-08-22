<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
$user = check_user_status();
global $container, $site_config;

$set = [
    'override_class' => 255,
];
$users_class = $container->get(User::class);
$users_class->update($set, $user['id']);
$fluent = $container->get(Database::class);
$fluent->deleteFrom('ajax_chat_online')
       ->where('userID = ?', $user['id'])
       ->execute();
$cache = $container->get(Cache::class);
$cache->delete('chat_users_list_');
header("Location: {$site_config['paths']['baseurl']}");

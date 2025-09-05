<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_html.php';
$user = check_user_status();
global $container, $site_config;
$set = [
    'curr_ann_id' => 0,
    'curr_ann_last_check' => 0,
];
$fluent = $container->get(Database::class);
// TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

$cache = $container->get(Cache::class);
$cache->update_row('user_' . $user['id'], [
    'curr_ann_id' => 0,
    'curr_ann_last_check' => 0,
], $site_config['expires']['user_cache']);
header("Location: {$site_config['paths']['baseurl']}");

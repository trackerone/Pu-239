<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);





use Pu239\Cache;
use Pu239\Database;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();
global $container, $site_config;

$set = [
    'override_class' => 255,
];
$users_class = $container->get(User::class);
$users_class->update($set, $user['id']);
// $fluent removed — use $this->db (ExtendedPdo)
$sql = "DELETE FROM ajax_chat_online WHERE userID = :userID";
$db->perform($sql, ['userID' => $user['id']]);
$cache = $container->get(Cache::class);
$cache->delete('chat_users_list_');
header("Location: {$site_config['paths']['baseurl']}");

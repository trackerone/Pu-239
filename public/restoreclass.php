<?php
declare(strict_types=1);

use PU239\Support\Audit;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\User;

require_once dirname(__DIR__) . '/bootstrap_web.php';

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);
$baseUrl = (string) $config->get('paths.baseurl');

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();

$set = [
    'override_class' => 255,
];
$previousOverride = $user['override_class'] ?? null;
$users_class = $container->get(User::class);
$users_class->update($set, $user['id']);
Audit::log(
    $user['id'] ?? null,
    'role.change',
    [
        'target' => $user['id'] ?? null,
        'from' => $previousOverride,
        'to' => $set['override_class'],
    ],
);
// $fluent removed — use $this->db (ExtendedPdo)
$sql = "DELETE FROM ajax_chat_online WHERE userID = :userID";
$db->perform($sql, ['userID' => $user['id']]);
$cache = $container->get(Cache::class);
$cache->delete('chat_users_list_');
header('Location: ' . $baseUrl);

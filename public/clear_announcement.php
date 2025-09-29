<?php
declare(strict_types=1);

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();

$db->run(
    'UPDATE users SET curr_ann_id = :curr_ann_id, curr_ann_last_check = :curr_ann_last_check WHERE id = :id AND curr_ann_id != 0',
    [
        ':curr_ann_id' => 0,
        ':curr_ann_last_check' => 0,
        ':id' => $user['id'],
    ],
);
audit_log($user['id'] ?? null, 'announcement.clear', []);

$cache = $container->get(Cache::class);
$cache->update_row(
    'user_' . $user['id'],
    [
        'curr_ann_id' => 0,
        'curr_ann_last_check' => 0,
    ],
    $config->get('expires.user_cache'),
);

header('Location: ' . $config->get('paths.baseurl'));

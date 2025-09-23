<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\User;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sid = isset($_GET['id']) ? (int) $_GET['id'] : 1;
    if ($sid > 0 && $sid != $user['stylesheet']) {
        $set = [
            'stylesheet' => $sid,
        ];
        $users_class = $container->get(User::class);
        $users_class->update($set, $user['id']);
    }
}

$baseUrl = (string) $config->get('paths.baseurl');
$returnto = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $baseUrl;
header("Location: $returnto");

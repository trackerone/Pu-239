<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);





use Delight\Auth\Auth;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
global $container, $site_config;

$auth = $container->get(Auth::class);
if ($auth->isLoggedIn()) {
    $userid = $auth->getUserId();
    if (!empty($userid)) {
        $user = $container->get(User::class);
        $user->logout($userid, true);
    }
}
header("Location: {$site_config['paths']['baseurl']}/login.php");

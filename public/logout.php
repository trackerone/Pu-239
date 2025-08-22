<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

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

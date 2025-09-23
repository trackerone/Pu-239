<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';





use Delight\Auth\Auth;
use Pu239\Config\ConfigRepository;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$baseurl = (string) $config->get('paths.baseurl');

$auth = $container->get(Auth::class);
if ($auth->isLoggedIn()) {
    $userid = $auth->getUserId();
    if (!empty($userid)) {
        $user = $container->get(User::class);
        $user->logout($userid, true);
    }
}
header("Location: {$baseurl}/login.php");

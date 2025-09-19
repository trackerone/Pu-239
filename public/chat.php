<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);





require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();
global $site_config;

$nick = $user ? $user['username'] : ('Guest_' . random_int(1000, 9999));
$HTMLOUT = main_div("
    <div class='padding20'>
    <p class='has-text-centered'>" . _fe('The official IRC channel is {0}#pu-239{1}', "<a href='irc://irc.p2p-network.net'>", '</a>')) . '</p>';

$title = _('IRC');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT), stdfoot();

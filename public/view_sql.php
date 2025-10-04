<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Config\ConfigRepository;
use Pu239\Session;

if (!defined('PU239_ROUTED')) {
    require_once __DIR__ . '/index.php';

    return;
}

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();
$stdfoot = [
    'js' => [
        get_file_name('iframe_js'),
    ],
];
global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$baseUrl = (string) $config->get('paths.baseurl');
$databaseName = (string) $config->get('db.database');

if (empty($user) || !has_access($user['class'], UC_SYSOP, 'coder')) {
    $session = $container->get(Session::class);
    $session->set('is-danger', 'You do not have access to that page.');
    write_log($user['username'] . ' has attempted to access Adminer');
    write_info($user['username'] . ' has attempted to access a Staff Page');
    header("Location: {$baseUrl}");
    app_halt('Exit called');
} else {
    write_info($user['username'] . ' has accessed a Staff Page: Adminer');
    $html = "<iframe src='{$baseUrl}/ajax/view_sql.php?username={$user['username']}&db={$databaseName}' id='iframe_adminer' name='iframe_adminer' onload='resizeIframe(this)' class='iframe'></iframe>";

    $title = _('Adminer');
    $breadcrumbs = [
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot($stdfoot);
}

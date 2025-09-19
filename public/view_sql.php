<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);




use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();
$stdfoot = [
    'js' => [
        get_file_name('iframe_js'),
    ],
];
global $container, $site_config;

if (empty($user) || !has_access($user['class'], UC_SYSOP, 'coder')) {
    $session = $container->get(Session::class);
    $session->set('is-danger', 'You do not have access to that page.');
    write_log($user['username'] . ' has attempted to access Adminer');
    write_info($user['username'] . ' has attempted to access a Staff Page');
    header("Location: {$site_config['paths']['baseurl']}");
    app_halt('Exit called');
} else {
    write_info($user['username'] . ' has accessed a Staff Page: Adminer');
    $html = "<iframe src='{$site_config['paths']['baseurl']}/ajax/view_sql.php?username={$user['username']}&db={$site_config['db']['database']}' id='iframe_adminer' name='iframe_adminer' onload='resizeIframe(this)' class='iframe'></iframe>";

    $title = _('Adminer');
    $breadcrumbs = [
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot($stdfoot);
}

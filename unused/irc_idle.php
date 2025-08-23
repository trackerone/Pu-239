<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

$key = 'VGhlIE1vemlsbGEgZmFtaWx5IGFwcG';
$vars = [
    'ircidle' => '',
    'username' => '',
    'key' => '',
    'do' => '',
];
foreach ($vars as $k => $v) {
    $vars[$k] = isset($_GET[$k]) ? $_GET[$k] : '';
}
if ($key !== $vars['key'] || empty($vars['username'])) {
    app_halt('hmm something looks odd');
}
require_once __DIR__ . '/include/bittorrent.php';
switch ($vars['do']) {
    case 'check':
        $q = sql_query('SELECT id FROM users WHERE username = ' . sqlesc($vars['username']));
        echo mysqli_num_rows($q);
        break;

    case 'idle':
        sql_query('UPDATE users SET onirc = ' . sqlesc(!$vars['ircidle'] ? 'no' : 'yes') . ' WHERE username = ' . sqlesc($vars['username']));
        echo mysqli_affected_rows($mysqli);
        break;

    default:
        app_halt('hmm something looks odd again');
}
app_halt('Exit called');

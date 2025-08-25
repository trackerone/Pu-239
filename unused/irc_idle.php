<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

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
        $q = $db->run(');
}
app_halt('Exit called');

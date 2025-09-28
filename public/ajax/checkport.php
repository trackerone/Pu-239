<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once __DIR__ . '/../../include/bittorrent.php';

check_user_status();
// TODO(2025): csrf
if (empty($_POST['ip']) || empty($_POST['port'])) {
    return false;
}
$ip = $_POST['ip'];
$port = (int) $_POST['port'];

$connection = fsockopen($ip, $port, $errno, $errstr);
if (is_resource($connection)) {
    $msg = [
        'class' => 'has-text-success',
        'text' => _('OPEN'),
    ];
    fclose($connection);
} else {
    $msg = [
        'class' => 'has-text-danger',
        'text' => _fe('CLOSED => {0}', $errstr),
    ];
}
$status = ['data' => $msg];
json_out($status);

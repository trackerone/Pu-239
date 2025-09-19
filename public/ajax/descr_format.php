<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap_web.php';

$db = $container->get(Database::class);




use Pu239\Torrent;

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();
header('content-type: application/json');
global $container;

$tid = (int) $_POST['tid'];
if (!empty($tid)) {
    $torrents_class = $container->get(Torrent::class);
    $descr = $torrents_class->format_descr($tid);
    if (!empty($descr)) {
        echo json_encode(['descr' => $descr]);
        app_halt('Exit called');
    }
}
echo json_encode([
    'fail' => 'invalid',
]);
app_halt('Exit called');

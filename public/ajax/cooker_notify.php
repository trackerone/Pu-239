<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();
header('content-type: application/json');
global $container;

if (empty($user)) {
    echo json_encode(['notify' => 'invalid']);
    die();
}
$id = (int) $_POST['id'];
$notified = (bool) $_POST['notified'];
if (empty($id) || !isset($notified)) {
    echo json_encode(['notify' => 'invalid']);
    die();
}
$fluent = $container->get(Database::class);
if ($notified) {
    try {
        $fluent->deleteFrom('upcoming_notify')
               ->where('userid = ?', $user['id'])
               ->where('upcomingid = ?', $id)
               ->execute();
        echo json_encode(['notify' => 0]);
        die();
    } catch (Exception $e) {
        // TODO
    }
} else {
    $values = [
        'userid' => $user['id'],
        'upcomingid' => $id,
    ];
    try {
        $notify_id = $fluent->insertInto('upcoming_notify')
                            ->values($values)
                            ->execute();

        echo json_encode(['notify' => $notify_id]);
        die();
    } catch (Exception $e) {
        // TODO
    }
}
echo json_encode(['notify' => 'invalid']);
die();

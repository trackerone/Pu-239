<?php
require_once __DIR__ . '/../../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();
header('content-type: application/json');
global $container;

if (empty($user)) {
    echo json_encode(['notify' => 'invalid']);
    app_halt('Exit called');
}
$id = (int) $_POST['id'];
$notified = (bool) $_POST['notified'];
if (empty($id) || !isset($notified)) {
    echo json_encode(['notify' => 'invalid']);
    app_halt('Exit called');
}
$fluent = $container->get(Database::class);
if ($notified) {
    try {
        $fluent->deleteFrom('request_notify')
               ->where('userid = ?', $user['id'])
               ->where('requestid = ?', $id)
               ->execute();
        echo json_encode(['notify' => 0]);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
} else {
    $values = [
        'userid' => $user['id'],
        'requestid' => $id,
    ];
    try {
        $notify_id = $fluent->insertInto('request_notify')
                            ->values($values)
                            ->execute();

        echo json_encode(['notify' => $notify_id]);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
}
echo json_encode(['notify' => 'invalid']);
app_halt('Exit called');

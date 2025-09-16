<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

$db = $container->get(Database::class);




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
        $fluent->deleteFrom('upcoming_notify')
               ->where('userid = ?', $user['id'])
               ->where('upcomingid = ?', $id)
               ->execute();
        echo json_encode(['notify' => 0]);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
} else {
    $values = [
        'userid' => $user['id'],
        'upcomingid' => $id,
    ];
    try {
        $sql = "INSERT INTO upcoming_notify (/* columns */) VALUES (/* values */)";
$notify_id = $db->perform($sql, $values);

        echo json_encode(['notify' => $notify_id]);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
}
echo json_encode(['notify' => 'invalid']);
app_halt('Exit called');

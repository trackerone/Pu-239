<?php
require_once __DIR__ . '/../../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();
header('content-type: application/json');
global $container;

if (empty($user)) {
    echo json_encode(['vote' => 'invalid']);
    app_halt('Exit called');
}
$id = (int) $_POST['id'];
$voted = $_POST['voted'];
if (empty($id) || !isset($voted)) {
    echo json_encode(['voted' => 'invalid']);
    app_halt('Exit called');
}
// $fluent removed — use $this->db (ExtendedPdo)
if ($voted === 'yes') {
    $update = [
        'vote' => 'no',
    ];
    try {
        $fluent->update('request_votes')
               ->set($update)
               ->where('user_id = ?', $user['id'])
               ->where('request_id = ?', $id)
               ->execute();
        echo json_encode(['voted' => 'no']);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
} elseif ($voted === 'no') {
    try {
        $fluent->deleteFrom('request_votes')
               ->where('user_id = ?', $user['id'])
               ->where('request_id = ?', $id)
               ->execute();
        echo json_encode(['voted' => 0]);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
} else {
    $values = [
        'vote' => 'yes',
        'user_id' => $user['id'],
        'request_id' => $id,
    ];
    try {
        $sql = "INSERT INTO request_votes (/* columns */) VALUES (/* values */)";
$this->db->perform($sql, $values);
        echo json_encode(['voted' => 'yes']);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
}
echo json_encode(['voted' => 'invalid']);
app_halt('Exit called');

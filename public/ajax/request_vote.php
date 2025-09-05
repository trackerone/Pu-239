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
$fluent = $container->get(Database::class);
if ($voted === 'yes') {
    $update = [
        'vote' => 'no',
    ];
    try {
        // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
        echo json_encode(['voted' => 'no']);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
} elseif ($voted === 'no') {
    try {
        // TODO: review delete
$sql = "DELETE FROM table WHERE ...";
$this->db->perform($sql, [/* params */]);;
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
        // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
        echo json_encode(['voted' => 'yes']);
        app_halt('Exit called');
    } catch (Exception $e) {
        // TODO
    }
}
echo json_encode(['voted' => 'invalid']);
app_halt('Exit called');

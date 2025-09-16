<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

$db = $container->get(Database::class);




use Pu239\Cache;
use Pu239\Database;

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();

header('content-type: application/json');
global $container;

if (empty($user) || $user['class'] < UC_STAFF) {
    echo json_encode(['show_in_navbar' => 'class']);
    app_halt('Exit called');
}

if (!isset($_POST['show']) || empty($_POST['id'])) {
    echo json_encode(['show_in_navbar' => 'invalid']);
    app_halt('Exit called');
}

$show = $_POST['show'] == 0 ? 1 : 0;
$set = [
    'navbar' => $show,
];
// $fluent removed — use $this->db (ExtendedPdo)
$sql = "UPDATE staffpanel SET /* columns */ WHERE id = :id";
$result = $db->perform($sql, array_merge($set, ['id' => $_POST['id']]));

if ($result) {
    $cache = $container->get(Cache::class);
    $cache->delete('staff_panels_' . $class);
    $data['show_in_navbar'] = $show;
    echo json_encode($data);
    app_halt('Exit called');
} else {
    $data['show_in_navbar'] = 'fail';
    echo json_encode($data);
    app_halt('Exit called');
}

<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap_web.php';

$db = $container->get(Database::class);




use Pu239\User;

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();
header('content-type: application/json');
global $container;

$term = htmlsafechars(strtolower(strip_tags($_POST['keyword'])));
if (!empty($term)) {
    $users_class = $container->get(User::class);
    $users = $users_class->search_by_username($term);
    if (!empty($users)) {
        echo json_encode($users);
        app_halt('Exit called');
    }
}
$status = ['data' => _('Invalid Request')];
echo json_encode($status);
app_halt('Exit called');

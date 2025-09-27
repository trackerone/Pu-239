<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\User;

require_once __DIR__ . '/../../include/bittorrent.php';

check_user_status();

header('Content-Type: application/json; charset=utf-8');

// TODO(2025): add CSRF verification
$keyword = trim((string) ($_POST['keyword'] ?? ''));

if ($keyword === '') {
    echo json_encode(['data' => _('Invalid Request')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$users = $container->get(User::class)->search_by_username(strtolower($keyword));

if (!empty($users)) {
    echo json_encode($users, JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

echo json_encode(['data' => _('Invalid Request')], JSON_THROW_ON_ERROR);
app_halt('Exit called');

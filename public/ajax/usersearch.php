<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\User;

require_once __DIR__ . '/../../include/bittorrent.php';

check_user_status();

// TODO(2025): add CSRF verification
$keyword = trim((string) ($_POST['keyword'] ?? ''));

if ($keyword === '') {
    json_out(['data' => _('Invalid Request')]);
}

$users = $container->get(User::class)->search_by_username(strtolower($keyword));

if (!empty($users)) {
    json_out($users);
}

json_out(['data' => _('Invalid Request')]);

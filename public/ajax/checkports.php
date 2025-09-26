<?php

declare(strict_types=1);

use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();

// TODO(2025): csrf
$requestedUserId = (int) ($_POST['uid'] ?? 0);
if ($requestedUserId <= 0) {
    return false;
}

$uid = has_access($user['class'], UC_STAFF, '') ? $requestedUserId : (int) $user['id'];

$sql = <<<SQL
    SELECT INET6_NTOA(ip) AS ip, port, agent
    FROM peers
    WHERE userid = :uid
SQL;
$ips = $db->toArray($sql, ['uid' => $uid]);

$out = '';
$used = [];
foreach ($ips as $peer) {
    $ip = $peer['ip'];
    $port = (int) $peer['port'];
    $agent = $s($peer['agent']);

    if (in_array($ip . $port, $used, true)) {
        continue;
    }
    $used[] = $ip . $port;

    $connection = @fsockopen($ip, $port, $errno, $errstr, 10.0);
    $ipDisplay = $s($ip);
    if (is_resource($connection)) {
        $message = "<span class='has-text-success'>" . _('OPEN') . '</span>';
        fclose($connection);
    } else {
        $message = "<span class='has-text-danger'>" . _fe('CLOSED => {0}', $s($errstr)) . '</span>';
    }

    $out .= "
    <div class='columns is-multiline is-gapless padding10'>
        <span class='column is-2 padding5'>{$ipDisplay}</span>
        <span class='column is-1 padding5'>{$port}</span>
        <span class='column is-2 padding5'>{$agent}</span>
        <span class='column padding5 has-text-left'>{$message}</span>
    </div>";
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['data' => $out], JSON_THROW_ON_ERROR);

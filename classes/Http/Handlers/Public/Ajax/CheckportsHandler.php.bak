<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;

final class CheckportsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var Database $db */
            $db = $container->get(Database::class);

            $s = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $user = check_user_status();

            // TODO(2025): csrf
            $requestedUserId = (int) ($_POST['uid'] ?? 0);
            if ($requestedUserId <= 0) {
                return;
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
                $ip = (string) $peer['ip'];
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

            json_out(['data' => $out]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

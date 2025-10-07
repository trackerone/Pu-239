<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=65-5

namespace PU239\Http\Handlers\Public\Ajax;

final class CheckportHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=65-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            check_user_status();
            // TODO(2025): csrf
            if (empty($_POST['ip']) || empty($_POST['port'])) {
                return;
            }
            $ip = (string) $_POST['ip'];
            $port = (int) $_POST['port'];

            $errno = 0;
            $errstr = '';
            $connection = fsockopen($ip, $port, $errno, $errstr);
            if (is_resource($connection)) {
                $msg = [
                    'class' => 'has-text-success',
                    'text' => _('OPEN'),
                ];
                fclose($connection);
            } else {
                $msg = [
                    'class' => 'has-text-danger',
                    'text' => _fe('CLOSED => {0}', $errstr),
                ];
            }
            $status = ['data' => $msg];
            json_out($status);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

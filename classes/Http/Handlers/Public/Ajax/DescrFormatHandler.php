<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;
use Pu239\Torrent;

final class DescrFormatHandler
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
            /** @var Torrent $torrents */
            $torrents = $container->get(Torrent::class);

            check_user_status();

            $tid = (int) ($_POST['tid'] ?? 0);
            // TODO(2025): csrf
            if (!empty($tid)) {
                $descr = $torrents->format_descr($tid);
                if (!empty($descr)) {
                    json_out(['descr' => $descr]);
                }
            }

            json_out([
                'fail' => 'invalid',
            ]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

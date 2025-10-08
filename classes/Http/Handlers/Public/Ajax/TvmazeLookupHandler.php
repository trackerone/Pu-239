<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Torrent;

final class TvmazeLookupHandler
{
    /** @param array<string, mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5
        try {
            global $container;

            /** @var Torrent $torrents */
            $torrents = $container->get(Torrent::class);

            $user = \check_user_status();

            // TODO(2025): add CSRF verification
            $tvmazeId = (int) ($_POST['tvmazeid'] ?? 0);
            $torrentId = (int) ($_POST['tid'] ?? 0);
            $name = isset($_POST['name']) ? \htmlsafechars((string) $_POST['name']) : null;

            if ($user === false || $tvmazeId <= 0 || $torrentId <= 0) {
                \json_out(['fail' => 'invalid']);

                return;
            }

            \preg_match('/S(\d+)E(\d+)/i', (string) $name, $match);
            $episode = !empty($match[2]) ? (int) $match[2] : 0;
            $season = !empty($match[1]) ? (int) $match[1] : 0;

            $poster = $torrents->get_items(['poster'], $torrentId);

            if (empty($poster)) {
                $poster = \get_image_by_id('tv', (string) $tvmazeId, 'poster', $season);
            }

            $poster = $poster ?: '';
            $tvmazeData = \tvmaze($tvmazeId, $torrentId, $season, $episode, $poster);

            if (!empty($tvmazeData)) {
                \json_out(['content' => $tvmazeData]);

                return;
            }

            \json_out(['fail' => 'invalid']);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

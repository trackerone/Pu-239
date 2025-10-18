<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-17T04:20:10Z via handler-convert offset=170 size=5

namespace PU239\Http\Handlers\Public;

use Pu239\Torrent;
use Pu239\User;
use RuntimeException;

final class ScrapeHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-17T04:20:10Z via handler-convert offset=170 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            if (empty($_SERVER['QUERY_STRING'])) {
                err('Invalid request');
            }

            $queryPairs = explode('&', (string) $_SERVER['QUERY_STRING']);
            $_GET = []; // TODO(2025): centralise tracker query parsing
            foreach ($queryPairs as $pair) {
                $parts = explode('=', $pair, 2);
                $key = rawurldecode(trim($parts[0] ?? ''));
                $value = rawurldecode(trim($parts[1] ?? ''));
                if ($key === '') {
                    continue;
                }
                if (!isset($_GET[$key])) {
                    $_GET[$key] = $value;
                } elseif (!is_array($_GET[$key])) {
                    $_GET[$key] = [$_GET[$key], $value];
                } else {
                    $_GET[$key][] = $value;
                }
            }

            if (empty($_GET['torrent_pass']) || strlen((string) $_GET['torrent_pass']) !== 64) {
                err('torrent pass not valid, please redownload your torrent file');
            }

            $torrentPass = (string) $_GET['torrent_pass'];
            if ($torrentPass === '') {
                err('empty torrent pass');
            }

            /** @var User $userRepository */
            $userRepository = $container->get(User::class);
            $user = $userRepository->get_user_from_torrent_pass($torrentPass);

            if (empty($user) || ($user['status'] ?? 0) > 0 || ($user['downloadpos'] ?? 0) !== 1) {
                err('scrape user error');
            }

            $numHashes = 1;
            if (!empty($_GET['info_hash']) && is_array($_GET['info_hash'])) {
                $numHashes = count($_GET['info_hash']);
            } elseif (empty($_GET['info_hash'])) {
                $numHashes = 0;
            }

            /** @var Torrent $torrentRepository */
            $torrentRepository = $container->get(Torrent::class);
            $torrents = [];

            if ($numHashes < 1) {
                err('Scrape Error d5:filesdee');
            } elseif ($numHashes === 1) {
                $hash = is_array($_GET['info_hash']) ? (string) ($_GET['info_hash'][0] ?? '') : (string) $_GET['info_hash'];
                $torrent = $torrentRepository->get_torrent_from_hash($hash);
                if ($torrent) {
                    $torrents[$hash] = $torrent;
                }
            } else {
                foreach ((array) $_GET['info_hash'] as $hash) {
                    $hash = (string) $hash;
                    $torrent = $torrentRepository->get_torrent_from_hash($hash);
                    if ($torrent) {
                        $torrents[$hash] = $torrent;
                    }
                }
            }

            if (count($torrents) === 0) {
                err('torrent error');
            }

            $response = 'd5:filesd';
            foreach ($torrents as $infoHash => $torrent) {
                $seeders = (int) ($torrent['seeders'] ?? 0);
                $leechers = (int) ($torrent['leechers'] ?? 0);
                $timesCompleted = (int) ($torrent['times_completed'] ?? 0);
                $response .= '20:' . $infoHash . 'd8:completei' . $seeders . 'e10:downloadedi' . $timesCompleted . 'e10:';
                $response .= 'incompletei' . $leechers . 'ee';
            }
            $response .= 'ee';

            benc_resp_raw($response);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

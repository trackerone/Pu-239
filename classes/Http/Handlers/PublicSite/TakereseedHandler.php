<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:55:00Z via handler-convert offset=280 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PDO;
use PU239\Support\Audit;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;

use function dirname;

final class TakereseedHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:55:00Z via handler-convert offset=280 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var Message $messages */
            $messages = $container->get(Message::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $user = check_user_status();

            $pmScope = isset($_POST['pm_what']) && $_POST['pm_what'] === 'last10' ? 'last10' : 'owner';
            $reseedId = (int) ($_POST['reseedid'] ?? 0);
            $uploader = (int) ($_POST['uploader'] ?? 0);
            $name = $_POST['name'] ?? '';

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                // TODO(2025): add CSRF verification
            }

            $baseUrl = (string) $config->get('paths.baseurl');
            $torrentDetailsTtl = (int) $config->get('expires.torrent_details');
            $userCacheTtl = (int) $config->get('expires.user_cache');
            $bonusEnabled = (bool) $config->get('bonus.on');

            $now = TIME_NOW;
            $subject = 'Request reseed!';
            $messageBody = "@{$user['username']} asked for a reseed on [url={$baseUrl}/details.php?id={$reseedId}][class=has-text-success]{$name}[/class][/url]![br][br]Thank You!";

            $recipients = [];
            if ($pmScope === 'last10') {
                $rows = $db->fetchAll(
                    "SELECT s.userid FROM snatched AS s WHERE s.torrentid = :torrent_id AND s.seeder = 'yes' LIMIT 10",
                    [
                        ':torrent_id' => $reseedId,
                    ],
                );
                foreach ($rows as $row) {
                    $recipients[] = (int) ($row['userid'] ?? 0);
                }
            } elseif ($uploader > 0) {
                $recipients[] = $uploader;
            }

            if ($recipients !== []) {
                $payload = [];
                foreach ($recipients as $receiver) {
                    $payload[] = [
                        'receiver' => $receiver,
                        'added' => $now,
                        'msg' => $messageBody,
                        'subject' => $subject,
                    ];
                }
                $messages->insert($payload);
                $session->set('is-success', 'PM was sent! Now wait for a seeder!');
            } else {
                $session->set('is-warning', 'There were no users to PM!');
            }

            $db->run(
                'UPDATE torrents SET last_reseed = :last_reseed WHERE id = :id',
                [
                    ':last_reseed' => [$now, PDO::PARAM_INT],
                    ':id' => $reseedId,
                ],
            );

            $cache->update_row(
                'torrent_details_' . $reseedId,
                [
                    'last_reseed' => $now,
                ],
                $torrentDetailsTtl,
            );

            if ($bonusEnabled) {
                $db->run(
                    'UPDATE users SET seedbonus = seedbonus - 10.0 WHERE id = :id',
                    [
                        ':id' => $user['id'],
                    ],
                );
                $cache->update_row(
                    'user_' . $user['id'],
                    [
                        'seedbonus' => ($user['seedbonus'] ?? 0) - 10,
                    ],
                    $userCacheTtl,
                );
            }

            Audit::log(
                $user['id'] ?? null,
                'torrent.moderate',
                [
                    'id' => $reseedId,
                    'op' => 'reseed.request',
                    'pm_scope' => $pmScope,
                ],
            );

            header("Refresh: 0; url={$baseUrl}/details.php?id={$reseedId}");
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

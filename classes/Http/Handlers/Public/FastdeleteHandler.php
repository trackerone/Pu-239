<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-17T03:42:40Z via handler-convert offset=165 size=5

namespace PU239\Http\Handlers\Public;

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;
use Pu239\Torrent;

final class FastdeleteHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-17T03:42:40Z via handler-convert offset=165 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var Torrent $torrents */
            $torrents = $container->get(Torrent::class);

            $baseUrl = (string) $config->get('paths.baseurl');

            $user = check_user_status();

            if (($user['class'] ?? 0) < UC_STAFF) {
                stderr(_('Error'), _('You do not have permission to do this.'));
            }

            $id = (int) ($_GET['id'] ?? 0);
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $torrent = $db->fetch(
                'SELECT t.id, t.info_hash, t.owner, t.name, t.added, u.seedbonus
                    FROM torrents AS t
                    LEFT JOIN users AS u ON u.id = t.owner
                    WHERE t.id = :id',
                ['id' => [$id, \PDO::PARAM_INT]]
            );

            if ($torrent === null) {
                stderr(_('Error'), _('Something went wrong!'));
            }

            $sure = isset($_GET['sure']) && (int) $_GET['sure'] === 1;
            if (!$sure) {
                $returnTo = isset($_GET['returnto']) ? '&amp;returnto=' . urlencode((string) $_GET['returnto']) : '';
                stderr(
                    _('Sanity Check'),
                    _fe(
                        'Are you sure you want to delete this torrent?<br>Click {0}here{1} if you are.',
                        "<a href='{$baseUrl}/fastdelete.php?id={$id}&sure=1{$returnTo}' class='is-link'>",
                        '</a>'
                    )
                );
            }

            $torrents->remove_torrent((string) $torrent['info_hash']);
            $torrents->delete_by_id((int) $torrent['id']);

            if ((int) $user['id'] !== (int) $torrent['owner']) {
                $db->run(
                    'INSERT INTO messages (sender, receiver, added, msg) VALUES (2, :receiver, :added, :msg)',
                    [
                        'receiver' => [(int) $torrent['owner'], \PDO::PARAM_INT],
                        'added' => [TIME_NOW, \PDO::PARAM_INT],
                        'msg' => [
                            _fe('Your upload {0} has been deleted by {1}', "[b]{$torrent['name']}[/b]", $user['username']),
                            \PDO::PARAM_STR,
                        ],
                    ]
                );
            }

            write_log(_fe('Torrent {0} was deleted by {1}', $torrent['name'], $user['username']));
            audit_log($user['id'] ?? null, 'torrent.moderate', ['id' => $torrent['id'] ?? null, 'op' => 'delete']);

            if ((bool) $config->get('bonus.on')) {
                $dt = TIME_NOW - (14 * 86400);
                if ((int) $torrent['added'] > $dt) {
                    $seedbonus = (float) ($torrent['seedbonus'] ?? 0) - (float) $config->get('bonus.per_delete');
                    $db->run(
                        'UPDATE users SET seedbonus = :seedbonus WHERE id = :id',
                        [
                            'seedbonus' => [$seedbonus, \PDO::PARAM_STR],
                            'id' => [(int) $torrent['owner'], \PDO::PARAM_INT],
                        ]
                    );
                    $cache->update_row(
                        'user_' . (int) $torrent['owner'],
                        ['seedbonus' => $seedbonus],
                        (int) $config->get('expires.user_cache')
                    );
                }
            }

            $session->set('is-success', _fe("Torrent deleted\n[b]{0}[/b]", format_comment($torrent['name'])));
            if (isset($_GET['returnto'])) {
                header('Location: ' . $baseUrl . (string) $_GET['returnto']);

                return;
            }

            header('Location: ' . $baseUrl);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

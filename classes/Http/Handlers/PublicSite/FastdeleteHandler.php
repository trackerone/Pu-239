<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T17:13:49Z via handler-convert offset=290 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;
use Pu239\Torrent;
use function dirname;
use function is_string;

final class FastdeleteHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T17:13:49Z via handler-convert offset=290 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            $baseUrl = (string) $config->get('paths.baseurl');

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            $user = check_user_status();

            if ($user['class'] < UC_STAFF) {
                stderr(_('Error'), _('You do not have permission to do this.'));
            }

            if (!isset($_GET['id']) || !is_valid_id((int) $_GET['id'])) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $id = (int) $_GET['id'];
            $tid = $db->fetch(
                'SELECT t.id, t.info_hash, t.owner, t.name, t.added, u.seedbonus
        FROM torrents AS t
        LEFT JOIN users AS u ON u.id = t.owner
        WHERE t.id = :id',
                [':id' => $id]
            );

            if (!$tid) {
                stderr(_('Error'), _('Something went wrong!'));
            }

            $sure = isset($_GET['sure']) ? (int) $_GET['sure'] : 0;
            if (!$sure) {
                $returnto = !empty($_GET['returnto']) ? '&amp;returnto=' . urlencode((string) $_GET['returnto']) : '';
                stderr(
                    _('Sanity Check'),
                    _fe(
                        'Are you sure you want to delete this torrent?<br>Click {0}here{1} if you are.',
                        "<a href='{$baseUrl}/fastdelete.php?id={$_GET['id']}&sure=1{$returnto}' class='is-link'>",
                        '</a>'
                    )
                );
            }

            /** @var Torrent $torrents_class */
            $torrents_class = $container->get(Torrent::class);
            $torrents_class->remove_torrent($tid['info_hash']);
            $torrents_class->delete_by_id($tid['id']);
            if ($user['id'] != $tid['owner']) {
                $msg = _fe('Your upload {0} has been deleted by {1}', "[b]{$tid['name']}[/b]", $user['username']);
                $db->run(
                    'INSERT INTO messages (sender, receiver, added, msg) VALUES (2, :receiver, :added, :msg)',
                    [
                        ':receiver' => (int) $tid['owner'],
                        ':added' => TIME_NOW,
                        ':msg' => $msg,
                    ]
                );
            }
            write_log(_fe('Torrent {0} was deleted by {1}', $tid['name'], $user['username']));
            audit_log($user['id'] ?? null, 'torrent.moderate', ['id' => $tid['id'] ?? null, 'op' => 'delete']);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            if ($config->get('bonus.on')) {
                $dt = TIME_NOW - (14 * 86400);
                if ($tid['added'] > $dt) {
                    $sb = $tid['seedbonus'] - $config->get('bonus.per_delete');
                    $db->run(
                        'UPDATE users SET seedbonus = :seedbonus WHERE id = :id',
                        [
                            ':seedbonus' => $sb,
                            ':id' => (int) $tid['owner'],
                        ]
                    );
                    $cache->update_row('user_' . $tid['owner'], [
                        'seedbonus' => $sb,
                    ], $config->get('expires.user_cache'));
                }
            }
            /** @var Session $session */
            $session = $container->get(Session::class);
            $session->set('is-success', _fe("Torrent deleted\n[b]{0}[/b]", format_comment($tid['name'])));
            if (isset($_GET['returnto']) && is_string($_GET['returnto'])) {
                header('Location: ' . $baseUrl . $_GET['returnto']);
            } else {
                header('Location: ' . $baseUrl);
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

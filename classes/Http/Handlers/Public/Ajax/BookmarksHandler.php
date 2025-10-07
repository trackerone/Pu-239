<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=65-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Cache;
use Pu239\Database;

final class BookmarksHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=65-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();

            if ($user === false) {
                json_out(['fail' => 'csrf']);

                return;
            }

            // TODO(2025): csrf
            $torrentId = (int) ($_POST['tid'] ?? 0);
            $togglePrivate = ($_POST['private'] ?? '') === 'true';
            $remove = $_POST['remove'] ?? 'false';

            if ($torrentId <= 0) {
                json_out(['fail' => 'invalid']);

                return;
            }

            $bindings = [
                'torrentId' => $torrentId,
                'userId' => (int) $user['id'],
            ];

            if ($togglePrivate) {
                $bookmark = $db->row(
                    'SELECT private FROM bookmarks WHERE torrentid = :torrentId AND userid = :userId',
                    $bindings,
                );

                if ($bookmark === null) {
                    json_out(['fail' => 'missing']);

                    return;
                }

                $current = $bookmark['private'] === 'yes';
                $nextValue = $current ? 'no' : 'yes';
                $label = $current ? _('Mark Bookmark Private!') : _('Mark Bookmark Public!');

                $db->run(
                    'UPDATE bookmarks SET private = :private WHERE torrentid = :torrentId AND userid = :userId',
                    $bindings + ['private' => $nextValue],
                );

                $cache->delete('bookmarks_' . $user['id']);

                json_out([
                    'bookmark' => $nextValue,
                    'content' => 'private',
                    'text' => $label,
                    'tid' => $torrentId,
                    'remove' => 'false',
                ]);

                return;
            }

            $bookmark = $db->row(
                'SELECT id FROM bookmarks WHERE torrentid = :torrentId AND userid = :userId',
                $bindings,
            );

            if ($bookmark !== null) {
                $db->run('DELETE FROM bookmarks WHERE id = :id', ['id' => (int) $bookmark['id']]);
                $cache->delete('bookmarks_' . $user['id']);

                json_out([
                    'content' => 'deleted',
                    'text' => _('Add Bookmark'),
                    'tid' => $torrentId,
                    'remove' => $remove,
                ]);

                return;
            }

            $db->run(
                'INSERT INTO bookmarks (userid, torrentid, private, added) VALUES (:userId, :torrentId, :private, :added)',
                [
                    'userId' => (int) $user['id'],
                    'torrentId' => $torrentId,
                    'private' => 'no',
                    'added' => [TIME_NOW, \PDO::PARAM_INT],
                ],
            );

            $cache->delete('bookmarks_' . $user['id']);

            json_out([
                'content' => 'added',
                'text' => _('Delete Bookmark'),
                'tid' => $torrentId,
                'remove' => $remove,
            ]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

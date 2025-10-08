<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Public\Ajax;

use InvalidArgumentException;
use PDOException;
use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;

final class LikeHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08T04:13:01Z via codex handler conversion
        try {
            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            unset($config);

            /** @var Database $db */
            $db = $container->get(Database::class);

            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $fields = [
                'comment' => 'comments',
                'topic' => 'topics',
                'post' => 'posts',
                'usercomment' => 'usercomments',
                'request' => 'requests',
                'offer' => 'offers',
                'torrent' => 'torrents',
            ];

            $user = \check_user_status('like');

            if (!empty($user) && \is_array($user)) {
                $this->commentLikeUnlike($fields, $user, $db, $cache);
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    /**
     * @param array<string, string> $fields
     * @param array<string, mixed> $user
     */
    private function commentLikeUnlike(array $fields, array $user, Database $db, Cache $cache): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $type = \mb_substr(\trim((string) ($_POST['type'] ?? '')), 0, 12);
        $current = \mb_substr(\trim((string) ($_POST['current'] ?? '')), 0, 10);

        // TODO(2025): csrf on POST where missing

        if (!isset($fields[$type])) {
            \json_out(['label' => \_('Invalid Data Type')]);
            return;
        }

        if ($id <= 0) {
            \json_out(['label' => \_('Invalid ID')]);
            return;
        }

        if ($type === 'torrent') {
            $type = 'comment';
        }

        $table = match ($type) {
            'post' => 'posts',
            'comment' => 'comments',
            'topic' => 'topics',
            'usercomment' => 'usercomments',
            'request' => 'requests',
            'offer' => 'offers',
            'torrent' => 'torrents',
            default => throw new InvalidArgumentException('Invalid like type'),
        };

        $exists = (int) $db->fetchValue(
            "SELECT COUNT(id) FROM likes WHERE user_id = :uid AND {$type}_id = :id",
            [
                'uid' => (int) $user['id'],
                'id' => $id,
            ],
        );

        if ($exists === 0 && $current === 'Like') {
            try {
                $db->tx(function (Database $txDb) use ($type, $id, $user, $table): void {
                    $txDb->run(
                        "INSERT INTO likes ({$type}_id, user_id) VALUES (:id, :uid)",
                        [
                            'id' => $id,
                            'uid' => (int) $user['id'],
                        ],
                    );
                    $txDb->run(
                        "UPDATE {$table} SET user_likes = user_likes + 1 WHERE id = :id",
                        ['id' => $id],
                    );
                });
            } catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                    throw $exception;
                }
            }
            $cache->delete("{$type}_user_likes_" . $id);
            $cache->delete('latest_comments_');
            $label = 'Unlike';
            $list = 'you like this';
        } elseif ($exists === 1 && $current === 'Unlike') {
            $db->tx(function (Database $txDb) use ($type, $id, $user, $table): void {
                $txDb->run(
                    "DELETE FROM likes WHERE {$type}_id = :id AND user_id = :uid",
                    [
                        'id' => $id,
                        'uid' => (int) $user['id'],
                    ],
                );
                $txDb->run(
                    "UPDATE {$table} SET user_likes = user_likes - 1 WHERE id = :id",
                    ['id' => $id],
                );
            });
            $cache->delete("{$type}_user_likes_" . $id);
            $cache->delete('latest_comments_');
            $label = 'Like';
            $list = '';
        } elseif ($exists === 1 && $current === 'Like') {
            $label = \_('Unlike');
            $list = \_('you like this');
        } else {
            $label = \_('you lost me');
            $list = '';
        }

        $rows = $db->toArray(
            "SELECT user_id FROM likes WHERE {$type}_id = :id AND user_id != :uid",
            [
                'id' => $id,
                'uid' => (int) $user['id'],
            ],
        );

        $names = [];
        foreach ($rows as $row) {
            $names[] = \format_username((int) $row['user_id']);
        }

        if (!empty($names)) {
            $list = \implode(', ', $names) . (!empty($list) ? ' and ' . $list : ' like' . \plural(\count($names)) . ' this');
        }

        \json_out(['label' => $label, 'list' => $list, 'class' => "tot-$id"]);
    }
}

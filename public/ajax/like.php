<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status('like');

$fields = [
    'comment' => 'comments',
    'topic' => 'topics',
    'post' => 'posts',
    'usercomment' => 'usercomments',
    'request' => 'requests',
    'offer' => 'offers',
    'torrent' => 'torrents',
];

if (!empty($user) && is_array($user)) {
    comment_like_unlike($fields, $user);
}

/**
 * @param array $fields
 * @param array $user
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function comment_like_unlike(array $fields, array $user)
{
    global $container, $db;

    header('Content-Type: application/json; charset=utf-8');

    $id = (int) ($_POST['id'] ?? 0);
    $type = mb_substr(trim((string) ($_POST['type'] ?? '')), 0, 12);
    $current = mb_substr(trim((string) ($_POST['current'] ?? '')), 0, 10);

    // TODO(2025): csrf on POST where missing

    if (!isset($fields[$type])) {
        echo json_encode(['label' => _('Invalid Data Type')], JSON_THROW_ON_ERROR);
        app_halt('Exit called');
    }

    if ($id <= 0) {
        echo json_encode(['label' => _('Invalid ID')], JSON_THROW_ON_ERROR);
        app_halt('Exit called');
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
        ]
    );
    $cache = $container->get(Cache::class);

    if ($exists === 0 && $current === 'Like') {
        try {
            $db->tx(function (Database $db) use ($type, $id, $user): void {
                $db->run(
                    "INSERT INTO likes ({$type}_id, user_id) VALUES (:id, :uid)",
                    [
                        'id' => $id,
                        'uid' => (int) $user['id'],
                    ]
                );
                $db->run(
                    "UPDATE {$table} SET user_likes = user_likes + 1 WHERE id = :id",
                    ['id' => $id]
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
        $db->tx(function (Database $db) use ($type, $id, $user): void {
            $db->run(
                "DELETE FROM likes WHERE {$type}_id = :id AND user_id = :uid",
                [
                    'id' => $id,
                    'uid' => (int) $user['id'],
                ]
            );
            $db->run(
                "UPDATE {$table} SET user_likes = user_likes - 1 WHERE id = :id",
                ['id' => $id]
            );
        });
        $cache->delete("{$type}_user_likes_" . $id);
        $cache->delete('latest_comments_');
        $label = 'Like';
        $list = '';
    } elseif ($exists === 1 && $current === 'Like') {
        $label = _('Unlike');
        $list = _('you like this');
    } else {
        $label = _('you lost me');
        $list = '';
    }

    $rows = $db->toArray(
        "SELECT user_id FROM likes WHERE {$type}_id = :id AND user_id != :uid",
        [
            'id' => $id,
            'uid' => (int) $user['id'],
        ]
    );
    $names = [];
    foreach ($rows as $row) {
        $names[] = format_username((int) $row['user_id']);
    }
    if (!empty($names)) {
        $list = implode(', ', $names) . (!empty($list) ? ' and ' . $list : ' like' . plural(count($names)) . ' this');
    }

    echo json_encode(['label' => $label, 'list' => $list, 'class' => "tot-$id"], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

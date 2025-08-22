<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Pu239\Torrent;

/**
 * @param $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws UnbegunTransaction
 * @throws \Delight\Auth\AuthError
 * @throws \Delight\Auth\NotLoggedInException
 * @throws \Envms\FluentPDO\Exception
 * @throws \PHPMailer\PHPMailer\Exception
 * @throws \Spatie\Image\Exceptions\InvalidManipulation
 */
function delete_torrents_update($data)
{
    global $container;

    $time_start = microtime(true);
    $hours = 2;
    $dt = get_date(TIME_NOW - ($hours * 3600), 'MYSQL', 1, 0);
    $fluent = $container->get(Database::class);
    $never_seeded = $fluent->from('torrents')
                           ->select(null)
                           ->select('id')
                           ->select('owner')
                           ->select('name')
                           ->select('info_hash')
                           ->where('UNIX_TIMESTAMP(last_action) = added')
                           ->where('last_action < ?', $dt)
                           ->where('seeders = 0')
                           ->where('leechers = 0');

    $days = 45;
    $dt = get_date(TIME_NOW - ($days * 86400), 'MYSQL', 1, 0);
    $dead = $fluent->from('torrents')
                   ->select(null)
                   ->select('id')
                   ->select('owner')
                   ->select('name')
                   ->select('info_hash')
                   ->where('last_action < ?', $dt)
                   ->where('seeders = 0')
                   ->where('leechers = 0');

    $values = [];
    $torrents_class = $container->get(Torrent::class);
    foreach ($never_seeded as $torrent) {
        $torrents_class->delete_by_id((int) $torrent['id']);
        $torrents_class->remove_torrent($torrent['info_hash']);
        $msg = 'Torrent ' . (int) $torrent['id'] . ' (' . htmlsafechars($torrent['name']) . ") was deleted by system (never seeded after $hours hours)";
        $values[] = [
            'receiver' => $torrent['owner'],
            'added' => TIME_NOW,
            'msg' => $msg,
            'subject' => 'Torrent Deleted [Dead]',
        ];
        if ($data['clean_log']) {
            write_log($msg);
        }
    }

    foreach ($dead as $torrent) {
        $torrents_class->delete_by_id((int) $torrent['id']);
        $torrents_class->remove_torrent($torrent['info_hash']);
        $msg = 'Torrent ' . (int) $torrent['id'] . ' (' . htmlsafechars($torrent['name']) . ") was deleted by system (older than $days days and no seeders)";
        $values[] = [
            'receiver' => $torrent['owner'],
            'added' => TIME_NOW,
            'msg' => $msg,
            'subject' => 'Torrent Deleted [Dead]',
        ];
        if ($data['clean_log']) {
            write_log($msg);
        }
    }

    $count = count($values);
    if ($count > 0) {
        $cache = $container->get(Cache::class);
        $cache->delete('torrent_poster_count_');
        $messages_class = $container->get(Message::class);
        $messages_class->insert($values);
    }

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Delete Old Torrents Cleanup: Completed' . $text);
    }
}

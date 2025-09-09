<?php

declare(strict_types = 1);

require_once __DIR__ . '/runtime_safe.php';
require_once __DIR__ . '/bootstrap_pdo.php';

use Pu239\Database;
use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Comment;
use Pu239\Torrent;

$db = $container->get(Database::class);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'function_users.php';
global $container, $site_config;

$cache = $container->get(Cache::class);
$run = '';
if (!empty($argv[1]) && $argv[1] === 'force') {
    $cache->delete('cleanup_check_');
    $cache->delete('tfreak_cron_');
} elseif (!empty($argv[1])) {
    $run = trim($argv[1]);
}

echo "===================================================\n";
echo get_date((int) TIME_NOW, 'LONG', 1, 0) . "\n";

$cleanup_check = $cache->get('cleanup_check_');
if (user_exists($site_config['chatbot']['id']) && ($cleanup_check === false || is_null($cleanup_check)) || !empty($run)) {
    autoclean($run);
} else {
    echo "Already running.\n";
}

/**
 * @param string $run
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function autoclean(string $run)
{
    global $container, $site_config;

    $cache = $container->get(Cache::class);
    $cache->set('cleanup_check_', 'running', 600);
    $now = TIME_NOW;
    // using $db (ExtendedPdo)
    if (!empty($run)) {
        $sql = 'SELECT * FROM cleanup WHERE function_name = :run';
        $params = ['run' => $run];
    } else {
        $sql = 'SELECT * FROM cleanup WHERE clean_on = 1 AND function_name != :function AND clean_time < :now ORDER BY clean_time ASC, clean_increment ASC';
        $params = ['function' => 'funds_table_update', 'now' => $now];
    }
    $query = $db->fetchAll($sql, $params);
    if (!$query) {
        echo "Nothing to process, all caught up.\n";
    } else {
        foreach ($query as $row) {
            if ($row['clean_id']) {
                $next_clean = ceil(TIME_NOW / $row['clean_increment']) * $row['clean_increment'];
                $set = [
                    'clean_time' => $next_clean,
                ];
                $sql = "UPDATE cleanup SET /* columns */ WHERE clean_id = :clean_id";
$db->perform($sql, array_merge($set, ['clean_id' => $row['clean_id']]));

                if (file_exists(CLEAN_DIR . $row['clean_file'])) {
                    require_once CLEAN_DIR . $row['clean_file'];
                    if (function_exists($row['function_name'])) {
                        echo "Processing {$row['function_name']}\n";
                        $row['function_name']($row);
                    }
                }
            }
        }
    }
    $cache->delete('cleanup_check_');

    if ($site_config['newsrss']['tfreak'] || $site_config['newsrss']['github'] || $site_config['newsrss']['foxnews']) {
        echo "Newsrss Starting\n";
        $tfreak_cron = $cache->get('tfreak_cron_');
        if ($tfreak_cron === false || is_null($tfreak_cron)) {
            $query = $db->fetchAll('SELECT link FROM newsrss');

            foreach ($query as $tfreak_new) {
                $tfreak_news[] = $tfreak_new['link'];
            }

            $cache->set('tfreak_cron_', TIME_NOW, 30);
            require_once INCL_DIR . 'function_newsrss.php';
            if (empty($tfreak_news)) {
                github_shout();
                foxnews_shout();
                tfreak_shout();
            } else {
                github_shout($tfreak_news);
                foxnews_shout($tfreak_news);
                tfreak_shout($tfreak_news);
            }
        }
        echo "Newsrss Finished\n";
    } else {
        echo "Newsrss disabled\n";
    }

    $torrent = $container->get(Torrent::class);
    $torrent->get_latest_scroller();
    $torrent->get_latest_slider();
    $torrent->get_staff_picks();
    $torrent->get_top();
    $torrent->get_latest([]);
    $torrent->get_latest($site_config['categories']['tv']);
    $torrent->get_latest($site_config['categories']['movie']);
    $torrent->get_mow();
    $torrent->get_plots();
    $comment = $container->get(Comment::class);
    $comment->get_comments();
}

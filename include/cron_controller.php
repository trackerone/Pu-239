<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Comment;
use Pu239\Database;
use Pu239\Torrent;

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
 * @throws \Envms\FluentPDO\Exception
 */
function autoclean(string $run)
{
    global $container, $site_config;

    $cache = $container->get(Cache::class);
    $cache->set('cleanup_check_', 'running', 600);
    $now = TIME_NOW;
    $fluent = $container->get(Database::class);
    $query = $fluent->from('cleanup');
    if (!empty($run)) {
        $query = $query->where('function_name = ?', $run);
    } else {
        $query = $query->where('clean_on = 1')
                       ->where('function_name != ?', 'funds_table_update')
                       ->where('clean_time < ?', $now)
                       ->orderBy('clean_time ASC')
                       ->orderBy('clean_increment ASC');
    }
    $query = $query->fetchAll();
    if (!$query) {
        echo "Nothing to process, all caught up.\n";
    } else {
        foreach ($query as $row) {
            if ($row['clean_id']) {
                $next_clean = ceil(TIME_NOW / $row['clean_increment']) * $row['clean_increment'];
                $set = [
                    'clean_time' => $next_clean,
                ];
                $fluent->update('cleanup')
                       ->set($set)
                       ->where('clean_id=?', $row['clean_id'])
                       ->execute();

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
            $query = $fluent->from('newsrss')
                            ->select(null)
                            ->select('link');

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

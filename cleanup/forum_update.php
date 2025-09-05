<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

/**
 * @param $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function forum_update($data)
{
    global $container;

    $time_start = microtime(true);
    $fluent = $container->get(Database::class);
    $sql = "DELETE FROM now_viewing WHERE ...";
$this->db->perform($sql, [/* params */]);;

    $forums = $fluent->from('forums')
                     ->select(null)
                     ->select('forums.id')
                     ->select('COUNT(DISTINCT topics.id) AS topics')
                     ->select('COUNT(posts.id) AS posts')
                     ->leftJoin('topics ON forums.id = topics.forum_id')
                     ->leftJoin('posts ON topics.id = posts.topic_id')
                     ->groupBy('forums.id');

    foreach ($forums as $forum) {
        $forum['posts'] = $forum['topics'] > 0 ? $forum['posts'] : 0;
        $set = [
            'post_count' => $forum['posts'],
            'topic_count' => $forum['topics'],
        ];
        $sql = "UPDATE forums SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
    }
    $topics = $fluent->from('topics')
                     ->select(null)
                     ->select('id')
                     ->fetchAll();

    foreach ($topics as $topic) {
        $last_post = $fluent->from('posts')
                            ->select(null)
                            ->select('id')
                            ->select('added')
                            ->where('topic_id = ?', $topic['id'])
                            ->orderBy('added DESC')
                            ->limit(1)
                            ->fetch();

        if (empty($last_post['id'])) {
            $sql = "DELETE FROM topics WHERE ...";
$this->db->perform($sql, [/* params */]);;
        } else {
            $count = $fluent->from('posts')
                            ->select(null)
                            ->select('COUNT(id) AS count')
                            ->where('topic_id = ?', $topic['id'])
                            ->fetch('count');
            $set = [
                'last_post' => $last_post['id'],
                'post_count' => $count,
            ];
            $sql = "UPDATE topics SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
        }
    }

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Forum Cleanup: Completed' . $text);
    }
}

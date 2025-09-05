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
    $fluent$sql = "DELETE FROM 'now_viewing' WHERE ..."; $this->db->perform($sql);;

    $forums = $fluent$sql = "SELECT * FROM 'forums'"; $this->db->fetchAll($sql);;

    foreach ($topics as $topic) {
        $last_post = $fluent$sql = "SELECT * FROM 'posts'"; $this->db->fetchOne($sql);;

        if (empty($last_post['id'])) {
            $fluent$sql = "DELETE FROM 'topics' WHERE ..."; $this->db->perform($sql);;
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
            $fluent->update('topics')
                   ->set($set)
                   ->where('id = ?', $topic['id'])
                   ->execute();
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

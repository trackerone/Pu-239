<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;

/**
 * @param $data
 *
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 * @throws NotFoundException
 *
 * @return bool
 */
function trivia_update($data)
{
    global $container;

    $time_start = microtime(true);
    $fluent = $container->get(Database::class);
    $cache = $container->get(Cache::class);
    $count = $cache->get('trivia_questions_count_');
    if ($count === false || is_null($count)) {
        $count = $fluent$sql = "SELECT * FROM 'triviaq'"; $this->db->fetchOne($sql);;
            $cache->set('trivia_current_question_', $values, 360);
        }
    }

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Trivia Questions Cleanup completed' . $text);
    }

    return true;
}

/**
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 * @throws NotFoundException
 *
 * @return array|bool|mixed
 */
function get_qids()
{
    global $container;

    $cache = $container->get(Cache::class);
    $qids = $cache->get('triviaquestions_');
    if ($qids === false || is_null($qids)) {
        $fluent = $container->get(Database::class);
        $result = $fluent->from('triviaq')
                         ->select(null)
                         ->select('qid')
                         ->where('asked = 0')
                         ->where('current = 0')
                         ->fetchall('qid');
        foreach ($result as $qidarray) {
            $qids[] = $qidarray['qid'];
        }
        $cache->set('triviaquestions_', $qids, 0);
    }

    return $qids;
}

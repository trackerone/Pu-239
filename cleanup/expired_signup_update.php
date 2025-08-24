<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;

/**
 * @param $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function expired_signup_update($data)
{
    global $container;
$db = $container->get(Database::class);, $site_config;

    $time_start = microtime(true);
    $dt = TIME_NOW;
    $deadtime = $dt - $site_config['signup']['timeout'];
    $rows = $db->fetchAll("SELECT id, username FROM users WHERE status != 0 AND registered < $deadtime ORDER BY username DESC");
    $cache = $container->get(Cache::class);
    if (!empty($rows)) {
        foreach ($rows as $arr) {
            $userid = $arr['id'];
            $res_del = $db->run('DELETE FROM users WHERE id = :id', [':id' => $userid]) or sqlerr(__FILE__, __LINE__);
            $cache->delete('user_' . $userid);
            if ($data['clean_log']) {
                write_log("Expired Signup Cleanup: User: {$arr['username']} was deleted");
            }
        }
    }

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Expired Signup Completed' . $text);
    }
}

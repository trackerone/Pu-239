<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

use Pu239\Cache;
use Pu239\Message;

/**
 * @param $data
 *
 * @throws Exception
 */
function achievement_sig_update($data)
{
    global $container;
$db = $container->get(Database::class);, $site_config;

    $time_start = microtime(true);
    $rows = $db->fetchAll('SELECT userid, sigset FROM usersachiev WHERE sigset = 1 AND sigach = 0');
    $msgs_buffer = $usersachiev_buffer = $achievements_buffer = [];
    if (mysqli_num_rows($res) > 0) {
        $subject = 'New Achievement Earned!';
        $msg = 'Congratulations, you have just earned the [b]Signature Setter[/b] achievement. :) [img]' . $site_config['paths']['images_baseurl'] . 'achievements/signature.png[/img]';
        $dt = TIME_NOW;
        $cache = $container->get(Cache::class);
        foreach ($rows as $arr) {
            $points = random_int(1, 3);
            $msgs_buffer[] = [
                'receiver' => $arr['userid'],
                'added' => $dt,
                'msg' => $msg,
                'subject' => $subject,
            ];
            $achievements_buffer[] = '(' . $arr['userid'] . ', ' . $dt . ', \'Signature Setter\', \'signature.png\' , \'User has successfully set a signature on profile settings.\')';
            $usersachiev_buffer[] = '(' . $arr['userid'] . ',1, ' . $points . ')';
            $cache->delete('user_' . $arr['userid']);
        }
        $count = count($achievements_buffer);
        if ($count > 0) {
            $messages_class = $container->get(Message::class);
            $messages_class->insert($msgs_buffer);
            $db->run(');
            $db->run(');
        }
        $time_end = microtime(true);
        $run_time = $time_end - $time_start;
        $text = " Run time: $run_time seconds";
        echo $text . "\n";
        if ($data['clean_log']) {
            write_log('Achievements Cleanup: Signature Setter Completed. Signature Setter Achievements awarded to - ' . $count . ' Member(s).' . $text);
        }
        unset($usersachiev_buffer, $achievement_buffer, $msgs_buffer, $count);
    }
}

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
function achievement_avatar_update($data)
{
    global $container;
$db = $container->get(Database::class);, $site_config;

    $cache = $container->get(Cache::class);
    $time_start = microtime(true);
    $rows = $db->fetchAll('SELECT userid, avatarset FROM usersachiev WHERE avatarset = 1 AND avatarach = 0');
    $msgs_buffer = $usersachiev_buffer = $achievements_buffer = [];
    if (mysqli_num_rows($res) > 0) {
        $subject = 'New Achievement Earned!';
        $msg = 'Congratulations, you have just earned the [b]Avatar Setter[/b] achievement. :) [img]' . $site_config['paths']['images_baseurl'] . 'achievements/piratesheep.png[/img]';
        $dt = TIME_NOW;
        foreach ($rows as $arr) {
            $points = random_int(1, 3);
            $msgs_buffer[] = [
                'receiver' => $arr['userid'],
                'added' => $dt,
                'msg' => $msg,
                'subject' => $subject,
            ];
            $achievements_buffer[] = '(' . $arr['userid'] . ', ' . $dt . ', \'Avatar Setter\', \'piratesheep.png\' , \'User has successfully set an avatar on profile settings.\')';
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
            write_log('Achievements Cleanup: Avatar Setter Completed. Avatar Achievements awarded to - ' . $count . ' Member(s).' . $text);
        }
    }
}

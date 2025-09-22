<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime_safe.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/**
 * @return bool|false|float|int|string
 *
 * @throws \PDOException
 * @throws DependencyException
 * @throws NotFoundException
 */
function happyHour($action)
{
    global $container, $config;
    $db = $container->get(Database::class);

    if ($action === 'generate') {
        $nextDay = \date('Y-m-d', TIME_NOW + 86400);
        $nextHoura = \random_int(0, 2);
        if ($nextHoura == 2) {
            $nextHourb = \random_int(0, 3);
        } else {
            $nextHourb = \random_int(0, 9);
        }
        $nextHour = $nextHoura.$nextHourb;
        $nextMina = \random_int(0, 5);
        $nextMinb = \random_int(0, 9);
        $nextMin = $nextMina.$nextMinb;
        $happyHour = $nextDay.' '.$nextHour.':'.$nextMin.'';

        return $happyHour;
    }
    $file = (string) $config->get('paths.happyhour');
    $happy = \json_decode(\file_get_contents($file), true);
    $happyHour = \strtotime($happy['time']);
    $happyDate = $happyHour;
    $curDate = TIME_NOW;
    $nextDate = $happyHour + 3600;
    if ($action === 'check') {
        if ($happyDate < $curDate && $nextDate >= $curDate) {
            return true;
        }
    }
    if ($action === 'time') {
        $timeLeft = \mkprettytime(($happyHour + 3600) - TIME_NOW);
        $timeLeft = \explode(':', $timeLeft);
        $time = ($timeLeft[0].' min : '.$timeLeft[1].' sec');

        return $time;
    }
    if ($action === 'todo') {
        $act = \random_int(1, 2);
        if ($act === 1) {
            $todo = 255;
        } else {
            $fluent = $db; // alias
            // $fluent removed — use $this->db (ExtendedPdo)
            $categories = $fluent->from('categories')
                ->select(null)
                ->select('id')
                ->fetchAll();

            \shuffle($categories);
            $todo = $categories[0];
        }

        return $todo;
    }
    if ($action === 'multiplier') {
        $multiplier = \random_int(11, 55) / 10;

        return $multiplier;
    }
}

/**
 * @param  null  $id
 * @return bool
 */
function happyCheck($action, $id = null)
{
    global $config;

    $file = (string) $config->get('paths.happyhour');
    $happy = \json_decode(\file_get_contents($file), true);
    $happycheck = (int) $happy['catid'];
    if ($action === 'check') {
        return $happycheck;
    } elseif ($action === 'checkid' && ($happycheck === 255 || $happycheck == $id)) {
        return true;
    }

    return false;
}

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function happyFile($act)
{
    global $config;

    $file = (string) $config->get('paths.happyhour');
    $happy = \json_decode(\file_get_contents($file), true);
    if ($act === 'set') {
        $array_happy = [
            'time' => \happyHour('generate'),
            'status' => '1',
            'catid' => \happyHour('todo'),
        ];
    } elseif ($act === 'reset') {
        $array_happy = [
            'time' => $happy['time'],
            'status' => '0',
            'catid' => $happy['catid'],
        ];
    }
    if (! empty($array_happy)) {
        $array_happy = \json_encode($array_happy);
        $file = (string) $config->get('paths.happyhour');
        $file = \fopen($file, 'w');
        \ftruncate($file, 0);
        \fwrite($file, $array_happy);
        \fclose($file);
    }
}

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function happyLog($userid, $torrentid, $multi)
{
    global $container;
    $db = $container->get(Database::class);

    $time = TIME_NOW;
    $db->run(
        'INSERT INTO happylog (userid, torrentid, multi, date) VALUES (:userid, :torrentid, :multi, :time)',
        [
            ':userid' => $userid,
            ':torrentid' => $torrentid,
            ':multi' => $multi,
            ':time' => $time,
        ],
    );
}

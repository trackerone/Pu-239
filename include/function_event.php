<?php
declare(strict_types=1);

$db = $container->get(Database::class);

require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;

/**
 * @param int    $modifier
 * @param int    $begin
 * @param int    $expires
 * @param int    $setby
 * @param string $title
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function set_event(int $modifier, int $begin, int $expires, int $setby, string $title)
{
    global $container;

    // $fluent removed — use $this->db (ExtendedPdo)
    $cache = $container->get(Cache::class);
    $values = [
        'modifier' => $modifier,
        'begin' => $begin,
        'expires' => $expires,
        'setby' => $setby,
        'title' => $title,
    ];
    $sql = "INSERT INTO events (/* columns */) VALUES (/* values */)";
$db->perform($sql, $values);

    $cache->set('site_events_', $values, $expires);
}

/**
 * @param int $expires
 * @param int $new_expires
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function update_event(int $expires, int $new_expires)
{
    global $container;

    // $fluent removed — use $this->db (ExtendedPdo)
    $cache = $container->get(Cache::class);

    $set = [
        'expires' => $new_expires,
    ];
    $sql = "UPDATE events SET /* columns */ WHERE expires = :expires";
$db->perform($sql, array_merge($set, ['expires' => $expires]));

    $free = [
        'modifier' => 0,
        'expires' => 0,
    ];

    $cache->set('site_events_', $free, $free['expires']);
}

/**
 *
 * @param bool $all
 *
 * @throws \PDOException
 * @throws DependencyException
 * @throws NotFoundException
 *
 * @return array|bool|mixed
 */
function get_event(bool $all)
{
    global $container;

    // $fluent removed — use $this->db (ExtendedPdo)
    $cache = $container->get(Cache::class);
    if (!$all) {
        $free = $cache->get('site_events_');
        if ($free === false || is_null($free)) {
            $free = $fluent->from('events')
                           ->where('expires>?', TIME_NOW)
                           ->orderBy('id DESC')
                           ->limit(1)
                           ->fetch();

            if (empty($free)) {
                $free = [
                    'modifier' => 0,
                    'expires' => 0,
                ];
            }
            $cache->set('site_events_', $free, $free['expires']);
        }
    } else {
        $free = $fluent->from('events')
                       ->orderBy('id DESC')
                       ->limit(20)
                       ->fetchAll();

        $free = array_reverse($free);
    }

    return $free;
}

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 *
 * @return array
 */
function get_events_data()
{
    $is_free = [
        'free' => 0,
        'double' => 0,
        'silver' => 0,
    ];
    $free = get_event(true);
    if (!empty($free)) {
        foreach ($free as $fl) {
            if (!empty($fl['modifier'])) {
                switch ($fl['modifier']) {
                    case 1:
                        $is_free['free'] = $fl['expires'];
                        break;

                    case 2:
                        $is_free['double'] = $fl['expires'];
                        break;

                    case 3:
                        $is_free['free'] = $fl['expires'];
                        $is_free['double'] = $fl['expires'];
                        break;

                    case 4:
                        $is_free['silver'] = $fl['expires'];
                        break;
                }
            }
        }
    }

    return $is_free;
}

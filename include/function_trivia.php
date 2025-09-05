<?php
require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_users.php';

/**
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 *
 * @return array
 */
function trivia_table()
{
    global $container;

    $cache = $container->get(Cache::class);
    $triviaq = $cache->get('triviaq_');
    if ($triviaq === false || is_null($triviaq)) {
        $fluent = $container->get(Database::class);
        $qid = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    foreach ($cleanup as $item) {
        if ($item['clean_file'] === 'trivia_update.php') {
            $round = $item['clean_time'] < 0 ? 0 : $item['clean_time'];
        } elseif ($item['clean_file'] === 'trivia_points_update.php') {
            $game = $item['clean_time'] < 0 ? 0 : $item['clean_time'];
        }
    }

    return [
        'round' => $round,
        'game' => $game,
    ];
}

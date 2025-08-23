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
        $qid = $fluent->from('triviaq')
                      ->select(null)
                      ->select('qid')
                      ->where('current = 1')
                      ->where('asked = 1')
                      ->fetch('qid');

        $gamenum = $fluent->from('triviasettings')
                          ->select(null)
                          ->select('gamenum')
                          ->where('gameon = 1')
                          ->fetch('gamenum');

        $results = $fluent->from('triviausers')
                          ->select(null)
                          ->select('user_id')
                          ->select('correct')
                          ->where('gamenum = ?', $gamenum);

        $users = [];
        foreach ($results as $result) {
            if (empty($users[$result['user_id']])) {
                $users[$result['user_id']] = [
                    'uid' => $result['user_id'],
                    'correct' => 0,
                    'incorrect' => 0,
                ];
            }
            if ($result['correct'] === 0) {
                ++$users[$result['user_id']]['incorrect'];
            } else {
                ++$users[$result['user_id']]['correct'];
            }
        }
        $triviaq = [
            'qid' => $qid,
            'gamenum' => $gamenum,
            'users' => $users,
        ];
        $cache->set('triviaq_', $triviaq, 0);
    }
    $qid = $triviaq['qid'];
    $gamenum = $triviaq['gamenum'];
    $users = $triviaq['users'];

    if (!empty($users)) {
        $users = array_msort($users, [
            'correct' => SORT_DESC,
            'incorrect' => SORT_ASC,
        ]);
        $users = array_splice($users, 0, 5);

        $heading = "
        <tr>
            <th class='has-text-left w-5'>" . _('Username') . "</th>
            <th class='has-text-centered w-5'>" . _('Ratio') . "</th>
            <th class='has-text-centered w-5'>" . _('Correct') . "</th>
            <th class='has-text-centered w-5'>" . _('Incorrect') . '</th>
        </tr>';
        $body = '';
        foreach ($users as $user) {
            $percentage = $user['correct'] / ($user['correct'] + $user['incorrect']) * 100;
            $body .= "
        <tr>
            <td class='w-5'><div class='is-pulled-left'>" . format_username((int) $user['uid']) . "</div></td>
            <td class='has-text-centered w-5'>" . sprintf('%.2f%%', $percentage) . "</td>
            <td class='has-text-centered w-5'>{$user['correct']}</td>
            <td class='has-text-centered w-5'>{$user['incorrect']}</td>
        </tr>";
        }

        return [
            'table' => main_table($body, $heading),
            'qid' => $qid,
            'gamenum' => $gamenum,
        ];
    }

    return [
        'table' => '',
        'qid' => $qid,
        'gamenum' => $gamenum,
    ];
}

/**
 * @return string
 */
function trivia_clocks()
{
    return "
    <ul class='level-center top20'>
        <div id='clock_round'>
            <span class='right10'>" . _fe('Next Question in: {0}', "</span><span class='has-text-success'><span class='days'></span><span class='hours'></span><span class='minutes'></span>:<span class='seconds'></span></span>") . "
        </div>
        <div id='clock_game'>
            <span class='right10'>" . _fe('Game Ends in: {0}', "</span><span class='has-text-success'><span class='days'></span> <span class='hours'></span>:<span class='minutes'></span>:<span class='seconds'></span></span>") . '
        </div>
    </ul>';
}

/**
 * @param $data
 *
 * @return mixed
 */
function clean_data($data)
{
    foreach ($data as $key => $value) {
        $data[$key] = html_entity_decode(replace_unicode_strings(trim($value)));
    }

    return $data;
}

/**
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 *
 * @return array
 */
function trivia_time()
{
    global $container;

    $fluent = $container->get(Database::class);
    $round = $game = 0;
    $cleanup = $fluent->from('cleanup')
                      ->select(null)
                      ->select('clean_time - UNIX_TIMESTAMP(NOW()) AS clean_time')
                      ->select('clean_file')
                      ->fetchAll();

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

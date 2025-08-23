<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Envms\FluentPDO\Literal;
use Pu239\Cache;
use Pu239\Database;
use Pu239\PollVoter;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
$user = check_user_status();
$poll_id = isset($_GET['pollid']) ? (int) $_GET['pollid'] : 0;
if (!is_valid_id($poll_id)) {
    stderr(_('Error'), 'No poll with that ID');
}
$vote_cast = [];
$_POST['choice'] = isset($_POST['choice']) ? $_POST['choice'] : [];
global $container, $site_config;

$fluent = $container->get(Database::class);
$poll_data = $fluent->from('polls')
                    ->where('polls.pid = ?', $poll_id)
                    ->leftJoin('poll_voters ON polls.pid = poll_voters.poll_id AND poll_voters.user_id = ?', $user['id'])
                    ->fetch();

if (empty($poll_data)) {
    stderr(_('Error'), _('Invalid ID'));
}

if (!empty($poll_data['user_id'])) {
    stderr(_('Error'), _('You have already voted!'));
}
$_POST['nullvote'] = isset($_POST['nullvote']) ? $_POST['nullvote'] : 0;
$pollvoter_class = $container->get(PollVoter::class);
if (!$_POST['nullvote']) {
    if (is_array($_POST['choice']) && count($_POST['choice'])) {
        foreach ($_POST['choice'] as $question_id => $choice_id) {
            if (!$question_id || !isset($choice_id)) {
                continue;
            }
            $vote_cast[$question_id][] = $choice_id;
        }
    }
    foreach ($_POST as $k => $v) {
        if (preg_match("#^choice_(\d+)_(\d+)$#", $k, $matches)) {
            if ($_POST[$k] == 1) {
                $vote_cast[$matches[1]][] = $matches[2];
            }
        }
    }
    $poll_answers = json_decode($poll_data['choices'], true);
    reset($poll_answers);
    if (!empty($vote_cast) && count($vote_cast) < count($poll_answers)) {
        stderr(_('Error'), 'No vote');
    }
    $values = [
        'user_id' => $user['id'],
        'poll_id' => $poll_data['pid'],
        'vote_date' => TIME_NOW,
    ];
    $vid = $pollvoter_class->add($values);
    if (!$vid) {
        stderr(_('Error'), _('Could not update records'));
    }
    foreach ($vote_cast as $question_id => $choice_array) {
        foreach ($choice_array as $choice_id) {
            if (is_numeric($choice_id)) {
                ++$poll_answers[$question_id]['votes'][$choice_id];
                if ($poll_answers[$question_id]['votes'][$choice_id] < 1) {
                    $poll_answers[$question_id]['votes'][$choice_id] = 1;
                }
            }
        }
    }
    $choices = json_encode($poll_answers);
    $votes = $poll_data['votes'] + 1;
    $cache = $container->get(Cache::class);
    $cache->update_row('poll_data_' . $user['id'], [
        'votes' => $votes,
        'user_id' => $user['id'],
        'vote_date' => TIME_NOW,
        'choices' => $choices,
    ], $site_config['expires']['poll_data']);

    $set = [
        'votes' => new Literal('votes + 1'),
        'choices' => $choices,
    ];
    $result = $fluent->update('polls')
                     ->set($set)
                     ->where('pid = ?', $poll_data['pid'])
                     ->execute();

    if (!$result) {
        stderr(_('Error'), _('Could not update records'));
    }
} else {
    $values = [
        'user_id' => $user['id'],
        'poll_id' => $poll_data['pid'],
        'vote_date' => TIME_NOW,
    ];
    $vid = $pollvoter_class->add($values);
    $votes = $poll_data['votes'] + 1;
    $cache->update_row('poll_data_' . $user['id'], [
        'votes' => $votes,
        'user_id' => $user['id'],
        'vote_date' => TIME_NOW,
    ], $site_config['expires']['poll_data']);

    if (!$vid) {
        stderr(_('Error'), _('Could not update records'));
    }
}
header("location: {$site_config['paths']['baseurl']}/#poll");

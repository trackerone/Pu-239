<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

require_once __DIR__ . '/../include/bittorrent.php';

use Pu239\Database;
use Pu239\User;

$post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : (isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0);
$forum_id = isset($_GET['forum_id']) ? (int) $_GET['forum_id'] : (isset($_POST['forum_id']) ? (int) $_POST['forum_id'] : 0);
$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
if (!is_valid_id($post_id) || !is_valid_id($forum_id) || !is_valid_id($topic_id)) {
    stderr(_('Error'), _('Bad ID.'));
}
global $container, $site_config, $CURUSER;

$users_class = $container->get(User::class);
$fluent = $container->get(Database::class);
$query = $fluent->from('posts AS p')
                ->select('t.topic_name AS topic_name')
                ->select('f.name AS forum_name')
                ->leftJoin('topics AS t ON p.topic_id = t.id')
                ->leftJoin('forums AS f ON t.forum_id = f.id')
                ->where('p.id = ?', $post_id);
if ($CURUSER['class'] < UC_STAFF) {
    $query = $query->where("p.status = 'ok'")
                   ->where("t.status = 'ok'");
} elseif ($CURUSER['class'] < $site_config['forum_config']['min_delete_view_class']) {
    $query = $query->where("p.status != 'deleted'")
                   ->where("t.status != 'deleted'");
}
$query = $query->fetch();
$arr_edited = $users_class->getUserFromId($query['edited_by']);
$icon = htmlsafechars($query['icon']);
$post_title = htmlsafechars($query['post_title']);
$HTMLOUT .= " 
    <h1 class='has-text-centered'>" . ($query['anonymous'] === '1' ? '<i>' . get_anonymous_name() . '</>' : htmlsafechars($arr_edited['username'])) . '\'s ' . _('Final Edited Post') . "</h1>
    <h2 class='has-text-centered'>" . _('last edited by') . ': ' . ($query['anonymous'] === '1' ? '<i>' . get_anonymous_name() . '</i>' : htmlsafechars($arr_edited['username'])) . '</h2>';
$body = "
    #{$post_id} " . ($query['anonymous'] === '1' ? '<i>' . get_anonymous_name() . '</i>' : format_username($arr_edited['id'])) . ' ' . _('Posted') . ': ' . get_date($query['added'], 'LONG') . '
    <br>' . (!empty($post_title) ? '' . _('Title') . ": <span class='has-text-weight-bold'>{$post_title}</span>" : '') . (!empty($icon) ? ' <img src="' . $site_config['paths']['images_baseurl'] . 'smilies/' . $icon . '.gif" alt="' . $icon . '" title="' . $icon . '" class="emoticon">' : '') . ($query['anonymous'] === '1' ? '<i>' . get_anonymous_name() . '</i>' : format_username($arr_edited['id'])) . ($query['bbcode'] === 'yes' ? format_comment($query['body']) : format_comment_no_bbcode($query['body']));
$HTMLOUT .= main_div($body, 'bottom20', 'padding20') . "
    <h2 class='has-text-centered'>" . _('Post History') . "</h2>
    <div class='has-text-centered bottom20'>
        [ " . _('All Post Edits by Date Desc') . ' ]
    </div>';
$HTMLOUT .= $query['post_history'];

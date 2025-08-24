<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

use Pu239\Cache;

$child_boards = $now_viewing = $colour = '';
$forum_id = isset($_GET['forum_id']) ? (int) $_GET['forum_id'] : (isset($_POST['forum_id']) ? (int) $_POST['forum_id'] : 0);
if (!is_valid_id($forum_id)) {
    stderr(_('Error'), _('Bad ID.'));
}

$over_forums_res = sql_query('SELECT name, min_class_view FROM over_forums WHERE id = ' . sqlesc($forum_id)) or sqlerr(__FILE__, __LINE__);
$over_forums_arr = mysqli_fetch_assoc($over_forums_res);
global $container;
$db = $container->get(Database::class);, $CURUSER, $site_config;

if ($CURUSER['class'] < $over_forums_arr['min_class_view']) {
    stderr(_('Error'), _('Bad ID.'));
}

$HTMLOUT .= $mini_menu;

$HTMLOUT .= "
    <h1 class='has-text-centered'pan>" . _('Section View for') . ' ' . format_comment($over_forums_arr['name']) . '</h1>';
$forums_res = sql_query('SELECT name AS forum_name, description AS forum_description, id AS forum_id, post_count, topic_count FROM forums WHERE min_class_read < ' . sqlesc($CURUSER['class']) . ' AND forum_id=' . sqlesc($forum_id) . ' AND parent_forum = 0 ORDER BY sort') or sqlerr(__FILE__, __LINE__);
$body = '';
$cache = $container->get(Cache::class);
while ($forums_arr = mysqli_fetch_assoc($forums_res)) {
    //=== Get last post info
    if (($last_post_arr = $cache->get('sv_last_post_' . $forums_arr['forum_id'] . '_' . $CURUSER['class'])) === false) {
        $rows = $db->fetchAll('SELECT t.last_post, t.topic_name, t.id AS topic_id, t.anonymous AS tan, p.user_id, p.added, p.anonymous AS pan, u.id, u.username, u.class, u.donor, u.warned, u.status, u.chatpost, u.leechwarn, u.pirate, u.king, u.perms, u.offensive_avatar FROM topics AS t LEFT JOIN posts AS p ON t.last_post = p.id LEFT JOIN users AS u ON p.user_id=u.id WHERE ' . ($CURUSER['class'] < UC_STAFF ? 'p.status = \'ok\' AND t.status = \'ok\' AND' : ($CURUSER['class'] < $site_config['forum_config']['min_delete_view_class'] ? 'p.status != \'deleted\' AND t.status != \'deleted\' AND' : '')) . ' forum_id=' . sqlesc($forums_arr['forum_id']) . ' ORDER BY last_post DESC LIMIT 1');
        $last_post_arr = mysqli_fetch_assoc($query);
        $cache->set('sv_last_post_' . $forums_arr['forum_id'] . '_' . $CURUSER['class'], $last_post_arr, $site_config['expires']['sv_last_post']);
    }
    //=== only do more if there is a stuff here...
    if ($last_post_arr['last_post'] > 0) {
        //=== get the last post read by CURUSER
        if (($last_read_post_arr = $cache->get('sv_last_read_post_' . $last_post_arr['topic_id'] . '_' . $CURUSER['id'])) === false) {
            $rows = $db->fetchAll('SELECT last_post_read FROM read_posts WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($last_post_arr['topic_id'])) or sqlerr(__FILE__, __LINE__);
            $last_read_post_arr = mysqli_fetch_row($query);
            $cache->set('sv_last_read_post_' . $last_post_arr['topic_id'] . '_' . $CURUSER['id'], $last_read_post_arr, $site_config['expires']['sv_last_read_post']);
        }
        $image_and_link = ($last_post_arr['added'] > (TIME_NOW - $site_config['forum_config']['readpost_expiry'])) ? (!$last_read_post_arr || $last_post_arr['last_post'] > $last_read_post_arr[0]) : 0;
        $img = ($image_and_link ? 'unlockednew' : 'unlocked');
        //=== get '._('child boards').' if any
        $keys['child_boards'] = 'sv_child_boards_' . $forums_arr['forum_id'] . '_' . $CURUSER['class'];
        if (($child_boards_cache = $cache->get($keys['child_boards'])) === false) {
            $child_boards = '';
            $child_boards_cache = [];
            $rows = $db->fetchAll('SELECT name, id FROM forums WHERE parent_forum = ' . sqlesc($forums_arr['forum_id']) . ' ORDER BY sort');
            foreach ($rows as $arr) {
                if ($child_boards) {
                    $child_boards .= ', ';
                }
                $child_boards .= '<a href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_forum&amp;forum_id=' . (int) $arr['id'] . '" title="click to view!" class="is-link tooltipper">' . format_comment($arr['name']) . '</a>';
            }
            $child_boards_cache['child_boards'] = $child_boards;
            $cache->set($keys['child_boards'], $child_boards_cache, $site_config['expires']['sv_child_boards']);
        }
        $child_boards = $child_boards_cache['child_boards'];
        if ($child_boards !== '') {
            $child_boards = '<hr><span style="font-size: xx-small;">' . _('child boards') . ':</span> ' . $child_boards;
        }
        //=== now_viewing
        if (($now_viewing_cache = $cache->get('now_viewing_section_view')) === false) {
            $nowviewing = '';
            $now_viewing_cache = [];
            $rows = $db->fetchAll('SELECT n_v.user_id, u.id, u.username, u.class, u.donor, u.warned, u.status, u.chatpost, u.leechwarn, u.pirate, u.king, u.perms FROM now_viewing AS n_v LEFT JOIN users AS u ON n_v.user_id=u.id WHERE forum_id=' . sqlesc($forums_arr['forum_id'])) or sqlerr(__FILE__, __LINE__);
            foreach ($rows as $arr) {
                if ($nowviewing) {
                    $nowviewing .= ",\n";
                }
                $nowviewing .= (get_anonymous((int) $arr['user_id']) ? '<i>' . _('UnKn0wn') . '</i>' : format_username((int) $arr['user_id']));
            }
            $now_viewing_cache['now_viewing'] = $nowviewing;
            $cache->set('now_viewing_section_view', $now_viewing_cache, $site_config['expires']['section_view']);
        }
        if (!$now_viewing_cache['now_viewing']) {
            $now_viewing_cache['now_viewing'] = _('There have been no active users in the last 15 minutes.');
        }
        $now_viewing = $now_viewing_cache['now_viewing'];
        if ($now_viewing !== '') {
            $now_viewing = '<hr><span style="font-size: xx-small;">' . _('now viewing') . ': </span>' . $now_viewing;
        }
        if ($last_post_arr['tan'] === '1') {
            if ($CURUSER['class'] < UC_STAFF && $last_post_arr['user_id'] != $CURUSER['id']) {
                $last_post = '' . _('Last Post by') . ': ' . _('Anonymous in') . ' &#9658; <a class="is-link tooltipper" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . (int) $last_post_arr['topic_id'] . '&amp;page=p' . (int) $last_post_arr['last_post'] . '#' . (int) $last_post_arr['last_post'] . '" title="' . format_comment($last_post_arr['topic_name']) . '">
		<span style="font-weight: bold;">' . CutName(format_comment($last_post_arr['topic_name']), 30) . '</span></a><br>
		' . get_date((int) $last_post_arr['added'], '') . '<br>';
            } else {
                $last_post = '' . _('Last Post by') . ': ' . get_anonymous_name() . ' [' . format_username((int) $last_post_arr['user_id']) . ']</span><br>
		in &#9658; <a class="is-link tooltipper" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . (int) $last_post_arr['topic_id'] . '&amp;page=p' . (int) $last_post_arr['last_post'] . '#' . (int) $last_post_arr['last_post'] . '" title="' . format_comment($last_post_arr['topic_name']) . '">
		<span style="font-weight: bold;">' . CutName(format_comment($last_post_arr['topic_name']), 30) . '</span></a><br>
		' . get_date((int) $last_post_arr['added'], '') . '<br>';
            }
        } else {
            $last_post = '' . _('Last Post by') . ': ' . format_username((int) $last_post_arr['user_id']) . '</span><br>
		in &#9658; <a class="is-link tooltipper" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . (int) $last_post_arr['topic_id'] . '&amp;page=p' . (int) $last_post_arr['last_post'] . '#' . (int) $last_post_arr['last_post'] . '" title="' . format_comment($last_post_arr['topic_name']) . '">
		<span style="font-weight: bold;">' . CutName(format_comment($last_post_arr['topic_name']), 30) . '</span></a><br>
		' . get_date((int) $last_post_arr['added'], '') . '<br>';
        }
    } else {
        $img = 'unlocked';
        $now_viewing = '';
        $last_post = _('N/A');
    }
    $body .= "
    <tr>
        <td>
            <img src='{$site_config['paths']['images_baseurl']}forums/{$img}.gif' alt='" . ucfirst($img) . "' title='" . ucfirst($img) . "' class='tooltipper'>
        </td>
		<td>
    		<a class='is-link' href='{$site_config['paths']['baseurl']}/forums.php?action=view_forum&amp;forum_id={$forums_arr['forum_id']}'>" . format_comment($forums_arr['forum_name']) . "</a><p class='top10'>" . format_comment($forums_arr['forum_description']) . $child_boards . $now_viewing . '</p>
        </td>
        <td>' . number_format((int) $forums_arr['post_count']) . '' . _('Posts') . '<br>' . number_format((int) $forums_arr['topic_count']) . '' . _('Topics') . "</td>
        <td>
		    <span>{$last_post}</span>
        </td>
    </tr>";
}

$HTMLOUT .= main_table($body);

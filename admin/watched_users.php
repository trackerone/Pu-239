<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;

global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$baseurl = $s($config->get('paths.baseurl'));
$self = $s($_SERVER['PHP_SELF'] ?? '');

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$HTMLOUT = $H1_thingie = '';
$count = 0;

// TODO(2025): csrf
if (isset($_GET['remove'])) {
    if ($CURUSER['class'] < UC_STAFF) {
        stderr(_('Error'), _('Only the Staff can remove members from the list!'));
    }
    $ids = $_POST['wu'] ?? ($_GET['wu'] ?? []);
    if (!is_array($ids)) {
        $ids = [$ids];
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));

    $cache = $container->get(Cache::class);
    $removed_log = '';

    foreach ($ids as $id) {
        if (!is_valid_id($id)) {
            continue;
        }
        $user = $db->fetch('SELECT username, modcomment FROM users WHERE id = :id', [':id' => $id]);
        if (!$user) {
            continue;
        }
        $modcomment = get_date((int) TIME_NOW, 'DATE', 1) . ' - ' . _('Removed from watched users by') . " {$CURUSER['username']}\n" . $user['modcomment'];
        $db->run('UPDATE users SET watched_user = 0, modcomment = :modcomment WHERE id = :id', [':modcomment' => $modcomment, ':id' => $id]);
        $cache->update_row('user_' . $id, ['watched_user' => 0, 'modcomment' => $modcomment], $config->get('expires.user_cache'));
        ++$count;
        $removed_log .= ($removed_log === '' ? '' : ', ') . format_username($id);
    }

    if ($count > 0) {
        write_log('[b]' . $CURUSER['username'] . '[/b] ' . _('Removed:') . "<br>{$removed_log} <br>" . _('from watched users'));
    }
    $H1_thingie = '<h1 class="has-text-centered">' . _pfe('{0} Member removed from the list', '{0} Members removed from the list', $count) . '</h1>';
}

if (isset($_GET['add'])) {
    $member = (int) ($_GET['id'] ?? 0);
    if (is_valid_id($member)) {
        $user = $db->fetch('SELECT id, username, modcomment, watched_user, watched_user_reason FROM users WHERE id = :id', [':id' => $member]);
        if ($user['watched_user'] > 0) {
            stderr(_('Error'), _fe("{0} is on the watched user list already! back to {1}'s profile", $s($user['username']), format_username((int) $user['id'])));
        }
        if (isset($_GET['add']) && $_GET['add'] == 1) {
            $text = "
                <form method='post' action='./staffpanel.php?tool=watched_users&amp;action=watched_users&amp;add=2&amp;id={$member}' enctype='multipart/form-data' accept-charset='utf-8'>
                    <h2>" . _fe('Add {0} to the Watched Users List', $s($user['username'])) . "</h2>
                    <div class='has-text-centered'>
                        <span><b>" . _fe('please fill in the reason for adding {0} to the watched user list.', format_username((int) $member)) . "</b></span>
                    </div>
                    <textarea class='w-100' rows='6' name='reason'>" . $s($user['watched_user_reason']) . "</textarea>
                    <input type='submit' class='button is-small' value='" . _('add to watched users!') . "'>
                </form>";
            $naughty_box = main_div($text);
            stderr('watched Users', $naughty_box);
        }
        $watched_user_reason = $s($_POST['reason'] ?? '');
        $modcomment = get_date((int) TIME_NOW, 'DATE', 1) . ' - ' . _fe('Added to watched users by {0}', $CURUSER['username']) . "\n" . $user['modcomment'];
        $stmt = $db->run('UPDATE users SET watched_user = :now, modcomment = :mc, watched_user_reason = :reason WHERE id = :id', [':now' => TIME_NOW, ':mc' => $modcomment, ':reason' => $watched_user_reason, ':id' => $member]);
        if ($stmt->rowCount()) {
            $cache = $container->get(Cache::class);
            $cache->update_row('user_' . $member, ['watched_user' => TIME_NOW, 'watched_user_reason' => $watched_user_reason, 'modcomment' => $modcomment], $config->get('expires.user_cache'));
            $H1_thingie = '<h1 class="has-text-centered">' . _fe('Success! {0} Added to the Watched Users List!', format_comment($user['username'])) . '</h1>';
            write_log(_fe('{0} Added {1} to the {2} watched users list{4}.', format_username($CURUSER['id']), format_username((int) $member), "<a href='{$baseurl}/staffpanel.php?tool=watched_users&amp;action=watched_users' class='is-link'>", '</a>'));
        }
    }
}

$watched_users = number_format((int) $db->fetchValue('SELECT COUNT(id) FROM users WHERE watched_user != 0'));

$good_stuff = ['username', 'watched_user', 'invitedby'];
$sort = $_GET['sort'] ?? 'watched_user';
$sort = in_array($sort, $good_stuff, true) ? $sort : 'watched_user';
$asc = (isset($_GET['ASC']) && $_GET['ASC'] === 'ASC') ? 'DESC' : 'ASC';

$rows = $db->fetchAll("SELECT id, username, registered, watched_user_reason, watched_user, uploaded, downloaded, warned, status, donor, class, leechwarn, chatpost, pirate, king, invitedby FROM users WHERE watched_user != 0 ORDER BY $sort $asc");
$HTMLOUT .= $H1_thingie;
if (!empty($rows)) {
    $HTMLOUT .= "
        <form action='{$self}?tool=watched_users&amp;action=watched_users&amp;remove=1' method='post'  name='checkme' accept-charset='utf-8'>
        <h1 class='has-text-centered'>" . _('Watched Users') . "[ {$watched_users} ]</h1>
    <table class='table table-bordered table-striped'>
    <tr>
        <td><a href='{$baseurl}/staffpanel.php?tool=watched_users&amp;action=watched_users&amp;sort=watched_user&amp;ASC={$asc}'>" . _('Added') . "</a></td>
        <td><a href='{$baseurl}/staffpanel.php?tool=watched_users&amp;action=watched_users&amp;sort=username&amp;ASC={$asc}'>" . _('Username') . "</a></td>
        <td class='has-text-left'>" . _('Suspicion') . "</td>
        <td class='has-text-centered'>" . _('Stats') . "</td>
        <td class='has-text-centered'><a href='{$baseurl}/staffpanel.php?tool=watched_users&amp;action=watched_users&amp;sort=invitedby&amp;ASC={$asc}'>" . _('Invited By') . "</a></td>" .
        ($CURUSER['class'] >= UC_STAFF ? "
        <td class='has-text-centered'>
            <input type='checkbox' id='checkThemAll' class='tooltipper' title='Select All'>
        </td>" : '') . "
    </tr>";

    foreach ($rows as $arr) {
        $invitor_arr = [];
        if ($arr['invitedby'] != 0) {
            $invitor_arr = $db->fetch('SELECT id, username, donor, class, status, warned, leechwarn, chatpost, pirate, king FROM users WHERE id = :id', [':id' => $arr['invitedby']]) ?? [];
        }
        $the_flip_box = "<p>" . format_comment($arr['watched_user_reason']) . "</p>";
        $HTMLOUT .= "
    <tr>
        <td class='has-text-centered'>" . get_date((int) $arr['watched_user'], '') . "</td>
        <td class='has-text-left'>" . format_username((int) $arr['id']) . "</td>
        <td class='has-text-left'>{$the_flip_box}</td>
        <td class='has-text-centered'>" . member_ratio((float) $arr['uploaded'], (float) $arr['downloaded']) . "</td>
        <td class='has-text-centered'>" . (empty($invitor_arr['username']) ? _('open sign-ups') : format_username((int) $arr['invitedby'])) . "</td>" .
        ($CURUSER['class'] >= UC_STAFF ? "
        <td class='has-text-centered'><input type='checkbox' name='wu[]' value='" . (int) $arr['id'] . "'></td>" : '') . "
    </tr>";
    }
    $HTMLOUT .= "
        <tr>
            <td class='has-text-centered' colspan='6'>
                <input type='submit' class='button is-small' value='" . _('remove selected from watched users') . "'>
            </td>
        </tr>
    </table>
</form>";
} else {
    $HTMLOUT .= stdmsg(_('Error'), _('The watched members list is empty'));
}

$title = _('Watched Users');
$breadcrumbs = [
    "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$self}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();


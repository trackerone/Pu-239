<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

use DI\DependencyException;
use DI\NotFoundException;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$input = array_merge($_GET, $_POST);
$input['mode'] = isset($input['mode']) ? $input['mode'] : '';
$reputationid = 0;
$time_offset = 0;
$a = explode(',', gmdate('Y,n,j,G,i,s', TIME_NOW + $time_offset));
$now_date = [
    'year' => $a[0],
    'mon' => $a[1],
    'mday' => $a[2],
    'hours' => $a[3],
    'minutes' => $a[4],
    'seconds' => $a[5],
];
switch ($input['mode']) {
    case 'modify':
        show_level($input);
        break;

    case 'add':
        show_form($input, 'new');
        break;

    case 'doadd':
        do_update($input, 'new');
        break;

    case 'edit':
        show_form($input, 'edit');
        break;

    case 'doedit':
        do_update($input, 'edit');
        break;

    case 'doupdate':
        do_update($input, '');
        break;

    case 'dodelete':
        do_delete($input);
        break;

    case 'list':
        view_list($now_date, $input, $time_offset);
        break;

    case 'editrep':
        //show_form_rep('edit');
        show_form_rep($input);
        break;

    case 'doeditrep':
        do_edit_rep($input);
        break;

    case 'dodelrep':
        do_delete_rep($input);
        break;

    default:
        show_level($input);
        break;
}

/**
 * @param array $input
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws Exception
 */
function show_level(array $input)
{
    global $site_config;

    $title = _('User Reputation Manager - Overview');
    $html = '';
    $rows = $db->fetchAll('SELECT * FROM reputationlevel ORDER BY minimumreputation');
    if (!mysqli_num_rows($query)) {
        do_update($input, 'new');

        return;
    }
    $html .= "
        <h1 class='has-text-centered'>" . _('User Reputation Manager') . "</h1>
        <p class='margin20'>" . _('On this page you can modify the minimum amount required for each reputation level. Make sure you press Update Minimum Levels to save your changes. You cannot set the same minimum amount to more than one level.') . '<br>' . _('From here you can also choose to edit or remove any single level. Click the Edit link to modify the Level description (see Editing a Reputation Level) or click Remove to delete a level. If you remove a level or modify the minimum reputation needed to be at a level, all users will be updated to reflect their new level if necessary.') . "</p>
        <div class='has-text-centered bottom20'>
            <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=reputation_ad&amp;mode=list'>
                <span class='button is-small has-text-black'>
                    " . _('View comments') . '
                </span>
            </a>
        </div>';
    $html .= "<form action='{$_SERVER['PHP_SELF']}?tool=reputation_ad' name='show_rep_form' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
                <input name='mode' value='doupdate' type='hidden'>";
    $heading = '
        <tr>
            <th>' . _('ID') . '</th>
            <th>' . _('Reputation Level') . '</th>
            <th>' . _('Minimum Reputation Level') . '</th>
            <th>' . _('Controls') . '</th>
        </tr>';
    $body = '';
    while ($res = mysqli_fetch_assoc($query)) {
        $body .= "
        <tr>
            <td>#{$res['reputationlevelid']}</td>
            <td>" . _fe('User <b>{0}</b>', format_comment($res['level'])) . "</b></td>
            <td><input type='text' name='reputation[" . $res['reputationlevelid'] . "]' value='" . $res['minimumreputation'] . "'></td>
            <td>
                <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=reputation_ad&amp;mode=edit&amp;reputationlevelid=" . $res['reputationlevelid'] . "'>
                    <i class='icon-edit icon has-text-info' aria-hidden='true'></i>
                </a>
                <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=reputation_ad&amp;mode=dodelete&amp;reputationlevelid=" . $res['reputationlevelid'] . "'>
                    <i class='icon-trash-empty icon has-text-danger' aria-hidden='true'></i>
                </a>
            </td>
        </tr>";
    }
    $body .= "
        <tr>
            <td colspan='4' class='has-text-centered'>
                <input type='submit' value='" . _('Update') . "' accesskey='s' class='button is-small'>
                <input type='reset' value='" . _('Reset') . "' accesskey='r' class='button is-small'>
                <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=reputation_ad&amp;mode=add'>
                    <span class='button is-small has-text-black'>
                        " . _('Add New') . '
                    </span>
                </a>
            </td>
        </tr>';

    $html .= main_table($body, $heading);
    $html .= '</form>';
    html_out($html, $title);
}

/**
 * @param array  $input
 * @param string $type
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws Exception
 */
function show_form(array $input, string $type)
{
    $html = _('This allows you to add a new reputation level or edit an existing reputation level.');
    $res = [];
    if ($type === 'edit') {
        $rows = $db->fetchAll('SELECT * FROM reputationlevel WHERE reputationlevelid = ' . (int) $input['reputationlevelid']) or sqlerr(__LINE__, __FILE__);
        if (!$res = mysqli_fetch_assoc($query)) {
            stderr(_('Error'), _('Invalid ID.'));
        }
        $title = _('Edit Reputation Level');
        $html .= '<br>' . _fe('{0} (ID: #{1})', format_comment($res['level']), $res['reputationlevelid']) . '<br>';
        $button = _('Update');
        $extra = "<input type='button' class='button is-small' value='" . _('Back') . "' accesskey='b' class='button is-small' onclick='history.back()'>";
        $mode = 'doedit';
    } else {
        $title = _('Add New Reputation Level');
        $button = _('Save');
        $mode = 'doadd';
        $extra = "<input type='button' value='" . _('Back') . "' accesskey='b' class='button is-small' onclick='history.back()'>";
    }
    $replevid = isset($res['reputationlevelid']) ? $res['reputationlevelid'] : '';
    $replevel = isset($res['level']) ? $res['level'] : '';
    $minrep = isset($res['minimumreputation']) ? $res['minimumreputation'] : '';
    $html .= "<form action='staffpanel.php?tool=reputation_ad' name='show_rep_form' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
                <input name='reputationlevelid' value='{$replevid}' type='hidden'>
                <input name='mode' value='{$mode}' type='hidden'>";
    $html .= "<h2>$title</h2><table><tr>
        <td>&#160;</td>
        <td>&#160;</td></tr>";
    $html .= '<tr><td>' . _('Level Description') . "<div class='desctext'>" . _('This is what is displayed for the user when their reputation points are above the amount entered as the minimum.') . '</div></td>';
    $html .= "<td><input type='text' name='level' value=\"{$replevel}\" maxlength='250'></td></tr>";
    $html .= '<tr><td>' . _('Minimum amount of reputation points required for this level') . '<div>' . _("This can be a positive or a negative amount. When the user's reputation points reaches this amount, the above description will be displayed.") . '</div></td>';
    $html .= "<td><input type='text' name='minimumreputation' value=\"{$minrep}\" maxlength='10'></td></tr>";
    $html .= "<tr><td colspan='2' class='has-text-centered'><input type='submit' value='$button' accesskey='s' class='button is-small'> <input type='reset' value='" . _('Reset') . "' accesskey='r' class='button is-small'> $extra</td></tr>";
    $html .= '</table>';
    $html .= '</form>';
    html_out($html, $title);
}

/**
 * @param array  $input
 * @param string $type
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws Exception
 */
function do_update(array $input, string $type)
{
    $minrep = $level = $redirect = '';
    if ($type != '') {
        $level = strip_tags($input['level']);
        $level = trim($level);
        if ((strlen($input['level']) < 2) || ($level == '')) {
            stderr(_('Error'), _('The text you entered was too short.'));
        }
        if (strlen($input['level']) > 250) {
            stderr(_('Error'), _('The text entry is too long.'));
        }
        $level = sqlesc($level);
        $minrep = sqlesc(intval($input['minimumreputation']));
        $redirect = _fe('Saved Reputation Level <i>{0}</i> Successfully.', format_comment($input['level']));
    }
    // what we gonna do?
    if ($type === 'new') {
        $db->run(");
    } elseif ($type === 'edit') {
        $levelid = intval($input['reputationlevelid']);
        if (!is_valid_id($levelid)) {
            stderr(_('Error'), _('Invalid ID'));
        }
        // check it's a valid rep id
        $rows = $db->fetchAll("SELECT reputationlevelid FROM reputationlevel WHERE reputationlevelid=$levelid");
        if (!mysqli_num_rows($query)) {
            stderr(_('Error'), _('Invalid ID.'));
        }
        $db->run(");
    } else {
        $ids = $input['reputation'];
        if (is_array($ids) && count($ids)) {
            foreach ($ids as $k => $v) {
                $db->run(');
    }
    rep_cache();
    redirect('staffpanel.php?tool=reputation_ad&amp;mode=done', $redirect);
}

/**
 * @param array $input
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws Exception
 */
function do_delete(array $input)
{
    if (!isset($input['reputationlevelid']) || !is_valid_id((int) $input['reputationlevelid'])) {
        stderr(_('Error'), 'No valid ID.');
    }
    $levelid = intval($input['reputationlevelid']);
    // check the id is valid within db
    $rows = $db->fetchAll("SELECT reputationlevelid FROM reputationlevel WHERE reputationlevelid = $levelid");
    if (!mysqli_num_rows($query)) {
        stderr(_('Error'), _("Rep ID doesn't exist"));
    }
    // if we here, we delete it!
    $db->run(");
    rep_cache();
    redirect('staffpanel.php?tool=reputation_ad&amp;mode=done', _('Reputation deleted successfully'), 5);
}

/**
 * @param array $input
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws Exception
 */
function show_form_rep(array $input)
{
    global $site_config;

    if (!isset($input['reputationid']) || !is_valid_id((int) $input['reputationid'])) {
        stderr(_('Error'), _('Invalid ID.'));
    }
    $title = _('User Reputation Manager');
    $rows = $db->fetchAll('SELECT r.*, p.topic_id, t.topic_name, leftfor.username AS leftfor_name, 
                    leftby.username AS leftby_name
                    FROM reputation r
                    LEFT JOIN posts p ON p.id=r.postid
                    LEFT JOIN topics t ON p.topic_id=t.id
                    LEFT JOIN users leftfor ON leftfor.id=r.userid
                    LEFT JOIN users leftby ON leftby.id=r.whoadded
                    WHERE reputationid=' . sqlesc($input['reputationid'])) or sqlerr(__FILE__, __LINE__);
    if (!$res = mysqli_fetch_assoc($query)) {
        stderr(_('Error'), _("Erm, it's not there!"));
    }
    $html = "<form action='staffpanel.php?tool=reputation_ad' name='show_rep_form' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
                <input name='reputationid' value='{$res['reputationid']}' type='hidden'>
                <input name='oldreputation' value='{$res['reputation']}' type='hidden'>
                <input name='mode' value='doeditrep' type='hidden'>";
    $html .= '<h2>' . _('Edit Reputation') . '</h2>';
    $html .= '<table>';
    $html .= '<tr><td>' . _('Topic') . "</td><td><a href='{$site_config['paths']['baseurl']}/forums.php?action=viewtopic&amp;topicid={$res['topic_id']}&amp;page=p{$res['postid']}#{$res['postid']}' target='_blank'>" . htmlsafechars($res['topic_name']) . '</a></td></tr>';
    $html .= '<tr><td>' . _('Left By') . "</td><td>{$res['leftby_name']}</td></tr>";
    $html .= '<tr><td>' . _('Left For') . "</td><td>{$res['leftfor_name']}</td></tr>";
    $html .= '<tr><td>' . _('Comment') . "</td><td><input type='text' name='reason' value='" . htmlsafechars($res['reason']) . "' maxlength='250'></td></tr>";
    $html .= '<tr><td>' . _('Reputation') . "</td><td><input type='text' name='reputation' value='{$res['reputation']}' maxlength='10'></td></tr>";
    $html .= "<tr><td colspan='2' class='has-text-centered'><input type='submit' value='" . _('Save') . "' accesskey='s' class='button is-small'> <input type='reset' tabindex='1' value='" . _('Reset') . "' accesskey='r' class='button is-small'></td></tr>";
    $html .= '</table></form>';
    html_out($html, $title);
}

/**
 * @param array $now_date
 * @param array $input
 * @param int   $time_offset
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws Exception
 */
function view_list(array $now_date, array $input, int $time_offset)
{
    global $site_config;

    $title = _('User Reputation Manager');
    $html = '<h2>' . _('View Reputation Comments') . '</h2>';
    $html .= '<p>' . _('This page allows you to search for reputation comments left by / for specific users over the specified date range.') . '</p>';
    $html .= "<form action='{$_SERVER['PHP_SELF']}?tool=reputation_ad' name='list_form' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
                <input name='mode' value='list' type='hidden'>
                <input name='dolist' value='1' type='hidden'>";
    $html .= '<table>';
    $html .= '<tr><td>' . _('Left For') . "</td><td><input type='text' name='leftfor' value='' maxlength='250' tabindex='1'></td></tr>";
    $html .= "<tr><td colspan='2'><div>" . _('To limit the comments left for a specific user, enter the username here. Leave this field empty to receive comments left for every user.') . '</div></td></tr>';
    $html .= '<tr><td>' . _('Left By') . "</td><td><input type='text' name='leftby' value='' maxlength='250' tabindex='2'></td></tr>";
    $html .= "<tr><td colspan='2'><div>" . _('To limit the comments left by a specific user, enter the username here. Leave this field empty to receive comments left by every user.') . '</div></td></tr>';
    $html .= '<tr><td>' . _('Start Date') . "</td><td>
        <div>
                <span style='padding-right:5px; float:left;'>" . _('Month') . "<br><select name='start[month]' tabindex='3'>" . get_month_dropdown($now_date) . "</select></span>
                <span style='padding-right:5px; float:left;'>" . _('Day') . "<br><input type='text' name='start[day]' value='" . ($now_date['mday'] + 1) . "' maxlength='2' tabindex='3'></span>
                <span>{" . _('Year') . "}<br><input type='text' name='start[year]' value='" . $now_date['year'] . "' maxlength='4' tabindex='3'></span>
            </div></td></tr>";
    $html .= "<tr><td class='tdrow2' colspan='2'><div class='desctext'>{" . _('Select a start date for this report. Select a month, day, and year. The selected statistic must be no older than this date for it to be included in the report.') . '}</div></td></tr>';
    $html .= '<tr><td>' . _('End Date') . "</td><td>
            <div>
                <span style='padding-right:5px; float:left;'>" . _('Month') . "<br><select name='end[month]' class='textinput' tabindex='4'>" . get_month_dropdown($now_date) . "</select></span>
                <span style='padding-right:5px; float:left;'>" . _('Day') . "<br><input type='text' class='textinput' name='end[day]' value='" . $now_date['mday'] . "' maxlength='2' tabindex='4'></span>
                <span>" . _('Year') . "<br><input type='text' class='textinput' name='end[year]' value='" . $now_date['year'] . "' maxlength='4' tabindex='4'></span>
            </div></td></tr>";
    $html .= "<tr><td class='tdrow2' colspan='2'><div class='desctext'>" . _("Select an end date for this report. Select a month, day, and year. The selected statistic must not be newer than this date for it to be included in the report. You can use this setting in conjunction with the 'Start Date' setting to create a window of time for this report.") . '</div></td></tr>';
    $html .= "<tr><td colspan='2'><input type='submit' value='" . _('Search') . "' accesskey='s' class='button is-small' tabindex='5'> <input type='reset' value='" . _('Reset') . "' accesskey='r' class='button is-small' tabindex='6'></td></tr>";
    $html .= '</table></form>';

    if (isset($input['dolist'])) {
        $input['orderby'] = isset($input['orderby']) ? $input['orderby'] : '';
        $who = isset($input['who']) ? (int) $input['who'] : 0;
        $user = isset($input['user']) ? $input['user'] : 0;
        $first = isset($input['page']) ? (int) $input['page'] : 0;
        $cond = $who ? 'r.whoadded=' . sqlesc($who) : '';
        $start = isset($input['startstamp']) ? (int) $input['startstamp'] : mktime(0, 0, 0, $input['start']['month'], $input['start']['day'], $input['start']['year']) + $time_offset;
        $end = isset($input['endstamp']) ? (int) $input['endstamp'] : mktime(0, 0, 0, $input['end']['month'], $input['end']['day'] + 1, $input['end']['year']) + $time_offset;
        if (!$start) {
            $start = TIME_NOW - (3600 * 24 * 30);
        }
        if (!$end) {
            $end = TIME_NOW;
        }
        if ($start >= $end) {
            stderr(_('Error'), _('Start date is after the end date.'));
        }
        if (!empty($input['leftby'])) {
            $left_b = $db->run(');
}

/**
 * @param int   $i
 * @param array $now_date
 *
 * @return string
 */
function get_month_dropdown(array $now_date, $i = 0)
{
    $return = '';
    $month = [
        '',
        _('January'),
        _('February'),
        _('March'),
        _('April'),
        _('May'),
        _('June'),
        _('July'),
        _('August'),
        _('September'),
        _('October'),
        _('November'),
        _('December'),
    ];
    foreach ($month as $k => $m) {
        $return .= "\t<option value='" . $k . "' ";
        $return .= (($k + $i) == $now_date['mon']) ? 'selected' : '';
        $return .= '>' . $m . "</option>\n";
    }

    return $return;
}

/**
 * @throws Exception
 */
function rep_cache()
{
    $rows = $db->fetchAll('SELECT * FROM reputationlevel');
    if (!mysqli_num_rows($query)) {
        stderr(_('Error'), _('No items to cache'));
    }
    $rep_out = '<' . "?php\n\n\$reputations = [\n";
    while ($row = mysqli_fetch_assoc($query)) {
        $rep_out .= "\t{$row['minimumreputation']} => '{$row['level']}',\n";
    }
    $rep_out .= "\n];";
    file_put_contents(CACHE_DIR . 'rep_cache.php', $rep_out);
}

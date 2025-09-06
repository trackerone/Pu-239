<?php
declare(strict_types=1);

use Pu239\Database;
use Pu239\Cache;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_pager.php';
require_once CLASS_DIR . 'class_check.php';

global $container, $site_config, $CURUSER;

$db    = $container->get(Database::class);
$cache = $container->get(Cache::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$stdfoot = [
    'js' => [
        get_file_name('cheaters_js'),
    ],
];

$dt = TIME_NOW;

if (isset($_POST['nowarned']) && $_POST['nowarned'] === 'nowarned') {
    $ids_remove = !empty($_POST['remove']) ? array_map('intval', (array) $_POST['remove']) : [];
    $ids_disable = !empty($_POST['desact']) ? array_map('intval', (array) $_POST['desact']) : [];

    if (empty($ids_remove) && empty($ids_disable)) {
        stderr(_('Error'), _('You must select a user.'));
    }

    // A) Remove selected cheater rows
    if (!empty($ids_remove)) {
        $in = implode(', ', $ids_remove);
        $db->perform("DELETE FROM cheaters WHERE id IN ($in)");
    }

    // B) Disable selected users (status=2) and prepend modcomment
    if (!empty($ids_disable)) {
        $in = implode(', ', $ids_disable);
        $modline = get_date((int) $dt, 'DATE', 1) . ' - ' . _fe('Disabled by {0} (cheater panel)', $CURUSER['username']) . "\n";
        $db->perform(
            "UPDATE users
                SET status = 2,
                    modcomment = CONCAT(:modline, modcomment)
              WHERE id IN ($in)",
            ['modline' => $modline]
        );
        // Invalidate user cache rows
        foreach ($ids_disable as $uid) {
            $cache->delete('user_' . (int) $uid);
        }
    }

    header('Refresh: 2; url=' . htmlsafechars($_SERVER['REQUEST_URI']));
    $changed = count($ids_remove) + count($ids_disable);
    stderr(_('Success'), _pfe('{0} change applied.', '{0} changes applied.', $changed));
    app_halt('Exit called');
}

/** Count + pagination */
$count = (int) $db->fetchValue('SELECT COUNT(id) FROM cheaters');
$perpage = 15;

$HTMLOUT = "<h1 class='has-text-centered'>Possible Cheaters</h1>";

if ($count > 0) {
    $pager = pager($perpage, $count, $site_config['paths']['baseurl'] . '/staffpanel.php?tool=cheaters&amp;action=cheaters&amp;');

    $HTMLOUT .= "
    <form action='{$_SERVER['PHP_SELF']}?tool=cheaters&amp;action=cheaters' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";

    if ($count > $perpage) {
        $HTMLOUT .= $pager['pagertop'];
    }

    $heading = "
        <tr>
            <th class='w-1 has-text-centered'>#</th>
            <th>" . _('Username') . "</th>
            <th class='w-1 has-text-centered'>" . _('Disable') . "</th>
            <th class='w-1 has-text-centered'>" . _('Remove') . '</th>
        </tr>';

    $rows = $db->fetchAll(
        'SELECT c.id AS cid, c.added, c.userid, c.torrentid, c.client, c.rate, c.beforeup, c.upthis, c.timediff, c.userip,
                t.id AS tid, t.name AS tname
           FROM cheaters AS c
      LEFT JOIN torrents AS t ON t.id = c.torrentid
       ORDER BY c.added DESC ' . $pager['limit']
    );

    $body = '';
    foreach ($rows as $arr) {
        $id       = (int) $arr['cid'];
        $userid   = (int) $arr['userid'];
        $torrname = htmlsafechars(CutName($arr['tname'], 80));

        $cheater = format_username($userid) . ' ' . _(' has been flagged with an abnormally high upload speed!') . '<br>'
            . _('On torrent') . " <a href='{$site_config['paths']['baseurl']}/details.php?id=" . (int) $arr['tid'] . "' title='{$torrname}'>{$torrname}</a><br>"
            . _('Uploaded') . ' ' . mksize((int) $arr['upthis']) . '<br>'
            . _('Speed') . ' ' . mksize((int) $arr['rate']) . '/s<br>'
            . _('Within') . ' ' . (int) $arr['timediff'] . ' ' . _('Seconds') . '<br>'
            . _('Using Client:') . ' ' . htmlsafechars($arr['client']) . '<br>'
            . _('Ip Address') . ' ' . htmlsafechars($arr['userip']);

        $cheaters = "
        <div class='dt-tooltipper-large' data-tooltip-content='#cheater_{$id}_tooltip'>" . format_username($userid, true, false) . "
            <div class='tooltip_templates'>
                <div id='cheater_{$id}_tooltip'>$cheater</div>
            </div>
        </div>";

        $body .= "
        <tr>
            <td class='has-text-centered'>{$id}</td>
            <td>{$cheaters}</td>
            <td class='has-text-centered'><input type='checkbox' name='desact[]' value='{$userid}'></td>
            <td class='has-text-centered'><input type='checkbox' name='remove[]' value='{$id}'></td>
        </tr>";
    }

    $HTMLOUT .= main_table($body, $heading);

    $HTMLOUT .= "
        <div class='has-text-centered margin20'>
            <input type='button' value='" . _('Check All Disable') . "' onclick=\"this.value=check1(this.form.elements['desact[]'])\" class='button is-small'>
            <input type='button' value='" . _('Check All Remove') . "' onclick=\"this.value=check2(this.form.elements['remove[]'])\" class='button is-small'>
            <input type='hidden' name='nowarned' value='nowarned'>
            <input type='submit' name='submit' value='" . _('Apply Changes') . "' class='button is-small'>
        </div>
    </form>";

    if ($count > $perpage) {
        $HTMLOUT .= $pager['pagerbottom'];
    }
} else {
    stderr(_('Error'), _('There are not any cheaters'), 'bottom20');
}

$title = _('Ratio Cheats');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];

echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

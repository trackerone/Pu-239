<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=85-5

namespace PU239\Http\Handlers\Admin;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use PU239\Security\AuthZ;

final class CheatersHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=85-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            $handlerPath = __FILE__;
            if (stripos($handlerPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container, $CURUSER;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $s = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $s($_SERVER['PHP_SELF'] ?? '');
            $baseurl = $s((string) $config->get('paths.baseurl'));

            $stdfoot = [
                'js' => [
                    get_file_name('cheaters_js'),
                ],
            ];

            $dt = TIME_NOW;

            if (isset($_POST['nowarned']) && $_POST['nowarned'] === 'nowarned') {
                // TODO(2025): csrf hardening for admin cheater actions
                $idsRemove = !empty($_POST['remove']) ? array_map('intval', (array) $_POST['remove']) : [];
                $idsDisable = !empty($_POST['desact']) ? array_map('intval', (array) $_POST['desact']) : [];

                if ($idsRemove === [] && $idsDisable === []) {
                    stderr(_('Error'), _('You must select a user.'));
                }

                if ($idsRemove !== []) {
                    [$inClause, $bindings] = $db->inClause('remove_id', $idsRemove);
                    $db->run("DELETE FROM cheaters WHERE id IN ($inClause)", $bindings);
                }

                if ($idsDisable !== []) {
                    [$inClause, $bindings] = $db->inClause('disable_id', $idsDisable);
                    $modline = get_date((int) $dt, 'DATE', 1) . ' - ' . _fe('Disabled by {0} (cheater panel)', $CURUSER['username']) . "\n";
                    $bindings['modline'] = $modline;
                    $db->run(
                        "UPDATE users
                            SET status = 2,
                                modcomment = CONCAT(:modline, modcomment)
                          WHERE id IN ($inClause)",
                        $bindings
                    );

                    foreach ($idsDisable as $uid) {
                        $cache->delete('user_' . (int) $uid);
                    }
                }

                $refreshTarget = htmlsafechars($_SERVER['REQUEST_URI'] ?? '');
                header('Refresh: 2; url=' . $refreshTarget);
                $changed = count($idsRemove) + count($idsDisable);
                stderr(_('Success'), _pfe('{0} change applied.', '{0} changes applied.', $changed));
                app_halt('Exit called');
            }

            $count = (int) $db->fetchValue('SELECT COUNT(id) FROM cheaters');
            $perpage = 15;

            $HTMLOUT = "<h1 class='has-text-centered'>" . _('Possible Cheaters') . '</h1>';

            if ($count > 0) {
                $pager = pager($perpage, $count, (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=cheaters&amp;action=cheaters&amp;');

                $HTMLOUT .= "
    <form action='{$self}?tool=cheaters&amp;action=cheaters' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";

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
                   ORDER BY c.added DESC ' . ($pager['limit'] ?? '')
                );

                $body = '';
                foreach ($rows as $arr) {
                    $id = (int) ($arr['cid'] ?? 0);
                    $userid = (int) ($arr['userid'] ?? 0);
                    $idDisplay = $s((string) $id);
                    $userIdDisplay = $s((string) $userid);
                    $torrentName = $s(CutName((string) ($arr['tname'] ?? ''), 80));
                    $client = $s((string) ($arr['client'] ?? ''));
                    $userIp = $s((string) ($arr['userip'] ?? ''));
                    $uploaded = $s(mksize((int) ($arr['upthis'] ?? 0)));
                    $speed = $s(mksize((int) ($arr['rate'] ?? 0)));
                    $timeDiff = $s((string) ($arr['timediff'] ?? ''));
                    $tid = $s((string) (int) ($arr['tid'] ?? 0));
                    $detailsLink = "{$baseurl}/details.php?id={$tid}";

                    $cheater = format_username($userid) . ' ' . _(' has been flagged with an abnormally high upload speed!') . '<br>'
                        . _('On torrent') . " <a href='{$detailsLink}' title='{$torrentName}'>{$torrentName}</a><br>"
                        . _('Uploaded') . ' ' . $uploaded . '<br>'
                        . _('Speed') . ' ' . $speed . '/s<br>'
                        . _('Within') . ' ' . $timeDiff . ' ' . _('Seconds') . '<br>'
                        . _('Using Client:') . ' ' . $client . '<br>'
                        . _('Ip Address') . ' ' . $userIp;

                    $cheaters = "
        <div class='dt-tooltipper-large' data-tooltip-content='#cheater_{$idDisplay}_tooltip'>" . format_username($userid, true, false) . "
            <div class='tooltip_templates'>
                <div id='cheater_{$idDisplay}_tooltip'>$cheater</div>
            </div>
        </div>";

                    $body .= "
        <tr>
            <td class='has-text-centered'>{$idDisplay}</td>
            <td>{$cheaters}</td>
            <td class='has-text-centered'><input type='checkbox' name='desact[]' value='{$userIdDisplay}'></td>
            <td class='has-text-centered'><input type='checkbox' name='remove[]' value='{$idDisplay}'></td>
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
                $HTMLOUT .= stdmsg(_('Error'), _('Nothing found.'));
            }

            $title = _('Possible Cheaters');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $s($title) . '</a>',
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

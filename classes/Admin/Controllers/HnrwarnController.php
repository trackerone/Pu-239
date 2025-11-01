<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Psr\Container\ContainerInterface;

final class HnrwarnController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23; tool=codex-admin-medium-require; rules=2025.10.23-admin-require
        try {
            global $container, $CURUSER;
            $container = $this->container;
            $config = $this->config;

            $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
            if (strpos($scriptPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $db = $container->get(Database::class);
            $cache = $container->get(Cache::class);

            $s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $s($_SERVER['PHP_SELF'] ?? '');
            $baseurl = $s($config->get('paths.baseurl'));

            $HTMLOUT = '';
            $this_url = $_SERVER['SCRIPT_NAME'] ?? '';
            $requestUri = $s($_SERVER['REQUEST_URI'] ?? '');
            $do = (isset($_GET['do']) && $_GET['do'] === 'disabled') ? 'disabled' : 'hnrwarn';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
                $r = isset($_POST['ref']) ? (string) $_POST['ref'] : $this_url;
                $uids = isset($_POST['users']) && is_array($_POST['users']) ? array_map('intval', $_POST['users']) : [];

                if (empty($uids)) {
                    stderr(_('Error'), _("Looks like you didn't select any user!"));
                }

                $valid = ['unwarn', 'disable', 'delete'];
                $act = isset($_POST['action']) && in_array($_POST['action'], $valid, true) ? (string) $_POST['action'] : '';

                if ($act === '') {
                    stderr(_('Error'), _('Something went wrong!'));
                }

                if ($act === 'unwarn') {
                    $messages = $container->get(Message::class);
                    $buffer = [];
                    $sub = _('HnR Warning Removed');
                    $bodyTpl = _fe('Hey, your Hit and Run warning was removed by {0}. Please keep your best behaviour from now on.', $CURUSER['username']);

                    [$in, $bindings] = $db->inClause('uid', $uids);
                    $params = array_merge(['no' => 'no'], $bindings);
                    $db->run(
                        'UPDATE users SET hnrwarn = :no, warn_reason = NULL WHERE id IN (' . $in . ')',
                        $params
                    );

                    foreach ($uids as $id) {
                        $cache->delete('user_' . (int) $id);
                        $buffer[] = [
                            'receiver' => (int) $id,
                            'added'    => TIME_NOW,
                            'msg'      => $bodyTpl,
                            'subject'  => $sub,
                        ];
                    }
                    if (!empty($buffer)) {
                        $messages->insert($buffer);
                    }

                    audit_log(
                        $CURUSER['id'] ?? null,
                        'user.unban',
                        [
                            'target' => $uids,
                            'reason' => 'hnrwarn.remove',
                        ]
                    );

                    header('Refresh: 2; url=' . $r);
                    stderr(_('Success'), _pfe("{0} user's HnR warning removed", "{0} users' HnR warnings removed", count($uids)));
                } elseif ($act === 'disable') {
                    $reason = _fe('Disabled for HnR by {0} on {1}', $CURUSER['username'], get_date(TIME_NOW, 'DATE', 1));
                    [$in, $bindings] = $db->inClause('uid', $uids);
                    $params = array_merge(['reason' => $reason, 'hnr' => 'no'], $bindings);
                    $db->run(
                        "UPDATE users
                         SET status = 2, disable_reason = :reason, hnrwarn = :hnr
                         WHERE id IN ($in)",
                        $params
                    );
                    foreach ($uids as $id) {
                        $cache->delete('user_' . (int) $id);
                    }

                    audit_log(
                        $CURUSER['id'] ?? null,
                        'user.ban',
                        [
                            'target' => $uids,
                            'reason' => $reason,
                            'op' => 'hnr.disable',
                        ]
                    );

                    header('Refresh: 2; url=' . $r);
                    stderr(_('Success'), _pfe('{0} user disabled', '{0} users disabled', count($uids)));
                } elseif ($act === 'delete') {
                    if (!has_access((int) $CURUSER['class'], UC_SYSOP, 'coder')) {
                        stderr(_('Error'), _('Permission denied.'));
                    }
                    [$in, $bindings] = $db->inClause('uid', $uids);
                    $db->run("DELETE FROM users WHERE id IN ($in)", $bindings);
                    foreach ($uids as $id) {
                        $cache->delete('user_' . (int) $id);
                    }

                    audit_log(
                        $CURUSER['id'] ?? null,
                        'user.ban',
                        [
                            'target' => $uids,
                            'reason' => 'hnr.delete',
                            'op' => 'delete',
                        ]
                    );

                    header('Refresh: 2; url=' . $r);
                    stderr(_('Success'), _pfe('{0} user deleted', '{0} users deleted', count($uids)));
                }

                app_halt('Exit called');
            }

            switch ($do) {
                case 'disabled':
                    $query = "SELECT id, username, class, downloaded, uploaded, IF(downloaded>0, round((uploaded/downloaded),2), '---') AS ratio, disable_reason, registered, last_access
                              FROM users WHERE status = 2 ORDER BY last_access DESC";
                    $title = _('Disabled users');
                    $link = '<a href="staffpanel.php?tool=hnrwarn&amp;action=hnrwarn&amp;do=warned">' . _('Hit and Run warned users') . '</a>';
                    break;

                case 'hnrwarn':
                default:
                    $query = "SELECT id, username, class, downloaded, uploaded, IF(downloaded>0, round((uploaded/downloaded),2), '---') AS ratio, warn_reason, hnrwarn, registered, last_access
                              FROM users WHERE hnrwarn='yes' ORDER BY last_access DESC, hnrwarn DESC";
                    $title = _('Hit and Run Warned users');
                    $link = '<a href="staffpanel.php?tool=hnrwarn&amp;action=hnrwarn&amp;do=disabled">' . _('disabled users') . '</a>';
                    break;
            }

            $rows = $db->fetchAll($query);
            $count = count($rows);

            if ($count === 0) {
                $HTMLOUT .= stdmsg(_('Hey'), _('There are no ') . strtolower($title));
            } else {
                $HTMLOUT .= "<form action='{$self}?tool=hnrwarn&amp;action=hnrwarn' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
                    <table id='checkbox_container' style='border-collapse:separate;'>
                    <tr>
                        <td class='colhead'>" . _('User') . "</td>
                        <td class='colhead' nowrap='nowrap'>" . _('Ratio') . "</td>
                        <td class='colhead' nowrap='nowrap'>" . _('Class') . "</td>
                        <td class='colhead' nowrap='nowrap'>" . _('Last access') . "</td>
                        <td class='colhead' nowrap='nowrap'>" . _('Joined') . "</td>
                        <td class='colhead' nowrap='nowrap'><input type='checkbox' id='checkThemAll'></td>
                    </tr>";

                foreach ($rows as $a) {
                    $tip = ($do === 'hnrwarn'
                        ? _('Hit and run Warned for: ') . htmlsafechars((string) ($a['warn_reason'] ?? ''))
                        : _('Disabled for ') . htmlsafechars((string) ($a['disable_reason'] ?? ''))
                    );
                    $HTMLOUT .= "<tr>
                              <td><a href='userdetails.php?id=" . (int) $a['id'] . "' class='tooltipper' title='$tip'>" . htmlsafechars((string) $a['username']) . "</a></td>
                              <td nowrap='nowrap'>" . (is_numeric($a['ratio']) ? (float) $a['ratio'] : '---') . "<br><span class='small'><b>" . _('D:') . '</b>' . mksize((int) $a['downloaded']) . '&#160;<b>' . _('U:') . '</b> ' . mksize((int) $a['uploaded']) . "</span></td>
                              <td nowrap='nowrap'>" . get_user_class_name((int) $a['class']) . "</td>
                              <td nowrap='nowrap'>" . get_date((int) $a['last_access'], 'LONG', 0, 1) . "</td>
                              <td nowrap='nowrap'>" . get_date((int) $a['registered'], 'DATE', 1) . "</td>
                              <td nowrap='nowrap'><input type='checkbox' name='users[]' value='" . (int) $a['id'] . "'></td>
                            </tr>";
                }

                $HTMLOUT .= "<tr>
                        <td colspan='6' class='colhead'>
                            <select name='action'>
                                <option value='unwarn'>" . _('Unwarn') . "</option>
                                <option value='disable'>" . _('Disable') . "</option>
                                <option value='delete' " . (!has_access((int) $CURUSER['class'], UC_SYSOP, 'coder') ? 'disabled' : '') . '>' . _('Delete') . "</option>
                            </select>
                            &raquo;
                            <input type='submit' value='" . _('Apply') . "'>
                            <input type='hidden' value='{$requestUri}' name='ref'>
                        </td>
                        </tr>
                        </table>
                        </form>";
            }

            $title = $title ?? _('HnR Warn');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $s($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Admin controller error (hnrwarn): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}

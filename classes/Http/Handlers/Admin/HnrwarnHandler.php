<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:57:12Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;

final class HnrwarnHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:57:12Z via codex handler conversion
        try {
            $container = $GLOBALS['container'] ?? null;
            if ($container === null) {
                throw new \RuntimeException('Global container not initialized');
            }
            $currentUser = $GLOBALS['CURUSER'] ?? null;

            if (defined('ADMIN_DIR') && strpos((string) ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escape($_SERVER['PHP_SELF'] ?? '');
            $baseurl = $escape((string) $config->get('paths.baseurl'));

            $HTMLOUT = '';
            $thisUrl = $_SERVER['SCRIPT_NAME'] ?? '';
            $requestUri = $escape($_SERVER['REQUEST_URI'] ?? '');
            $do = (isset($_GET['do']) && $_GET['do'] === 'disabled') ? 'disabled' : 'hnrwarn';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
                $redirect = isset($_POST['ref']) ? (string) $_POST['ref'] : $thisUrl;
                $uids = isset($_POST['users']) && is_array($_POST['users']) ? array_map('intval', $_POST['users']) : [];

                if (empty($uids)) {
                    stderr(_('Error'), _("Looks like you didn't select any user!"));
                }

                $valid = ['unwarn', 'disable', 'delete'];
                $action = isset($_POST['action']) && in_array($_POST['action'], $valid, true) ? (string) $_POST['action'] : '';

                if ($action === '') {
                    stderr(_('Error'), _('Something went wrong!'));
                }

                if ($action === 'unwarn') {
                    /** @var Message $messages */
                    $messages = $container->get(Message::class);
                    $buffer = [];
                    $subject = _('HnR Warning Removed');
                    $bodyTemplate = _fe('Hey, your Hit and Run warning was removed by {0}. Please keep your best behaviour from now on.', $currentUser['username'] ?? '');

                    $placeholders = implode(',', array_fill(0, count($uids), '?'));
                    $params = array_merge([':no' => 'no'], $uids);
                    $db->run('UPDATE users SET hnrwarn = :no, warn_reason = NULL WHERE id IN (' . $placeholders . ')', $params);

                    foreach ($uids as $id) {
                        $cache->delete('user_' . (int) $id);
                        $buffer[] = [
                            'receiver' => (int) $id,
                            'added' => TIME_NOW,
                            'msg' => $bodyTemplate,
                            'subject' => $subject,
                        ];
                    }

                    if (!empty($buffer)) {
                        $messages->insert($buffer);
                    }

                    audit_log(
                        $currentUser['id'] ?? null,
                        'user.unban',
                        [
                            'target' => $uids,
                            'reason' => 'hnrwarn.remove',
                        ]
                    );

                    header('Refresh: 2; url=' . $redirect);
                    stderr(_('Success'), _pfe("{0} user's HnR warning removed", "{0} users' HnR warnings removed", count($uids)));
                } elseif ($action === 'disable') {
                    $reason = _fe('Disabled for HnR by {0} on {1}', $currentUser['username'] ?? '', get_date(TIME_NOW, 'DATE', 1));
                    $placeholders = implode(',', array_fill(0, count($uids), '?'));
                    $params = array_merge([$reason, 'no'], $uids);
                    $db->run(
                        "UPDATE users
             SET status = 2, disable_reason = ?, hnrwarn = ?
             WHERE id IN ($placeholders)",
                        $params
                    );

                    foreach ($uids as $id) {
                        $cache->delete('user_' . (int) $id);
                    }

                    audit_log(
                        $currentUser['id'] ?? null,
                        'user.ban',
                        [
                            'target' => $uids,
                            'reason' => $reason,
                            'op' => 'hnr.disable',
                        ]
                    );

                    header('Refresh: 2; url=' . $redirect);
                    stderr(_('Success'), _pfe('{0} user disabled', '{0} users disabled', count($uids)));
                } elseif ($action === 'delete') {
                    if (!has_access((int) ($currentUser['class'] ?? 0), UC_SYSOP, 'coder')) {
                        stderr(_('Error'), _('Permission denied.'));
                    }
                    $placeholders = implode(',', array_fill(0, count($uids), '?'));
                    $db->run('DELETE FROM users WHERE id IN (' . $placeholders . ')', $uids);

                    foreach ($uids as $id) {
                        $cache->delete('user_' . (int) $id);
                    }

                    audit_log(
                        $currentUser['id'] ?? null,
                        'user.ban',
                        [
                            'target' => $uids,
                            'reason' => 'hnr.delete',
                            'op' => 'delete',
                        ]
                    );

                    header('Refresh: 2; url=' . $redirect);
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

                foreach ($rows as $row) {
                    $tip = ($do === 'hnrwarn'
                        ? _('Hit and run Warned for: ') . htmlsafechars((string) ($row['warn_reason'] ?? ''))
                        : _('Disabled for ') . htmlsafechars((string) ($row['disable_reason'] ?? '')));
                    $HTMLOUT .= "<tr>
                  <td><a href='userdetails.php?id=" . (int) $row['id'] . "' class='tooltipper' title='$tip'>" . htmlsafechars((string) $row['username']) . "</a></td>
                  <td nowrap='nowrap'>" . (is_numeric($row['ratio']) ? (float) $row['ratio'] : '---') . "<br><span class='small'><b>" . _('D:') . '</b>' . mksize((int) $row['downloaded']) . '&#160;<b>' . _('U:') . '</b> ' . mksize((int) $row['uploaded']) . "</span></td>
                  <td nowrap='nowrap'>" . get_user_class_name((int) $row['class']) . "</td>
                  <td nowrap='nowrap'>" . get_date((int) $row['last_access'], 'LONG', 0, 1) . "</td>
                  <td nowrap='nowrap'>" . get_date((int) $row['registered'], 'DATE', 1) . "</td>
                  <td nowrap='nowrap'><input type='checkbox' name='users[]' value='" . (int) $row['id'] . "'></td>
                </tr>";
                }

                $HTMLOUT .= "<tr>
            <td colspan='6' class='colhead'>
                <select name='action'>
                    <option value='unwarn'>" . _('Unwarn') . "</option>
                    <option value='disable'>" . _('Disable') . "</option>
                    <option value='delete' " . (!has_access((int) ($currentUser['class'] ?? 0), UC_SYSOP, 'coder') ? 'disabled' : '') . '>' . _('Delete') . "</option>
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
                "<a href='{$self}'>" . $escape($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

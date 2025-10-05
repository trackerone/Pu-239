<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:57:12Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;

final class InactiveHandler
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
            /** @var Session $session */
            $session = $container->get(Session::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                rate_limit_or_fail();
            }

            $escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escape($_SERVER['PHP_SELF'] ?? '');
            $baseurlRaw = (string) $config->get('paths.baseurl');
            $baseurl = $escape($baseurlRaw);

            $HTMLOUT = '';
            $recordMail = true;
            $days = 30;
            $threshold = TIME_NOW - ($days * 86400);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
                $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
                $ids = isset($_POST['userid']) && is_array($_POST['userid']) ? array_values(array_unique(array_map('intval', $_POST['userid']))) : [];

                if (empty($ids) && ($action === 'deluser' || $action === 'mail' || $action === 'disable')) {
                    $session->set('is-warning', _('For this to work you must select at least a user!'));
                } else {
                    if ($action === 'deluser') {
                        if (!has_access((int) ($currentUser['class'] ?? 0), UC_ADMINISTRATOR, 'coder')) {
                            $session->set('is-warning', _('You do not have permission to delete users.'));
                        } else {
                            if (!empty($ids)) {
                                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                                $users = $db->fetchAll('SELECT id, username FROM users WHERE id IN (' . $placeholders . ')', $ids);
                                foreach ($users as $user) {
                                    $userId = (int) $user['id'];
                                    $username = (string) $user['username'];
                                    if (account_delete($userId)) {
                                        write_log('User: ' . htmlsafechars($username) . " was deleted by {$currentUser['username']}");
                                        audit_log(
                                            $currentUser['id'] ?? null,
                                            'user.ban',
                                            [
                                                'target' => $userId,
                                                'reason' => 'inactive.delete',
                                            ]
                                        );
                                    }
                                }
                                $session->set('is-success', _('You have successfully deleted the selected accounts!'));
                            }
                        }
                    } elseif ($action === 'disable') {
                        if (!empty($ids)) {
                            $reason = _fe('Disabled for inactivity by {0} on {1}', $currentUser['username'] ?? '', get_date(TIME_NOW, 'DATE', 1));
                            $placeholders = implode(',', array_fill(0, count($ids), '?'));
                            $params = array_merge([$reason], $ids);
                            $db->run('UPDATE users SET status = 2, disable_reason = ? WHERE id IN (' . $placeholders . ')', $params);
                            foreach ($ids as $targetId) {
                                audit_log(
                                    $currentUser['id'] ?? null,
                                    'user.ban',
                                    [
                                        'target' => (int) $targetId,
                                        'reason' => 'inactive.disable',
                                    ]
                                );
                            }
                            $session->set('is-success', _('You have successfully disabled the selected accounts!'));
                        }
                    } elseif ($action === 'mail') {
                        if (!empty($ids)) {
                            $placeholders = implode(',', array_fill(0, count($ids), '?'));
                            $rows = $db->fetchAll(
                                'SELECT id, email, username, registered, last_access
                     FROM users
                     WHERE id IN (' . $placeholders . ')
                     ORDER BY last_access DESC',
                                $ids
                            );

                            $success = 0;
                            foreach ($rows as $row) {
                                $id = (int) $row['id'];
                                $username = htmlsafechars((string) $row['username']);
                                $added = get_date((int) $row['registered'], 'DATE');
                                $lastAccess = get_date((int) $row['last_access'], 'DATE');
                                $body = doc_head(_('Your account at ')) . '</head>'
                                    . '<body>'
                                    . '<p>' . _('Hey') . " $username,</p>"
                                    . '<p>' . _('Your account at ') . ' ' . (string) $config->get('site.name') . _(' has been marked as inactive and will be deleted. If you wish to remain a member at') . ' ' . (string) $config->get('site.name') . _(', please login.') . '<br>'
                                    . _('Your username is: ') . " $username<br>"
                                    . _('And was created: ') . " $added<br>"
                                    . _('Last accessed: ') . " $lastAccess<br>"
                                    . _('Login at: ') . ' ' . (string) $config->get('paths.baseurl') . "/login.php<br>"
                                    . _('If you have forgotten your password you can retrieve it at') . ' ' . (string) $config->get('paths.baseurl') . "/resetpw.php<br>"
                                    . _('Welcome back!') . ' ' . (string) $config->get('site.name') . '</p>'
                                    . '</body>'
                                    . '</html>';
                                $mail = send_mail(
                                    (string) $row['email'],
                                    _('Your account at ') . (string) $config->get('site.name') . '!',
                                    $body,
                                    strip_tags($body)
                                );
                                if ($mail) {
                                    ++$success;
                                }
                            }

                            if ($recordMail) {
                                $date = TIME_NOW;
                                $actor = (int) ($currentUser['id'] ?? 0);
                                $db->run(
                                    'INSERT INTO avps (arg, value_s, value_i, value_u)
                         VALUES (:arg, :vs, :vi, :vu)
                         ON DUPLICATE KEY UPDATE value_s = VALUES(value_s), value_i = VALUES(value_i), value_u = VALUES(value_u)',
                                    [
                                        ':arg' => 'inactivemail',
                                        ':vs' => (string) $actor,
                                        ':vi' => (int) $date,
                                        ':vu' => (int) $success,
                                    ]
                                );
                            }

                            if ($success > 0) {
                                $session->set('is-success', _fe('Successfully sent {0} email(s).', $success));
                            } else {
                                $session->set('is-warning', _('No emails were sent.'));
                            }
                        }
                    }
                }
            }

            $countRow = $db->fetch(
                'SELECT COUNT(id) AS count FROM users WHERE last_access < :th AND verified = 1 AND status = 0',
                [':th' => $threshold]
            );
            $count = (int) ($countRow['count'] ?? 0);

            $perpage = 15;
            $pager = pager($perpage, $count, $baseurlRaw . '/staffpanel.php?tool=inactive&amp;');

            $rows = [];
            if ($count > 0) {
                $rows = $db->fetchAll(
                    'SELECT id, username, class, email, uploaded, downloaded, last_access
         FROM users
         WHERE last_access < :th AND verified = 1 AND status = 0
         ORDER BY last_access DESC ' . $pager['limit'],
                    [':th' => $threshold]
                );
            }

            if ($count > 0) {
                $HTMLOUT .= "<script>
    /*<![CDATA[*/
    var checkflag = 'false';
    function check(nodeList) {
        if (!nodeList) return 'Check All';
        var len = nodeList.length || 0;
        if (checkflag == 'false') {
            for (var i = 0; i < len; i++) { nodeList[i].checked = true; }
            checkflag = 'true';
            return 'Uncheck All';
        }
        for (var i = 0; i < len; i++) { nodeList[i].checked = false; }
        checkflag = 'false';
        return 'Check All';
    }
    /*]]>*/
    </script>";

                $HTMLOUT .= "
    <div class='row'><div class='col-md-12'>
    <h1 class='has-text-centered'>" . _fe('{0} accounts inactive for longer than {1} days.', $count, $days) . "</h1>
    <form method='post' action='{$baseurl}/staffpanel.php?tool=inactive&amp;action=inactive' enctype='multipart/form-data' accept-charset='utf-8'>
    <table class='table table-bordered'>
    <tr>
        <td class='colhead'>" . _('Username') . "</td>
        <td class='colhead'>" . _('Class') . "</td>
        <td class='colhead'>" . _('Email') . "</td>
        <td class='colhead'>" . _('Ratio') . "</td>
        <td class='colhead'>" . _('Last seen') . "</td>
        <td class='colhead'>" . _('X') . "</td>
    </tr>";

                foreach ($rows as $row) {
                    $ratio = member_ratio((float) $row['uploaded'], (float) $row['downloaded']);
                    $lastSeen = ((int) $row['last_access'] === 0) ? 'never' : get_date((int) $row['last_access'], 'DATE');
                    $className = get_user_class_name((int) $row['class']);
                    $HTMLOUT .= "<tr>
            <td>" . format_username((int) $row['id']) . "</td>
            <td>" . $className . "</td>
            <td style='max-width:130px;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;'><a href='mailto:" . htmlsafechars((string) $row['email']) . "'>" . htmlsafechars((string) $row['email']) . "</a></td>
            <td>" . $ratio . "</td>
            <td>" . $lastSeen . "</td>
            <td><input type='checkbox' name='userid[]' value='" . (int) $row['id'] . "'></td>
        </tr>";
                }

                $HTMLOUT .= "<tr>
        <td colspan='6' class='colhead'>
            <select name='action'>
                <option value='mail'>" . _('Send email') . "</option>
                <option value='deluser' " . (!has_access((int) ($currentUser['class'] ?? 0), UC_ADMINISTRATOR, 'coder') ? 'disabled' : '') . '>' . _('Delete users') . "</option>
                <option value='disable'>" . _('Disable accounts') . "</option>
            </select>
            &#160;&#160;
            <input type='submit' name='submit' value='" . _('Apply Changes') . "' class='button is-small'>
            &#160;&#160;
            <input type='button' value='Check all' onclick='this.value=check(document.getElementsByName(\"userid[]\"))' class='button is-small'>
        </td>
    </tr>";

                if ($recordMail) {
                    $dateRow = $db->fetch(
                        "SELECT avps.value_s AS userid, avps.value_i AS last_mail, avps.value_u AS mails, users.username
               FROM avps
               LEFT JOIN users ON avps.value_s = users.id
              WHERE avps.arg = 'inactivemail'
              LIMIT 1"
                    );
                    if (!empty($dateRow) && (int) ($dateRow['last_mail'] ?? 0) > 0) {
                        $HTMLOUT .= "<tr><td colspan='6' class='colhead has-text-danger'>" . _pfe(
                            'Last Email sent by {1} on {2} - {0} email sent',
                            'Last Email sent by {1} on {2} - {0} emails sent',
                            (int) $dateRow['mails'],
                            format_username((int) ($dateRow['userid'] ?? 0)),
                            get_date((int) $dateRow['last_mail'], 'DATE')
                        ) . '</td></tr>';
                    }
                }

                $HTMLOUT .= '</table></form>';
                $HTMLOUT .= '</div></div>';

                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagerbottom'];
                }
            } else {
                $HTMLOUT .= stdmsg(_('Awesome'), _fe('No account inactive for longer than {0} days.', $days));
            }

            $title = _('Inactive Users');
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

<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:57:12Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;
use Pu239\User;

final class InviteTreeHandler
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

            if (defined('ADMIN_DIR') && strpos((string) ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var User $usersClass */
            $usersClass = $container->get(User::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escape($_SERVER['PHP_SELF'] ?? '');
            $baseurlRaw = (string) $config->get('paths.baseurl');
            $baseurl = $escape($baseurlRaw);
            $imagesBaseurl = $escape((string) $config->get('paths.images_baseurl'));

            $HTMLOUT = '';
            $id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
            $fluent = $db;

            if ($id !== 0) {
                $arrUser = $usersClass->getUserFromId($id);
                $HTMLOUT .= '
    <div class="bottom20">
        <ul class="level-center bg-06">' . ($arrUser['invitedby'] == 0 ? '
            <li class="margin10"><a title="' . htmlsafechars($arrUser['username']) . ' ' . _('was registered during open doors') . '" class="is-link tooltipper">' . _('go up one level') . '</a></li>' : '
            <li class="margin10"><a href="' . $baseurl . '/staffpanel.php?tool=invite_tree&amp;really_deep=1&amp;id=' . (int) $arrUser['invitedby'] . '" title="go up one level" class="is-link tooltipper">' . _('go up one level') . '</a></li>') . '
            <li class="margin10"><a href="' . $baseurl . '/staffpanel.php?tool=invite_tree&amp;' . (isset($_GET['deeper']) ? '' : '&amp;deeper=1') . '&amp;id=' . $id . '" title=" ' . _('click to') . ' ' . (isset($_GET['deeper']) ? _('shrink') : _('expand')) . ' ' . _('this tree') . ' " class="is-link tooltipper">' . _('expand tree') . '</a></li>
            <li class="margin10"><a href="' . $baseurl . '/staffpanel.php?tool=invite_tree&amp;really_deep=1&amp;id=' . $id . '" title="' . _('click to expand even more') . '" class="is-link tooltipper">' . _('expand even more') . '</a></li>
        </ul>
    </div>
    <h1 class="has-text-centered">' . htmlsafechars($arrUser['username']) . (substr((string) $arrUser['username'], -1) === 's' ? '\'' : '\'s') . ' ' . _('Invite Tree') . '</h1>
    <table>
        <tr>
            <td>';
                $query = $fluent->from('users as u')
                    ->select(null)
                    ->select('u.id')
                    ->select('u.username')
                    ->select('u.uploaded')
                    ->select('u.downloaded')
                    ->select('u.email')
                    ->select('i.status')
                    ->leftJoin('invite_codes AS i ON u.id = i.receiver')
                    ->where('u.invitedby = ?', $id)
                    ->where("u.join_type = 'invite'")
                    ->orderBy('u.registered')
                    ->fetchAll();
                if (empty($query)) {
                    $HTMLOUT .= stdmsg(_('Error'), _('No invitees yet.'));
                } else {
                    $HTMLOUT .= '
                <table class="table table-bordered table-striped">
                    <tr>
                        <td><span class="has-text-weight-bold">' . _('Username') . '</span></td>
                        <td><span class="has-text-weight-bold">' . _('Email') . '</span></td>
                        <td><span class="has-text-weight-bold">' . _('Uploaded') . '</span></td>
                        <td><span class="has-text-weight-bold">' . _('Downloaded') . '</span></td>
                        <td><span class="has-text-weight-bold">' . _('Ratio') . '</span></td>
                        <td><span class="has-text-weight-bold">' . _('Status') . '</span></td>
                    </tr>';
                    foreach ($query as $arrInvited) {
                        $deeper = '';
                        if (isset($_GET['deeper']) || isset($_GET['really_deep'])) {
                            $query2 = $fluent->from('users as u')
                                ->select(null)
                                ->select('u.id')
                                ->select('u.username')
                                ->select('u.uploaded')
                                ->select('u.downloaded')
                                ->select('u.email')
                                ->select('i.status')
                                ->leftJoin('invite_codes AS i ON u.id = i.receiver')
                                ->where('u.invitedby = ?', $arrInvited['id'])
                                ->where("u.join_type = 'invite'")
                                ->orderBy('u.registered')
                                ->fetchAll();

                            if (!empty($query2)) {
                                $deeper .= '
                    <tr>
                        <td colspan="6"><span class="has-text-weight-bold">' . htmlsafechars($arrInvited['username']) . (substr((string) $arrInvited['username'], -1) === 's' ? '\'' : '\'s') . _('Invites') . ': </span>
                            <div>
                                <table class="table table-bordered table-striped">
                                    <tr>
                                        <td><span class="has-text-weight-bold">' . _('Username') . '</span></td>
                                        <td><span class="has-text-weight-bold">' . _('Email') . '</span></td>
                                        <td><span class="has-text-weight-bold">' . _('Uploaded') . '</span></td>
                                        <td><span class="has-text-weight-bold">' . _('Downloaded') . '</span></td>
                                        <td><span class="has-text-weight-bold">' . _('Ratio') . '</span></td>
                                        <td><span class="has-text-weight-bold">' . _('Status') . '</span></td>
                                    </tr>';
                                foreach ($query2 as $arrInvitedDeeper) {
                                    if (isset($_GET['really_deep'])) {
                                        $query3 = $fluent->from('users as u')
                                            ->select(null)
                                            ->select('u.id')
                                            ->select('u.username')
                                            ->select('u.uploaded')
                                            ->select('u.downloaded')
                                            ->select('u.email')
                                            ->select('i.status')
                                            ->leftJoin('invite_codes AS i ON u.id = i.receiver')
                                            ->where('u.invitedby = ?', $arrInvitedDeeper['id'])
                                            ->where("u.join_type = 'invite'")
                                            ->orderBy('u.registered')
                                            ->fetchAll();

                                        if (!empty($query3)) {
                                            $deeper .= '
                                    <tr>
                                        <td colspan="6"><span class="has-text-weight-bold">' . htmlsafechars($arrInvitedDeeper['username']) . (substr((string) $arrInvitedDeeper['username'], -1) === 's' ? '\'' : '\'s') . ' Invites:</span>
                                            <div>
                                                <table class="table table-bordered table-striped">
                                                    <tr>
                                                        <td><span class="has-text-weight-bold">' . _('Username') . '</span></td>
                                                        <td><span class="has-text-weight-bold">' . _('Email') . '</span></td>
                                                        <td><span class="has-text-weight-bold">' . _('Uploaded') . '</span></td>
                                                        <td><span class="has-text-weight-bold">' . _('Downloaded') . '</span></td>
                                                        <td><span class="has-text-weight-bold">' . _('Ratio') . '</span></td>
                                                        <td><span class="has-text-weight-bold">' . _('Status') . '</span></td>
                                                    </tr>';
                                            foreach ($query3 as $arrInvitedReallyDeep) {
                                                $deeper .= '
                                                    <tr>
                                                        <td>' . ($arrInvitedReallyDeep['status'] === 'Pending' ? htmlsafechars($arrInvitedReallyDeep['username']) : format_username($arrInvitedReallyDeep['id'])) . '</td>
                                                        <td>' . htmlsafechars($arrInvitedReallyDeep['email']) . '</td>
                                                        <td>' . mksize($arrInvitedReallyDeep['uploaded']) . '</td>
                                                        <td>' . mksize($arrInvitedReallyDeep['downloaded']) . '</td>
                                                        <td>' . member_ratio($arrInvitedReallyDeep['uploaded'], $arrInvitedReallyDeep['downloaded']) . '</td>
                                                        <td>' . ($arrInvitedReallyDeep['status'] === 'Confirmed' ? '<span class="has-color-lime">' . _('Confirmed') . '</span></td></tr>' : '<span class="has-color-danger">' . _('Pending') . '</span></td>
                                                    </tr>');
                                            }
                                            $deeper .= '
                                                </table>
                                            </div>
                                        </td>
                                    </tr>';
                                        }
                                    }
                                    $deeper .= '
                                    <tr>
                                        <td>' . ($arrInvitedDeeper['status'] === 'Pending' ? htmlsafechars($arrInvitedDeeper['username']) : format_username($arrInvitedDeeper['id'])) . '</td>
                                        <td>' . htmlsafechars($arrInvitedDeeper['email']) . '</td>
                                        <td>' . mksize($arrInvitedDeeper['uploaded']) . '</td>
                                        <td>' . mksize($arrInvitedDeeper['downloaded']) . '</td>
                                        <td>' . member_ratio($arrInvitedDeeper['uploaded'], $arrInvitedDeeper['downloaded']) . '</td>
                                        <td>' . ($arrInvitedDeeper['status'] === 'Confirmed' ? '<span class="has-color-lime">' . _('Confirmed') . '</span></td></tr>' : '<span class="has-color-danger">' . _('Pending') . '</span></td>
                                    </tr>');
                                }
                                $deeper .= '
                                </table>
                            </div>
                        </td>
                    </tr>';
                            }
                        }
                        $HTMLOUT .= '
                    <tr>
                        <td>' . ($arrInvited['status'] === 'Pending' ? htmlsafechars($arrInvited['username']) : format_username($arrInvited['id'])) . '</td>
                        <td>' . htmlsafechars($arrInvited['email']) . '</td>
                        <td>' . mksize($arrInvited['uploaded']) . '</td>
                        <td>' . mksize($arrInvited['downloaded']) . '</td>
                        <td>' . member_ratio($arrInvited['uploaded'], $arrInvited['downloaded']) . '</td>
                        <td>' . ($arrInvited['status'] === 'Confirmed' ? '
                            <span class="has-color-lime">' . _('Confirmed') . '</span>
                        </td>
                    </tr>' : '
                            <span class="has-color-danger">' . _('Pending') . '</span>
                        </td>
                    </tr>');
                        $HTMLOUT .= $deeper;
                    }
                    $HTMLOUT .= '
                </table>';
                }
                $HTMLOUT .= '
            </td>
        </tr>
    </table>';
            } else {
                $id = '';
                $search = isset($_GET['search']) ? strip_tags((string) trim((string) $_GET['search'])) : '';
                $classFilter = isset($_GET['class']) ? $_GET['class'] : '-';
                $letter = '';
                $q = '';
                if ($classFilter == '-' || !ctype_digit((string) $classFilter)) {
                    $classFilter = '';
                }
                if ($search != '' || $classFilter) {
                    $query = 'username LIKE ' . sqlesc("%$search%") . " AND status='confirmed'";
                    if ($search) {
                        $q = 'search=' . htmlsafechars($search);
                    }
                } else {
                    $letter = isset($_GET['letter']) ? trim((string) $_GET['letter']) : '';
                    if (strlen($letter) > 1) {
                        app_halt('Exit called');
                    }
                    if ($letter == '' || strpos('abcdefghijklmnopqrstuvwxyz0123456789', $letter) === false) {
                        $letter = '';
                    }
                    $query = 'username LIKE ' . sqlesc("$letter%") . " AND status='confirmed'";
                    $q = 'letter=' . $letter;
                }
                if (ctype_digit((string) $classFilter)) {
                    $query .= ' AND class=' . sqlesc($classFilter);
                    $q .= ($q ? '&amp;' : '') . 'class=' . $classFilter;
                }
                $HTMLOUT .= '
        <h1 class="has-text-centered">' . _('Search Users To View Invite Tree') . '</h1>
        <form method="get" action="staffpanel.php?tool=invite_tree&amp;search=1&amp;" accept-charset="utf-8">
            <div class="has-text-centered margin20">
                <input type="hidden" name="action" value="invite_tree"/>
                <input type="text" size="30" name="search" value="' . $escape($search) . '"/>
                <select name="class">
                    <option value=" - ">' . _('(any class)') . '</option>';
                for ($i = 0;; ++$i) {
                    if ($c = get_user_class_name((int) $i)) {
                        $HTMLOUT .= '
                    <option value="' . $i . '" ' . (ctype_digit((string) $classFilter) && (string) $classFilter === (string) $i ? 'selected' : '') . '>' . $c . '</option>';
                    } else {
                        break;
                    }
                }
                $HTMLOUT .= '
                </select>
                <input type="submit" value="' . _('Search') . '" class="button is-small">
            </div>
        </form>';
                $aa = range('0', '9');
                $bb = range('a', 'z');
                $cc = array_merge($aa, $bb);
                unset($aa, $bb);
                $HTMLOUT .= '
            <nav class="pagination is-centered is-marginless is-small" role="navigation" aria-label="pagination">
                <ul class="pagination-list bottom20">
                    <li>';
                $countLetters = 0;
                foreach ($cc as $L) {
                    $HTMLOUT .= ($countLetters === 10) ? '<br><br>' : '';
                    $LL = strtoupper((string) $L);
                    if (!strcmp((string) $L, $letter)) {
                        $HTMLOUT .= '
                        <a class="pagination-link is-current" aria-label="' . $LL . '">' . $LL . '</a>';
                    } else {
                        $HTMLOUT .= '
                        <a href="' . $baseurl . '/staffpanel.php?tool=invite_tree&amp;letter=' . $escape($L) . '" class="pagination-link button">' . $LL . '</a>';
                    }
                    ++$countLetters;
                }
                $HTMLOUT .= '
                    </li>
                </ul>
            </nav>';
                $page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
                $perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : 20;
                $countRow = $db->fetch('SELECT COUNT(id) AS count FROM users WHERE ' . $query);
                $count = (int) ($countRow['count'] ?? 0);
                $link = $baseurlRaw . '/staffpanel.php?tool=invite_tree';
                $pager = pager($perpage, $count, $link);
                $menuTop = $pager['pagertop'];
                $menuBottom = $pager['pagerbottom'];
                $limit = $pager['limit'];

                $HTMLOUT .= $count > $perpage ? $menuTop : '';
                if ($count > 0) {
                    $rows = $db->fetchAll('SELECT users.*, countries.name, countries.flagpic FROM users FORCE INDEX ( username ) LEFT JOIN countries ON country = countries.id WHERE ' . $query . ' ORDER BY username ' . $limit);
                    $heading = '
            <tr>
                <th>' . _('User name') . '</th>
                <th>' . _('Registered') . '</th>
                <th>' . _('Last access') . '</th>
                <th>' . _('Class') . '</th>
                <th>' . _('Country') . '</th>
                <th>' . _('Edit') . '</th>
            </tr>';
                    $body = '';
                    foreach ($rows as $row) {
                        $country = ($row['name'] != null) ? '
                <td>
                    <img src="' . $imagesBaseurl . 'flag/' . $escape($row['flagpic']) . '" alt="' . htmlsafechars((string) $row['name']) . '" title="' . htmlsafechars((string) $row['name']) . '" class="tooltipper">
                </td>' : '
                <td>---</td>';
                        $body .= '
            <tr>
                <td>' . format_username((int) $row['id']) . '</td>
                <td>' . get_date((int) $row['registered'], '') . '</td><td>' . get_date((int) $row['last_access'], '') . '</td>
                <td>' . get_user_class_name((int) $row['class']) . '</td>' . $country . '
                <td>
                    <a href="' . $baseurl . '/staffpanel.php?tool=invite_tree&amp;id=' . (int) $row['id'] . '" title="' . _('Look at this members invite tree') . '" class="tooltipper">
                        <span class="button is-small">' . _('VIEW') . '</span>
                    </a>
                </td>
            </tr>';
                    }
                    $HTMLOUT .= main_table($body, $heading);
                } else {
                    $HTMLOUT .= stdmsg(_('Error'), _('Sorry, no member was found'));
                }
                $HTMLOUT .= $count > $perpage ? $menuBottom : '';
            }
            $title = _('Invite Tree');
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

<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PDO;
use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use PU239\Support\Audit;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;
use Psr\Container\ContainerInterface;

final class AcpmanageController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
        private readonly PDO $pdo,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23; tool=codex-admin-medium-require; rules=2025.10.23-admin-require
        try {
            global $container, $CURUSER, $cache, $session;
            $container = $this->container;
            $config = $this->config;
            $pdo = $this->pdo;
            $cache ??= $container->get(Cache::class);
            $session ??= $container->get(Session::class);
            $db = $container->get(Database::class);
            $fluent = $db;
            $s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $s($_SERVER['PHP_SELF'] ?? '');
            $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
            if (strpos($scriptPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $stdfoot = [
                'js' => [
                    get_file_name('acp_js'),
                ],
            ];

            $HTMLOUT = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
                // TODO(2025): csrf
                $ids = $_POST['ids'];
                foreach ($ids as $id) {
                    $id = (int) $id;
                    if (!is_valid_id($id)) {
                        stderr(_('Error'), _('Invalid Credentials.'));
                    }
                }
                $do = isset($_POST['do']) ? htmlsafechars($_POST['do']) : '';
                if ($do == 'enabled') {
                    $placeholders = [];
                    $params = [];
                    foreach ($ids as $i => $id) {
                        $placeholders[] = ':id' . $i;
                        $params[':id' . $i] = $id;
                    }
                    $db->run('UPDATE users SET status = 0 WHERE id IN (' . implode(',', $placeholders) . ') AND status = 2', $params);
                    foreach ($ids as $id) {
                        $cache->update_row('user_' . $id, [
                            'status' => 0,
                        ], $config->get('expires.user_cache'));
                        Audit::log($CURUSER['id'] ?? null, 'user.unban', ['target' => (int) $id]);
                    }
                } elseif ($do == 'confirm') {
                    $placeholders = [];
                    $params = [];
                    foreach ($ids as $i => $id) {
                        $placeholders[] = ':id' . $i;
                        $params[':id' . $i] = $id;
                    }
                    $db->run('UPDATE users SET verified = 1 WHERE id IN (' . implode(',', $placeholders) . ') AND verified = 0', $params);
                    foreach ($ids as $id) {
                        $cache->update_row('user_' . $id, [
                            'status' => 'confirmed',
                        ], $config->get('expires.user_cache'));
                    }
                } elseif ($do == 'delete' && ($CURUSER['class'] >= UC_MAX)) {
                    foreach ($ids as $id) {
                        $username = account_delete((int) $id);
                        if ($username) {
                            write_log(_fe('User: {0} was deleted by {1}', $username, $CURUSER['username']));
                            $session->set('is-success', _('The account was deleted.'));
                            Audit::log($CURUSER['id'] ?? null, 'user.ban', ['target' => (int) $id, 'reason' => 'account_delete']);
                        } else {
                            stderr(_('Error'), _('Unable to delete the account.'));
                        }
                    }
                    $session->set('is-success', _('The account was deleted.'));
                }
                header('Location: ' . ($_SERVER['PHP_SELF'] ?? '') . '?tool=acpmanage&amp;action=acpmanage');
                app_halt('Exit called');
            }
            $disabled = $fluent->from('users')
                               ->select(null)
                               ->select('COUNT(id) AS count')
                               ->where('status = 2')
                               ->fetch("count");
            $pending = $fluent->from('users')
                              ->select(null)
                              ->select('COUNT(id) AS count')
                              ->where('verified = 0')
                              ->fetch("count");
            $count = $fluent->from('users')
                            ->select(null)
                            ->select('COUNT(id) AS count')
                            ->where('status = 2 OR verified = 0')
                            ->fetch("count");
            $disabled = number_format($disabled);
            $pending = number_format($pending);
            $perpage = 25;
            $pager = pager($perpage, $count, 'staffpanel.php?tool=acpmanage&amp;action=acpmanage&amp;');
            $rows = $db->fetchAll('SELECT id, username, registered, downloaded, uploaded, last_access, status, verified FROM users WHERE status = 2 OR verified = 0 ORDER BY username DESC ' . $pager['limit']);
            if (!empty($rows)) {
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagertop'];
                }
                $HTMLOUT .= "<form action='{$self}?tool=acpmanage&amp;action=acpmanage' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";
                $HTMLOUT .= begin_table();
                $HTMLOUT .= "<tr><td class='colhead'>
      <input class='is-marginless' type='checkbox' title='" . _('Mark All') . "' value='" . _('Mark All') . "' onclick=\"this.value=check(form);\"></td>
      <td class='colhead'>" . _('Username') . "</td>
      <td class='colhead has-no-wrap'>" . _('Registered') . "</td>
      <td class='colhead has-no-wrap'>" . _('Last access') . "</td>
      <td class='colhead'>" . _('Class') . "</td>
      <td class='colhead'>" . _('Downloaded') . "</td>
      <td class='colhead'>" . _('Uploaded') . "</td>
      <td class='colhead'>" . _('Ratio') . "</td>
      <td class='colhead'>" . _('Status') . "</td>
      <td class='colhead has-no-wrap'>" . _('Enabled') . '</td>
      </tr>';
                foreach ($rows as $arr) {
                    $uploaded = mksize($arr['uploaded']);
                    $downloaded = mksize($arr['downloaded']);
                    $ratio = $arr['downloaded'] > 0 ? $arr['uploaded'] / $arr['downloaded'] : 0;
                    $color = get_ratio_color($ratio);
                    if ($color) {
                        $ratio = "<span style='color: $color;'>" . number_format($ratio, 2) . '</span>';
                    }
                    $added = get_date((int) $arr['registered'], 'LONG', 0, 1);
                    $last_access = get_date((int) $arr['last_access'], 'LONG', 0, 1);
                    $className = get_user_class_name((int) $arr['class']);
                    $status = $arr['status'] == 0 ? 'Enabled' : 'Disabled';
                    $enabled = $arr['status'] == 0 ? 'Enabled' : 'Disabled';
                    $HTMLOUT .= "
        <tr>
            <td>
                <input type='checkbox' name='ids[]' value='" . $s((string) $arr['id']) . "'>
            </td>
            <td>" . format_username((int) $arr['id']) . "</td>
            <td class='has-no-wrap'>" . $s($added) . "</td>
            <td class='has-no-wrap'>" . $s($last_access) . "</td>
            <td>" . $s($className) . "</td>
            <td>" . $s($downloaded) . "</td>
            <td>" . $s($uploaded) . "</td>
            <td>" . $ratio . "</td>
            <td>" . $s($status) . "</td>
            <td>" . $s($enabled) . "</td>
        </tr>";
                }
                if (($CURUSER['class'] >= UC_MAX)) {
                    $HTMLOUT .= "<tr><td colspan='10' class='has-text-centered'><select name='do'><option value='enabled' disabled selected>" . _('What to do?') . "</option><option value='enabled'>" . _('Enabled selected') . "</option><option value='confirm'>" . _('Confirm selected') . "</option><option value='delete'>" . _('Delete selected') . "</option></select><br><input type='submit' class='margin20 button is-small' value='" . _('Submit') . "'></td></tr>";
                } else {
                    $HTMLOUT .= "<tr><td colspan='10' class='has-text-centered'><select name='do'><option value='enabled' disabled selected>" . _('What to do?') . "</option><option value='enabled'>" . _('Enabled selected') . "</option><option value='confirm'>" . _('Confirm selected') . "</option></select><br><input type='submit' class='margin20 button is-small' value='" . _('Submit') . "'></td></tr>";
                }

                $HTMLOUT .= end_table();
                $HTMLOUT .= '</form>';
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagerbottom'];
                }
            } else {
                $HTMLOUT = stdmsg('<h2>' . _('Sorry') . '</h2>', '<p>' . _('Nothing found!') . '</p>');
            }
            $title = _('Account Manager');
            $baseurl = $s($config->get('paths.baseurl'));
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $s($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Admin controller error (acpmanage): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}

<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;
use Pu239\User;

final class UserHitsHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T17:02:40Z via codex handler conversion
        try {
            global $container, $CURUSER;

            if (strpos(ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            stderr(_('Error'), 'This page is not in use atm');

            $HTMLOUT = '';
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!is_valid_id($id) || ($CURUSER['id'] ?? 0) !== $id && ($CURUSER['class'] ?? 0) < UC_STAFF) {
                $id = (int) ($CURUSER['id'] ?? 0);
            }

            $count = (int) ($db->fetch(
                'SELECT COUNT(id) AS count FROM userhits WHERE hitid = :id',
                [':id' => $id],
            )['count'] ?? 0);
            $perpage = 15;
            $pager = pager($perpage, $count, "staffpanel.php?tool=user_hits&amp;id={$id}&amp;");
            if ($count === 0) {
                stderr(_('No views'), _('This user has had no profile views yet.'));
            }

            /** @var User $users */
            $users = $container->get(User::class);
            unset($users);

            $db->fetch('SELECT username FROM users WHERE id = :id', [':id' => $id]);
            $HTMLOUT .= '<h1>' . _('Profile views of ') . '' . format_username((int) $id) . '</h1>
<h2>' . _('In total ') . '' . htmlsafechars($count) . '' . _(' views') . '</h2>';
            if ($count > $perpage) {
                $HTMLOUT .= $pager['pagertop'];
            }

            $HTMLOUT .= "
<table>
<tr>
<td class='colhead'>" . _('Nr.') . "</td>
<td class='colhead'>" . _('Username') . "</td>
<td class='colhead'>" . _('Viewed at') . "</td>
</tr>\n";
            $rows = $db->fetchAll(
                'SELECT uh.*, username, users.id AS uid FROM userhits AS uh LEFT JOIN users ON uh.userid = users.id WHERE hitid = :id ORDER BY uh.id DESC ' . $pager['limit'],
                [':id' => $id],
            );
            foreach ($rows as $arr) {
                $HTMLOUT .= '
<tr><td>' . number_format((int) $arr['number']) . '</td>
<td>' . format_username((int) $arr['uid']) . '</td>
<td>' . get_date((int) $arr['added'], 'DATE', 0, 1) . "</td>
</tr>\n";
            }
            $HTMLOUT .= '</table>';
            if ($count > $perpage) {
                $HTMLOUT .= $pager['pagerbottom'];
            }

            $title = _('Profile Views');
            $baseurl = (string) $config->get('paths.baseurl');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='" . ($_SERVER['PHP_SELF'] ?? '') . "'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

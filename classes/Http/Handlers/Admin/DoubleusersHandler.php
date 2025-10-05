<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:36:06Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use Pu239\Cache;
use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;
use PU239\Security\AuthZ;

final class DoubleusersHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:36:06Z via codex handler conversion
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

            $dt = TIME_NOW;
            $HTMLOUT = '';

            $remove = isset($_GET['remove']) ? (int) $_GET['remove'] : 0;
            if ($remove) {
                $user = $db->fetch(
                    'SELECT id, username, class FROM users WHERE personal_doubleseed > NOW() AND id = :id',
                    [':id' => $remove]
                );
                if (!$user) {
                    stderr(_('Error'), _('Invalid user or DoubleSeed already expired.'));
                }

                $modline = get_date((int) $dt, 'DATE', 1) . ' - ' . _fe('DoubleSeed On All Torrents removed by {0}', $CURUSER['username']) . " \n";

                $db->run(
                    'UPDATE users
                     SET personal_doubleseed = NULL,
                         modcomment = CONCAT(:mod, modcomment)
                     WHERE id = :id',
                    [
                        ':mod' => $modline,
                        ':id' => (int) $user['id'],
                    ]
                );
                audit_log($CURUSER['id'] ?? null, 'role.change', ['target' => (int) $user['id'], 'from' => 'double_seed', 'to' => null]);

                /** @var Message $messages_class */
                $messages_class = $container->get(Message::class);
                $msg = _fe('DoubleSeed On All Torrents have been removed by {0}', $CURUSER['username']);
                $messages_class->insert([
                    [
                        'receiver' => (int) $user['id'],
                        'added'    => $dt,
                        'msg'      => $msg,
                        'subject'  => _('DoubleSeed Notice!'),
                    ],
                ]);

                /** @var Cache $cache */
                $cache = $container->get(Cache::class);
                $cache->delete('inbox_' . (int) $user['id']);
            }

            $countRow = $db->fetch('SELECT COUNT(id) AS count FROM users WHERE personal_doubleseed > NOW()');
            $count = (int) ($countRow['count'] ?? 0);

            $perpage = 25;
            $pager = pager($perpage, $count, (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=doubleusers&amp;');

            $rows = [];
            if ($count > 0) {
                $rows = $db->fetchAll(
                    'SELECT id, username, class, personal_doubleseed
                     FROM users
                     WHERE personal_doubleseed > NOW()
                     ORDER BY username ' . $pager['limit']
                );
            }

            $HTMLOUT .= "<h1 class='has-text-centered'>" . _fe('DoubleSeed Users ({0})', $count) . '</h1>';

            if ($count === 0) {
                $HTMLOUT .= main_div(_('Nothing here'), null, 'padding20 has-text-centered');
            } else {
                $heading = '
        <tr>
            <th>' . _('UserName') . '</th>
            <th>' . _('Class') . '</th>
            <th>' . _('Expires') . '</th>
            <th>' . _('Remove DoubleSeed') . '</th>
        </tr>';

                $body = '';
                foreach ($rows as $arr2) {
                    $personal_doubleseed = strtotime((string) $arr2['personal_doubleseed']);
                    $body .= '
        <tr>
            <td>' . format_username((int) $arr2['id']) . '</td>
            <td>' . get_user_class_name((int) $arr2['class']);

                    if (!has_access((int) $arr2['class'], UC_ADMINISTRATOR, 'coder') && (int) $arr2['id'] !== (int) $CURUSER['id']) {
                        $body .= '</td>
            <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_doubleseed, 'DATE'), mkprettytime($personal_doubleseed - $dt)) . "</td>
            <td><span class='has-text-danger'>" . _('Not Allowed') . '</span></td>
        </tr>';
                    } else {
                        $body .= '</td>
            <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_doubleseed, 'DATE'), mkprettytime($personal_doubleseed - $dt)) . "</td>
            <td><a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=doubleusers&amp;remove=" . (int) $arr2['id'] . "' onclick=\"return confirm('" . _('Are you sure you want to remove this users DoubleSeed Status?') . "')\">" . _('Remove') . '</a></td>
        </tr>';
                    }
                }

                $HTMLOUT .= ($count > $perpage ? $pager['pagertop'] : '') . main_table($body, $heading) . ($count > $perpage ? $pager['pagerbottom'] : '');
            }

            $title = _('DoubleSeed Manager');
            $breadcrumbs = [
                "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

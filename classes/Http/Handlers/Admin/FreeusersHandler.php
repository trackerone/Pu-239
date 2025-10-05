<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:32:40Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Cache;
use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;

final class FreeusersHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:32:40Z via codex handler conversion
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

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escaper = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escaper($_SERVER['PHP_SELF'] ?? '');
            $baseurl = (string) $config->get('paths.baseurl');
            $baseurlEscaped = $escaper($baseurl);

            $dt = TIME_NOW;
            $HTMLOUT = '';

            $remove = isset($_GET['remove']) ? (int) $_GET['remove'] : 0;
            if ($remove > 0) {
                $user = $db->fetch(
                    'SELECT id, username, class FROM users WHERE personal_freeleech > NOW() AND id = :id',
                    [':id' => $remove]
                );
                if ($user === null) {
                    stderr(_('Error'), _('Invalid user or Freeleech already expired.'));
                }

                $modline = get_date((int) $dt, 'DATE', 1) . ' - ' . _fe('Freeleech On All Torrents removed by {0}', $currentUser['username'] ?? '') . " \n";

                $db->run(
                    'UPDATE users
                     SET personal_freeleech = NULL,
                         modcomment = CONCAT(:mod, modcomment)
                     WHERE id = :id',
                    [
                        ':mod' => $modline,
                        ':id'  => (int) $user['id'],
                    ]
                );
                audit_log(
                    $currentUser['id'] ?? null,
                    'config.update',
                    [
                        'keys' => ['freeleech.user'],
                        'target' => (int) $user['id'],
                        'op' => 'remove',
                    ]
                );

                /** @var Message $messages_class */
                $messages_class = $container->get(Message::class);
                $msg = _fe('Freeleech On All Torrents have been removed by {0}', $currentUser['username'] ?? '');
                $messages_class->insert([
                    [
                        'receiver' => (int) $user['id'],
                        'added'    => $dt,
                        'msg'      => $msg,
                        'subject'  => _('Freeleech Notice!'),
                    ],
                ]);

                /** @var Cache $cache */
                $cache = $container->get(Cache::class);
                $cache->delete('inbox_' . (int) $user['id']);
            }

            $countRow = $db->fetch('SELECT COUNT(id) AS count FROM users WHERE personal_freeleech > NOW()');
            $count = (int) ($countRow['count'] ?? 0);

            $perpage = 25;
            $pager = pager($perpage, $count, $baseurl . '/staffpanel.php?tool=freeusers&amp;');

            $rows = [];
            if ($count > 0) {
                $rows = $db->fetchAll(
                    'SELECT id, username, class, personal_freeleech
                     FROM users
                     WHERE personal_freeleech > NOW()
                     ORDER BY username ' . $pager['limit']
                );
            }

            $HTMLOUT .= "<h1 class='has-text-centered'>" . _fe('Freeleech Users ({0})', $count) . '</h1>';

            if ($count === 0) {
                $HTMLOUT .= main_div(_('Nothing here'), null, 'padding20 has-text-centered');
            } else {
                $heading = '
        <tr>
            <th>' . _('UserName') . '</th>
            <th>' . _('Class') . '</th>
            <th>' . _('Expires') . '</th>
            <th>' . _('Remove Freeleech') . '</th>
        </tr>';

                $body = '';
                foreach ($rows as $arr2) {
                    $personal_freeleech = strtotime((string) $arr2['personal_freeleech']);
                    $body .= '
        <tr>
            <td>' . format_username((int) $arr2['id']) . '</td>
            <td>' . get_user_class_name((int) $arr2['class']);

                    if (!has_access((int) $arr2['class'], UC_ADMINISTRATOR, 'coder') && (int) $arr2['id'] !== (int) ($currentUser['id'] ?? 0)) {
                        $body .= '</td>
            <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_freeleech, 'DATE'), mkprettytime($personal_freeleech - $dt)) . "</td>
            <td><span class='has-text-danger'>" . _('Not Allowed') . '</span></td>
        </tr>';
                    } else {
                        $body .= '</td>
            <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_freeleech, 'DATE'), mkprettytime($personal_freeleech - $dt)) . "</td>
            <td><a href='" . $baseurl . "/staffpanel.php?tool=freeusers&amp;remove=" . (int) $arr2['id'] . "' onclick=\"return confirm('" . _('Are you sure you want to remove this users Freeleech Status?') . "')\">" . _('Remove') . '</a></td>
        </tr>';
                    }
                }

                $HTMLOUT .= ($count > $perpage ? $pager['pagertop'] : '') . main_table($body, $heading) . ($count > $perpage ? $pager['pagerbottom'] : '');
            }

            $title = _('Freeleech Manager');
            $breadcrumbs = [
                "<a href='{$baseurlEscaped}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

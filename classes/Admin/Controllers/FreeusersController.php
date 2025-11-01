<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Psr\Container\ContainerInterface;

final class FreeusersController
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

            $dt = TIME_NOW;
            $HTMLOUT = '';

            $remove = isset($_GET['remove']) ? (int) $_GET['remove'] : 0;
            if ($remove) {
                $user = $db->fetch(
                    'SELECT id, username, class FROM users WHERE personal_freeleech > NOW() AND id = :id',
                    [':id' => $remove]
                );
                if (!$user) {
                    stderr(_('Error'), _('Invalid user or Freeleech already expired.'));
                }

                $modline = get_date((int) $dt, 'DATE', 1) . ' - ' . _fe('Freeleech On All Torrents removed by {0}', $CURUSER['username']) . " \n";

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
                    $CURUSER['id'] ?? null,
                    'config.update',
                    [
                        'keys' => ['freeleech.user'],
                        'target' => (int) $user['id'],
                        'op' => 'remove',
                    ]
                );

                $messages_class = $container->get(Message::class);
                $msg = _fe('Freeleech On All Torrents have been removed by {0}', $CURUSER['username']);
                $messages_class->insert([
                    [
                        'receiver' => (int) $user['id'],
                        'added'    => $dt,
                        'msg'      => $msg,
                        'subject'  => _('Freeleech Notice!'),
                    ],
                ]);

                $cache = $container->get(Cache::class);
                $cache->delete('inbox_' . (int) $user['id']);
            }

            $countRow = $db->fetch('SELECT COUNT(id) AS count FROM users WHERE personal_freeleech > NOW()');
            $count = (int) ($countRow['count'] ?? 0);

            $perpage = 25;
            $pager = pager($perpage, $count, (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=freeusers&amp;');

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

                    if (!has_access((int) $arr2['class'], UC_ADMINISTRATOR, 'coder') && (int) $arr2['id'] !== (int) $CURUSER['id']) {
                        $body .= '</td>
                        <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_freeleech, 'DATE'), mkprettytime($personal_freeleech - $dt)) . "</td>
                        <td><span class='has-text-danger'>" . _('Not Allowed') . '</span></td>
                    </tr>';
                    } else {
                        $body .= '</td>
                        <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_freeleech, 'DATE'), mkprettytime($personal_freeleech - $dt)) . "</td>
                        <td><a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=freeusers&amp;remove=" . (int) $arr2['id'] . "' onclick=\"return confirm('" . _('Are you sure you want to remove this users Freeleech Status?') . "')\">" . _('Remove') . '</a></td>
                    </tr>';
                    }
                }

                $HTMLOUT .= ($count > $perpage ? $pager['pagertop'] : '') . main_table($body, $heading) . ($count > $perpage ? $pager['pagerbottom'] : '');
            }

            $title = _('Freeleech Manager');
            $breadcrumbs = [
                "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Admin controller error (freeusers): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}

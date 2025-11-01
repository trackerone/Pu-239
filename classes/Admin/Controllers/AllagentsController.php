<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use Psr\Container\ContainerInterface;
use PU239\Config\ConfigRepository;
use PDO;

use Pu239\Database;
use PU239\Security\AuthZ;

final class AllagentsController
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
            global $container;
            $container = $this->container;
            $config = $this->config;
            $pdo = $this->pdo;

            // TODO(2025): inline legacy logic from admin/allagents.php (was using legacy require)

            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container;
            /** @var ContainerInterface $container */
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            // AUTO_ADMIN_MEDIUM: 2025-10-23; tool=codex-admin-medium-sweep; rules=2025.10.23-admin-medium

            $db     = $container->get(Database::class);
            $fluent = $db;

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $agents = $fluent->from('peers')
                ->select(null)
                ->select('agent')
                ->select('LEFT(peer_id, 8) AS peer_id')
                ->groupBy('agent')
                ->groupBy('peer_id')
                ->fetchAll();


            if (!empty($agents)) {
                $heading = '
                    <tr>
                        <th>' . _('Client') . '</th>
                        <th>' . _('Peer ID') . '</th>
                    </tr>';
                $body = '';
                foreach ($agents as $arr) {
                    $body .= '
                    <tr>
                        <td>' . format_comment($arr['agent']) . '</td>
                        <td>' . format_comment($arr['peer_id']) . '</td>
                    </tr>';
                }
                $HTMLOUT = main_table($body, $heading);
            } else {
                $HTMLOUT = stdmsg(_('Error'), _("There are no peers and therefore there are no client ID's"));
            }
            $title = _('Torrent Clients');
            $breadcrumbs = [
                "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Admin controller error (allagents): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}

<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:02:29Z via handler-convert offset=245 batch=2

namespace PU239\Http\Handlers\Admin;

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use PU239\Security\AuthZ;

final class AllagentsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:02:29Z via handler-convert offset=245 batch=2
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            $handlerPath = __FILE__;
            if (stripos($handlerPath, '/admin/') !== false) {
                // TODO(2025): reconcile legacy AuthZ conflict markers from admin/allagents.php
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escape($_SERVER['PHP_SELF'] ?? '');
            $baseUrl = $escape((string) $config->get('paths.baseurl'));

            $agents = $db->fetchAll(
                'SELECT agent, LEFT(peer_id, 8) AS peer_id
                   FROM peers
               GROUP BY agent, peer_id
               ORDER BY agent'
            );

            if ($agents !== []) {
                $heading = "
        <tr>
            <th>" . _('Client') . "</th>
            <th>" . _('Peer ID') . "</th>
        </tr>";
                $body = '';
                foreach ($agents as $row) {
                    $agent = format_comment($row['agent'] ?? '');
                    $peerId = format_comment($row['peer_id'] ?? '');
                    $body .= "
        <tr>
            <td>{$agent}</td>
            <td>{$peerId}</td>
        </tr>";
                }
                $HTMLOUT = main_table($body, $heading);
            } else {
                $HTMLOUT = stdmsg(_('Error'), _("There are no peers and therefore there are no client ID's"));
            }

            $title = _('Torrent Clients');
            $breadcrumbs = [
                "<a href='{$baseUrl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
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

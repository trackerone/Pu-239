<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;

final class TracerouteHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T17:02:40Z via codex handler conversion
        try {
            global $container;

            if (strpos(ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            /** @var Database $db */
            $db = $container->get(Database::class);
            unset($db);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $user = check_user_status();

            $HTMLOUT = '';
            $windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 1 : 0;
            $unix = $windows === 1 ? 0 : 1;
            $register_globals = (bool) ini_get('register_gobals');
            $action = '';
            $host = '';
            $system = ini_get('system');
            $unix = (bool) $unix;
            $win = (bool) $windows;
            unset($win);

            if ($register_globals) {
                $ip = getenv($_SERVER['REMOTE_ADDR'] ?? '');
                $self = $_SERVER['PHP_SELF'] ?? '';
            } else {
                $action = $_POST['action'] ?? '';
                $host = $_POST['host'] ?? '';
                $ip = getip($user['id']);
                $self = $_SERVER['SCRIPT_NAME'] ?? '';
            }

            if (($action ?? '') === 'do') {
                $host = preg_replace('/[^A-Za-z0-9.]/', '', (string) ($host ?? ''));
                $HTMLOUT .= '<div class="error">';
                $HTMLOUT .= '' . _('Trace Output:') . '<br>';
                $HTMLOUT .= '<pre>';
                if ($unix) {
                    system('' . 'traceroute ' . $host);
                    system('killall -q traceroute');
                } else {
                    system('' . 'tracert ' . $host);
                }
                $HTMLOUT .= '</pre>';
                $HTMLOUT .= '' . _('done...') . '</div>';
            } else {
                $HTMLOUT .= '
    <p><span class="size_3">' . _fe('Your IP is: {0}', $ip) . '</span></p>
    <form method="post" action="' . ($_SERVER['PHP_SELF'] ?? '') . '" accept-charset="utf-8">' . _('Enter IP or Host ') . '<input type="text" id=specialboxn name="host" value="' . $ip . '">
    <input type="hidden" name="action" value="do"><input type="submit" value="' . _('Traceroute!') . '" class="button is-small">
   </form>';
                $HTMLOUT .= '<br><b>' . $system . '</b>';
                $HTMLOUT .= '</body></html>';
            }

            $title = _('Traceroute');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
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

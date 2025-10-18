<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:25:00Z via handler-convert offset=180 size=5

namespace PU239\Http\Handlers\Public;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;
use Pu239\User;
use RuntimeException;

final class SearchHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:25:00Z via handler-convert offset=180 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var User $users */
            $users = $container->get(User::class);
            /** @var Session $session */
            $session = $container->get(Session::class);

            $data = array_merge($_GET, $_POST);
            $torrentPass = $data['torrent_pass'] ?? null;
            $auth = $data['auth'] ?? null;
            $bot = $data['bot'] ?? null;
            $search = $data['search'] ?? null;

            $userId = null;
            if (is_string($bot) && $bot !== '' && is_string($auth) && $auth !== '' && is_string($torrentPass) && $torrentPass !== '') {
                $userId = $users->get_bot_id($bot, $torrentPass, $auth);
            } else {
                $session->set('is-warning', _('The search page is a restricted page, bots only'));
                $baseUrl = (string) $config->get('paths.baseurl');
                header('Location: ' . $baseUrl . '/browse.php');
                app_halt('Exit called');
            }

            if (empty($userId)) {
                json_out(['msg' => _('invalid user credentials')]);
            }

            $status = $users->get_item('status', (int) $userId);
            if ($status === 5) {
                json_out(['msg' => _("Permission denied, you're account is suspended")]);
            } elseif ($status === 2) {
                json_out(['msg' => _("Permission denied, you're account is disabled")]);
            } elseif ($status === 1) {
                json_out(['msg' => _("Permission denied, you're account is parked")]);
            }

            if (is_string($search) && $search !== '') {
                $results = $db->fetchAll(
                    'SELECT id, name, hex(info_hash) AS info_hash FROM torrents WHERE name LIKE :search',
                    [
                        ':search' => '%' . $search . '%',
                    ],
                );

                if (!empty($results)) {
                    json_out($results);
                }

                json_out(['msg' => 'no results for: ' . $search]);
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

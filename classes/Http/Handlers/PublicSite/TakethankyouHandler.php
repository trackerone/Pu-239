<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T19:01:07Z via handler-convert offset=305 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;

use function dirname;
use function error_log;
use function is_valid_id;
use function sprintf;

final class TakethankyouHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T19:01:07Z via handler-convert offset=305 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $user = check_user_status();

            $bonusEnabled = (bool) $config->get('bonus.on');
            $bonusPerComment = (float) $config->get('bonus.per_comment');

            $requestId = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
            if ($requestId <= 0) {
                app_halt('Exit called');
            }

            if (!is_valid_id($requestId)) {
                stderr(_('Error'), _('Invalid ID'), 'bottom20');
            }

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                // TODO(2025): add CSRF verification for takethankyou submission
            }

            $torrent = $db->fetch(
                'SELECT id, thanks, comments FROM torrents WHERE id = :id',
                [
                    ':id' => [$requestId, \PDO::PARAM_INT],
                ],
            );

            if ($torrent === null) {
                stderr(_('Error'), _('Torrent not found'), 'bottom20');
            }

            $alreadyThanked = $db->fetchValue(
                'SELECT 1 FROM thankyou WHERE torid = :torid AND uid = :uid LIMIT 1',
                [
                    ':torid' => [$requestId, \PDO::PARAM_INT],
                    ':uid' => [$user['id'], \PDO::PARAM_INT],
                ],
            );

            if ($alreadyThanked !== null) {
                stderr(_('Error'), 'You have already thanked.', 'bottom20');
            }

            $text = ':thankyou:';
            $timestamp = TIME_NOW;

            // TODO(2025): confirm thankyou insert column mapping matches legacy literal
            $db->run(
                'INSERT INTO thankyou (torid, uid, thank_date) VALUES (:torid, :uid, :thank_date)',
                [
                    ':torid' => [$requestId, \PDO::PARAM_INT],
                    ':uid' => [$user['id'], \PDO::PARAM_INT],
                    ':thank_date' => [$timestamp, \PDO::PARAM_INT],
                ],
            );

            // TODO(2025): confirm comments insert column mapping matches legacy literal
            $db->run(
                'INSERT INTO comments (user, torrent, added, text, ori_text) VALUES (:user, :torrent, :added, :text, :ori_text)',
                [
                    ':user' => [$user['id'], \PDO::PARAM_INT],
                    ':torrent' => [$requestId, \PDO::PARAM_INT],
                    ':added' => [$timestamp, \PDO::PARAM_INT],
                    ':text' => [$text, \PDO::PARAM_STR],
                    ':ori_text' => [$text, \PDO::PARAM_STR],
                ],
            );

            $db->run(
                'UPDATE torrents SET thanks = thanks + 1, comments = comments + 1 WHERE id = :id',
                [
                    ':id' => [$requestId, \PDO::PARAM_INT],
                ],
            );

            $cache->deleteMulti([
                'latest_comments_',
                'torrent_details_' . $requestId,
            ]);

            if ($bonusEnabled) {
                $db->run(
                    'UPDATE users SET seedbonus = seedbonus + :amount WHERE id = :id',
                    [
                        ':amount' => [$bonusPerComment, \PDO::PARAM_STR],
                        ':id' => [$user['id'], \PDO::PARAM_INT],
                    ],
                );
            }

            /** @var Session $session */
            $session = $container->get(Session::class);
            $session->set('is-success', "Your 'Thank you' has been registered!");

            header(sprintf('Refresh: 0; url=details.php?id=%d', $requestId));
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

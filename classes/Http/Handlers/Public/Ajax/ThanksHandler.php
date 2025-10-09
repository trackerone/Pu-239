<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5

namespace PU239\Http\Handlers\Public\Ajax;

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;

final class ThanksHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $baseurl = (string) $config->get('paths.baseurl');
            $s = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $user = check_user_status();

            if ($_POST === []) {
                stderr(_('Error'), _('Access Not Allowed'));
                header('Location: ' . $baseurl);
                app_halt('Exit called');
            }

            if ($user === false) {
                stderr(_('Error'), _("You can't add a thank you on your own torrent"));
                header('Location: ' . $baseurl);
                app_halt('Exit called');
            }

            // TODO(2025): csrf
            $uid = (int) $user['id'];
            $tid = (int) ($_POST['tid'] ?? ($_GET['tid'] ?? 0));
            $action = htmlsafechars($_POST['action'] ?? ($_GET['action'] ?? 'list'));
            $ajax = isset($_POST['ajax']) && (int) $_POST['ajax'] === 1;

            switch ($action) {
                case 'list':
                    $payload = $this->printList($db, $config, $cache, $s, $uid, $tid, $ajax);

                    if ($ajax) {
                        json_out($payload);
                    }

                    // TODO(2025): review escaping strategy for $payload output
                    echo $payload; // noescape
                    echo $payload;
                    break;

                case 'add':
                    if ($uid > 0 && $tid > 0) {
                        $exists = $db->fetch(
                            'SELECT COUNT(id) AS count FROM thanks WHERE userid = :uid AND torrentid = :tid',
                            [
                                'uid' => [$uid, \PDO::PARAM_INT],
                                'tid' => [$tid, \PDO::PARAM_INT],
                            ]
                        );

                        if ((int) ($exists['count'] ?? 0) === 0) {
                            try {
                                $db->beginTransaction();
                                $db->run(
                                    'INSERT INTO thanks (userid, torrentid) VALUES (:uid, :tid)',
                                    [
                                        'uid' => [$uid, \PDO::PARAM_INT],
                                        'tid' => [$tid, \PDO::PARAM_INT],
                                    ]
                                );

                                if ((bool) $config->get('bonus.on')) {
                                    $db->run(
                                        'UPDATE users SET seedbonus = seedbonus + :bonus WHERE id = :id',
                                        [
                                            'bonus' => [(float) $config->get('bonus.per_thanks'), \PDO::PARAM_STR],
                                            'id' => [$uid, \PDO::PARAM_INT],
                                        ]
                                    );
                                }

                                $db->commit();
                            } catch (\Throwable $e) {
                                $db->rollBack();
                                break;
                            }

                            if ((bool) $config->get('bonus.on')) {
                                $updatedUser = $db->fetch(
                                    'SELECT seedbonus FROM users WHERE id = :id',
                                    ['id' => [$uid, \PDO::PARAM_INT]]
                                );

                                $cache->update_row(
                                    'user_' . $uid,
                                    [
                                        'seedbonus' => (int) ($updatedUser['seedbonus'] ?? 0),
                                    ],
                                    (int) $config->get('expires.user_cache')
                                );
                            }

                            $payload = $this->printList($db, $config, $cache, $s, $uid, $tid, $ajax);

                            if ($ajax) {
                                json_out($payload);
                            }

                            // TODO(2025): review escaping strategy for $payload output
                            echo $payload;
                        }
                    }
                    break;
            }

            app_halt('Exit called');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    /**
     * @param callable(mixed):string $s
     * @return array<string,mixed>|string
     */
    private function printList(Database $db, ConfigRepository $config, Cache $cache, callable $s, int $uid, int $tid, bool $ajax): array|string
    {
        $rows = $db->fetchAll(
            'SELECT th.userid, u.username, u.seedbonus FROM thanks AS th INNER JOIN users AS u ON u.id = th.userid WHERE th.torrentid = :tid ORDER BY u.class DESC',
            ['tid' => [$tid, \PDO::PARAM_INT]]
        );

        $list = [];
        $ids = [];

        foreach ($rows as $row) {
            $list[] = format_username((int) $row['userid']);
            $ids[] = (int) $row['userid'];
        }

        $hadThanks = in_array($uid, $ids, true);

        if ($ajax) {
            return [
                'list' => $list === [] ? '' : implode(', ', $list),
                'hadTh' => $hadThanks,
                'status' => true,
            ];
        }

        $form = '';

        if (!$hadThanks) {
            $actionUrl = $s((string) $config->get('paths.baseurl')) . '/ajax/thanks.php';
            $form = "<span class='left10'><form action='" . $actionUrl . "' method='post' enctype='multipart/form-data' accept-charset='utf-8'><input type='submit' class='button is-small details-button' name='submit' value='Say thanks'><input type='hidden' name='torrentid' value='" . $s($tid) . "'><input type='hidden' name='action' value='add'></form></span>";
        }

        $out = $list === [] ? '' : implode(', ', $list);

        return <<<IFRAME
<!doctype html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
<style>
body { margin:0;padding:0;
       font-size:12px;
       font-family:arial,sans-serif;
       color: #fff;
}
a, a:link, a:visited {
  text-decoration: none;
  color: #fff;
  font-size:12px;
}
a:hover {
  color: #fff;
  text-decoration:underline;

}
.btn {
background-color:#890537;
border:1px solid #000000;
color:#fff;
font-family:arial,sans-serif;
font-size:12px;
padding:1px 3px;
}
</style>
<title>::</title>
</head>
<body>
{$out}{$form}
</body>
</html>
IFRAME;
    }
}

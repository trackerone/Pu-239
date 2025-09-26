<?php

declare(strict_types=1);

<<<<<< codex/enforce-csrf-and-escape-output-hay3lv
require_once dirname(__DIR__) . '/bootstrap_web.php';

=======
<<<<<< codex/enforce-csrf-and-escape-output-dxtuor
require_once dirname(__DIR__) . '/bootstrap_web.php';

=======
>>>>>> master
>>>>>> master
use DI\DependencyException;
use DI\NotFoundException;
use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;

<<<<<< codex/enforce-csrf-and-escape-output-hay3lv
=======
<<<<<< codex/enforce-csrf-and-escape-output-dxtuor
=======
require_once dirname(__DIR__) . '/bootstrap_web.php';

>>>>>> master
>>>>>> master
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);
$cache = $container->get(Cache::class);
$baseurl = (string) $config->get('paths.baseurl');

require_once __DIR__ . '/../../include/bittorrent.php';

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

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

if ($ajax) {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function print_list(int $uid, int $tid, bool $ajax)
{
    global $cache, $config, $db, $s;

    $baseurl = (string) $config->get('paths.baseurl');

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
        return json_encode([
            'list' => $list === [] ? '' : implode(', ', $list),
            'hadTh' => $hadThanks,
            'status' => true,
        ], JSON_THROW_ON_ERROR);
<<<<<< codex/enforce-csrf-and-escape-output-hay3lv
    }

    $form = '';

    if (!$hadThanks) {
        $actionUrl = $s($baseurl) . '/ajax/thanks.php';
        $form = "<span class='left10'><form action='" . $actionUrl . "' method='post' enctype='multipart/form-data' accept-charset='utf-8'><input type='submit' class='button is-small details-button' name='submit' value='Say thanks'><input type='hidden' name='torrentid' value='" . $s($tid) . "'><input type='hidden' name='action' value='add'></form></span>";
    }

=======
    }

    $form = '';

    if (!$hadThanks) {
        $actionUrl = $s($baseurl) . '/ajax/thanks.php';
        $form = "<span class='left10'><form action='" . $actionUrl . "' method='post' enctype='multipart/form-data' accept-charset='utf-8'><input type='submit' class='button is-small details-button' name='submit' value='Say thanks'><input type='hidden' name='torrentid' value='" . $s($tid) . "'><input type='hidden' name='action' value='add'></form></span>";
    }

>>>>>> master
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

switch ($action) {
    case 'list':
        echo print_list($uid, $tid, $ajax);
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

                echo print_list($uid, $tid, $ajax);
            }
        }
        break;
}

app_halt('Exit called');

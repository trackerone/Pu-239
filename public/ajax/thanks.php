<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Database;
use Pu239\Cache;
use DI\DependencyException;
use DI\NotFoundException;

global $container, $site_config;
$db = $container->get(Database::class);
$cache = $container->get(Cache::class);

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();

if (empty($_POST)) {
    stderr(_('Error'), _('Access Not Allowed'));
    header("Location: {$site_config['paths']['baseurl']}");
    app_halt('Exit called');
}

if (!isset($user)) {
    stderr(_('Error'), _("You can't add a thank you on your own torrent"));
    header("Location: {$site_config['paths']['baseurl']}");
    app_halt('Exit called');
}

$uid = (int) $user['id'];
$tid = (int) ($_POST['tid'] ?? ($_GET['tid'] ?? 0));
$do = htmlsafechars($_POST['action'] ?? ($_GET['action'] ?? 'list'));
$ajax = isset($_POST['ajax']) && (int) $_POST['ajax'] === 1;

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function print_list(int $uid, int $tid, bool $ajax)
{
    global $db, $cache, $site_config;

    $rows = $db->fetchAll(
        'SELECT th.userid, u.username, u.seedbonus FROM thanks AS th INNER JOIN users AS u ON u.id = th.userid WHERE th.torrentid = :tid ORDER BY u.class DESC',
        [':tid' => $tid]
    );
    $list = $ids = [];
    foreach ($rows as $a) {
        $list[] = format_username((int) $a['userid']);
        $ids[] = (int) $a['userid'];
    }
    $hadTh = in_array($uid, $ids, true);

    if ($ajax) {
        return json_encode([
            'list' => (count($list) > 0 ? implode(', ', $list) : ''),
            'hadTh' => $hadTh,
            'status' => true,
        ]);
    }

    $form = !$hadTh ? "<span class='left10'><form action='{$site_config['paths']['baseurl']}/ajax/thanks.php' method='post'><input type='submit' class='button is-small details-button' name='submit' value='Say thanks'><input type='hidden' name='torrentid' value='{$tid}'><input type='hidden' name='action' value='add'></form></span enctype='multipart/form-data' accept-charset='utf-8'>" : '';
    $out = (count($list) > 0 ? implode(', ', $list) : '');

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

switch ($do) {
    case 'list':
        print print_list($uid, $tid, $ajax);
        break;

    case 'add':
        if ($uid > 0 && $tid > 0) {
            $arr = $db->fetch(
                'SELECT COUNT(id) AS c FROM thanks WHERE userid = :uid AND torrentid = :tid',
                [':uid' => $uid, ':tid' => $tid]
            );
            if ((int) ($arr['c'] ?? 0) === 0) {
                try {
                    $db->beginTransaction();
                    $db->run(
                        'INSERT INTO thanks (userid, torrentid) VALUES (:uid, :tid)',
                        [':uid' => $uid, ':tid' => $tid]
                    );
                    if ($site_config['bonus']['on']) {
                        $db->run(
                            'UPDATE users SET seedbonus = seedbonus + :bonus WHERE id = :id',
                            [
                                ':bonus' => $site_config['bonus']['per_thanks'],
                                ':id' => $uid,
                            ]
                        );
                    }
                    $db->commit();
                } catch (\Throwable $e) {
                    $db->rollBack();
                    break;
                }
                if ($site_config['bonus']['on']) {
                    $User = $db->fetch('SELECT seedbonus FROM users WHERE id = :id', [':id' => $uid]);
                    $cache->update_row(
                        'user_' . $uid,
                        [
                            'seedbonus' => (int) ($User['seedbonus'] ?? 0),
                        ],
                        $site_config['expires']['user_cache']
                    );
                }
                echo print_list($uid, $tid, $ajax);
            }
        }
        break;
}


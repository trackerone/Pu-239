<?php
declare(strict_types=1);
require_once __DIR__ . '/../include/runtime_safe.php';

use DI\DependencyException;
use DI\NotFoundException;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;
use Pu239\User;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws UnbegunTransaction
 * @throws \PDOException
 */
function stealth(int $userid, bool $stealth = true)
{
    global $container, $CURUSER, $db, $config;

    $users_class = $container->get(User::class);
    $username = $users_class->get_item('username', $userid);
    $setbits = $clrbits = 0;
    if ($stealth) {
        $display = 'is';
        $setbits |= PERMS_STEALTH;
    } else {
        $display = 'is not';
        $clrbits |= PERMS_STEALTH;
    }

    if ($setbits || $clrbits) {
        $db->run(
            'UPDATE users SET perms = ((perms | :setbits) & ~:clrbits) WHERE id = :id',
            [
                ':setbits' => (int) $setbits,
                ':clrbits' => (int) $clrbits,
                ':id' => $userid,
            ],
        );
    }
    $row = $db->fetch('SELECT username, perms, modcomment FROM users WHERE id = :id', [':id' => $userid]);
    $row['perms'] = (int) $row['perms'];
    $modcomment = \get_date((int) TIME_NOW, '', 1).' - '.$display.' in Stealth Mode thanks to '.$CURUSER['username']."\n".$row['modcomment'];
    $db->run('UPDATE users SET modcomment = :modcomment WHERE id = :id', [':modcomment' => $modcomment, ':id' => $userid]);
    $cache = $container->get(Cache::class);
    $cache->update_row('user_'.$userid, [
        'perms' => $row['perms'],
        'modcomment' => $modcomment,
    ], $config->get('expires.user_cache'));
    if ($userid === $CURUSER['id']) {
        $cache->update_row('user_'.$CURUSER['id'], [
            'perms' => $row['perms'],
            'modcomment' => $modcomment,
        ], $config->get('expires.user_cache'));
    }
    \write_log('Member [b][url=userdetails.php?id='.$userid.']'.(\htmlsafechars($row['username'])).'[/url][/b] '.$display.' in Stealth Mode thanks to [b]'.$CURUSER['username'].'[/b]');
    $session = $container->get(Session::class);
    $session->set('is-info', "{$username} $display Stealthy");
    \header("Location: {$config->get('paths.baseurl')}/userdetails.php?id=$userid");
    \app_halt('Exit called');
}

<?php

declare(strict_types=1);

require_once __DIR__.'/runtime_safe.php';
require_once __DIR__.'/bootstrap_pdo.php';

use DI\DependencyException;
use DI\NotFoundException;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;
use Pu239\User;

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws UnbegunTransaction
 * @throws \PDOException
 */
function stealth(int $userid, bool $stealth = true)
{
    global $container, $site_config, $CURUSER;
    $db = $container->get(Database::class);

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
        $db->run('UPDATE users SET perms = ((perms | '.$setbits.') & ~'.$clrbits.') WHERE id = :id', [':id' => $userid]);
    }
    $row = $db->fetch('SELECT username, perms, modcomment FROM users WHERE id = :id', [':id' => $userid]);
    $row['perms'] = (int) $row['perms'];
    $modcomment = \get_date((int) TIME_NOW, '', 1).' - '.$display.' in Stealth Mode thanks to '.$CURUSER['username']."\n".$row['modcomment'];
    $db->run('UPDATE users SET modcomment = :modcomment WHERE id = :id', [':modcomment' => $modcomment, ':id' => $userid]);
    $cache = $container->get(Cache::class);
    $cache->update_row('user_'.$userid, [
        'perms' => $row['perms'],
        'modcomment' => $modcomment,
    ], $site_config['expires']['user_cache']);
    if ($userid === $CURUSER['id']) {
        $cache->update_row('user_'.$CURUSER['id'], [
            'perms' => $row['perms'],
            'modcomment' => $modcomment,
        ], $site_config['expires']['user_cache']);
    }
    \write_log('Member [b][url=userdetails.php?id='.$userid.']'.(\htmlsafechars($row['username'])).'[/url][/b] '.$display.' in Stealth Mode thanks to [b]'.$CURUSER['username'].'[/b]');
    $session = $container->get(Session::class);
    $session->set('is-info', "{$username} $display Stealthy");
    \header("Location: {$site_config['paths']['baseurl']}/userdetails.php?id=$userid");
    \app_halt('Exit called');
}

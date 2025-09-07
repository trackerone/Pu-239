<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Cache;
use Pu239\Database;

global $container, $site_config;

$db = $container->get(Database::class);
$cache = $container->get(Cache::class);
$irc = $cache->get('ircusers_');
if ($irc === false || is_null($irc)) {
    $irc = $list = [];
    $query = $db->fetchAll('SELECT id FROM users WHERE onirc = :onirc AND perms < :perms AND anonymous_until < :now AND id != 2 ORDER BY username', [
        ':onirc' => 'yes',
        ':perms' => PERMS_STEALTH,
        ':now' => TIME_NOW,
    ]);

    $count = count($query);
    $i = 0;
    if ($count >= 100) {
        $irc['ircusers'] = format_comment(_('Too many to list here.'));
    } elseif ($count > 0) {
        foreach ($query as $row) {
            if (++$i != $count) {
                $list[] = format_username((int) $row['id'], true, true, false, true);
            } else {
                $list[] = format_username((int) $row['id']);
            }
        }
        $irc['ircusers'] = implode('&nbsp;&nbsp;', $list);
    } elseif ($count === 0) {
        $irc['ircusers'] = _('There have been no active irc users in the last 15 minutes.');
    }

    $irc['count'] = number_format($count);
    $cache->set('ircusers_', $irc, $site_config['expires']['activeircusers']);
}

$active_users_irc .= "
    <a id='irc-hash'></a>
    <div id='irc' class='box'>
        <div class='bordered'>
            <div class='alt_bordered bg-00 level-center-center is-wrapped padding20'>
                {$irc['ircusers']}
            </div>
        </div>
    </div>";

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Cache;
use Pu239\Database;

global $container, $site_config;

$db = $container->get(Database::class);
$cache = $container->get(Cache::class);
$active24 = $cache->get('last24_users_');
if ($active24 === false || is_null($active24)) {
    $list = [];
    $record = $db->fetch('SELECT value_i, value_u FROM avps WHERE arg = :arg', [':arg' => 'last24']);

    $dt = TIME_NOW - 86400;
    $query = $db->fetchAll('SELECT id FROM users WHERE last_access > :dt AND anonymous_until < :now AND perms < :perms AND id != 2 ORDER BY username', [
        ':dt' => $dt,
        ':now' => TIME_NOW,
        ':perms' => PERMS_STEALTH,
    ]);

    $count = count($query);
    $i = 0;
    if ($count >= 100) {
        $active24['activeusers24'] = format_comment(_('Too many to list here.'));
    } elseif ($count > 0) {
        foreach ($query as $row) {
            if (++$i != $count) {
                $list[] = format_username((int) $row['id'], true, true, false, true);
            } else {
                $list[] = format_username((int) $row['id']);
            }
        }
        $active24['activeusers24'] = implode('&nbsp;&nbsp;', $list);
    } elseif ($count === 0) {
        $active24['activeusers24'] = _('There have been no active users in the last 15 minutes.');
    }
    $active24['totalonline24'] = number_format($count);
    $active24['last24'] = number_format($record['value_i']);
    $active24['record'] = get_date((int) $record['value_u'], '');
    if ($count > $record['value_i']) {
        $db->run('UPDATE avps SET value_s = :value_s, value_i = :value_i, value_u = :value_u WHERE arg = :arg', [
            ':value_s' => 0,
            ':value_i' => $count,
            ':value_u' => TIME_NOW,
            ':arg' => 'last24',
        ]);
    }

    $cache->set('last24_users_', $active24, $site_config['expires']['last24']);
}

$active_users_24 .= "
        <a id='active24-hash'></a>
        <div id='active24' class='box'>
            <div class='bordered'>
                <div class='alt_bordered bg-00 has-text-centered'>
                    <div class='bg-00 padding10 bottom10 round5 size_5'>
                        " . _pfe('{0} Member visited during the last 24 hours', '{0} Members visited during the last 24 hours', $active24['totalonline24']) . "
                    </div>
                    <div class='top10 bottom10 level-center-center is-wrapped top10 bottom10 padding20'>
                        {$active24['activeusers24']}
                    </div>
                    <div class='bg-00 padding10 has-text-centered round5 size_3'>
                        " . _fe('Most ever visited in 24 hours was {0} Members on {1}', $active24['last24'], $active24['record']) . '
                    </div>
                </div>
            </div>
        </div>';

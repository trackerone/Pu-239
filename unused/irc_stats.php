<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

$hash = 'YXBwemZhbg';
$_hash = isset($_GET['hash']) ? $_GET['hash'] : '';
$_user = isset($_GET['u']) ? htmlsafechars($_GET['u']) : '';
$valid_do = [
    'stats',
    'torrents',
    'fls',
    'irc',
    'top_idle',
    'top_uploaders',
    'top_posters',
    'top_torrents',
];
$_do = isset($_GET['do']) && in_array($_GET['do'], $valid_do) ? $_GET['do'] : '';
/**
 * @param $val
 *
 * @return string
 */
function calctime($val)
{
    $days = (int) ($val / 86400);
    $val -= $days * 86400;
    $hours = (int) ($val / 3600);
    $val -= $hours * 3600;
    $mins = (int) ($val / 60);

    return "$days days, $hours hrs, $mins minutes";
}

if (substr($_do, 0, 3) === 'top') {
    $_type = end(explode('_', $_do));
    $_do = 'top';
}
//$_hash = "YXBwemZhbg";
if ($_hash === $hash) {
    require_once __DIR__ . '/include/bittorrent.php';
    if (empty($_user) && ($_do === 'stats' || $_do === 'torrents' || $_do === 'irc')) {
        app_halt("Can't find the username");
    }
    if ($_do === 'stats') {
        $q = sql_query('SELECT id, username, last_access, downloaded, uploaded, added, status, warned, disable_reason, warn_reason FROM users WHERE username = ' . sqlesc($_user)) or app_halt(((is_object($mysqli)) ? mysqli_error($mysqli) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
        if (mysqli_num_rows($q) == 1) {
            $a = mysqli_fetch_assoc($q);
            $txt = $a['username'] . ' is ' . ((TIME_NOW - $a['last_access']) < 300 ? 'online' : 'offline') . "\nJoined - " . get_date((int) $a['added'], 'LONG', 0, 1) . "\nLast seen - " . get_date((int) $a['last_access'], 'DATE', 0, 1) . "\nDownloaded - " . mksize($a['downloaded']) . "\nUploaded - " . mksize($a['uploaded']) . "\n";
            if ($a['status'] === 'disabled') {
                $txt .= 'This user is disabled. Reason ' . $a['disable_reason'] . "\n";
            }
            if ($a['warned'] === 'yes') {
                $txt .= 'This user is warned. Reason ' . $a['warn_reason'] . "\n";
            }
            $txt .= $site_config['paths']['baseurl'] . '/userdetails.php?id=' . $a['id'];
            echo $txt;
        } else {
            app_halt('User "' . $_user . '" not found!');
        }
        unset($txt, $a, $q);
    } elseif ($_do === 'torrents') {
        $q = sql_query('SELECT count(p.id) AS count, p.seeder,p.agent,p.port,p.connectable, u.username FROM peers AS p LEFT JOIN users AS u ON u.id=p.userid WHERE u.username=' . sqlesc($_user) . ' GROUP BY p.seeder') or app_halt(((is_object($mysqli)) ? mysqli_error($mysqli) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
        if (mysqli_num_rows($q) == 0) {
            app_halt('User "' . $_user . '"  has no torrent active');
        }
        $act['seed'] = $act['leech'] = 0;
        $con = 'no';
        while ($a = mysqli_fetch_assoc($q)) {
            $key = ($a['seeder'] === 'yes' ? 'seed' : 'leech');
            $act[$key] = $a['count'];
            $agent = $a['agent'];
            $port = $a['port'];
            $con = $a['connectable'];
            $user = $a['username'];
        }
        $txt = $user . ' is ' . ($con === 'yes' ? 'connectable' : 'not connectable') . "\nActive torrents\n seeding - " . number_format($act['seed']) . ' | leeching - ' . number_format($act['leech']) . "\nAgent - " . $agent . ' | Port - ' . $port;
        echo $txt;
        unset($txt, $a, $q);
    } elseif ($_do === 'fls') {
        $q = sql_query("SELECT id,username,last_access ,supportfor FROM users WHERE support = 'yes' ORDER BY added DESC") or app_halt(((is_object($mysqli)) ? mysqli_error($mysqli) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
        $txt = '';
        while ($a = mysqli_fetch_assoc($q)) {
            $txt .= $a['username'] . ' - status ' . ((TIME_NOW - $a['last_access']) < 300 ? 'online' : 'offline') . ' | Support for ' . $a['supportfor'] . "\n";
            unset($support);
        }
        echo $txt;
        unset($_fls, $a, $q, $txt);
    } elseif ($_do === 'irc') {
        $q = sql_query('SELECT onirc, irctotal,username FROM users WHERE username = ' . sqlesc($_user)) or app_halt(((is_object($mysqli)) ? mysqli_error($mysqli) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
        if (mysqli_num_rows($q) == 0) {
            app_halt('User "' . $_user . '" not found!');
        }
        $a = mysqli_fetch_assoc($q);
        $txt = $a['username'] . ' ' . ($a['irctotal'] == 0 ? 'never been on irc' : 'has idled on irc ' . calctime($a['irctotal'])) . "\nAnd now he " . ($a['onirc'] === 'yes' ? 'is' : "isn't") . ' on irc';
        echo $txt;
        unset($a, $q, $txt);
    } elseif ($_do === 'top') {
        switch ($_type) {
            case 'idle':
                $_q = 'SELECT username,irctotal FROM users ORDER BY irctotal DESC LIMIT 10';
                $txt = "Top 10 idle\n";
                break;

            case 'uploaders':
                $_q = "SELECT username, uploaded FROM users WHERE status = 'confirmed' ORDER BY uploaded DESC LIMIT 10";
                $txt = "Best uploaders (selected after uploaded amount)\n";
                break;

            case 'torrents':
                $_q = "SELECT count(t.id) AS c, u.username FROM torrents AS t LEFT JOIN users AS u ON t.owner = u.id WHERE u.username <> '' GROUP  BY u.id ORDER BY c DESC LIMIT 10";
                $txt = "Best uploaders (selected after the torrents uploaded)\n";
                break;

            case 'posters':
                $_q = "SELECT count(p.id) AS c, u.username FROM posts AS p LEFT JOIN users AS u ON p.user_id=u.id WHERE u.username <> '' GROUP  BY u.id ORDER BY c DESC LIMIT 10";
                $txt = "Best posters (selected after number of posts)\n";
                break;
        }
        $i = 1;
        $q = sql_query($_q) or app_halt(((is_object($mysqli)) ? mysqli_error($mysqli) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
        while ($a = mysqli_fetch_assoc($q)) {
            $txt .= $i . ' - ' . $a['username'] . ' with ' . ($_type === 'idle' ? calctime($a['irctotal']) . ' idle' : ($_type === 'uploaders' ? mksize($a['uploaded']) . ' uploaded' : ($_type === 'torrents' ? $a['c'] . ' torrents' : $a['c'] . ' posts'))) . "\n";
            ++$i;
        }
        echo $txt;
        unset($a, $q, $txt);
    }
}

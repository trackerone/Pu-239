<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

require_once __DIR__ . '/include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_onlinetime.php';
$password = 'adlsadladadll'; // same as in staff.tcl;
$hash = 'adlsadladadll'; // same as in staff.tcl;
$modclass = '4'; // minumum staff class;
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
    //$secs = $val - ($mins * 60);

    return "$days days, $hours hrs, $mins minutes";
}

if ((isset($_GET['pass']) && $_GET['pass'] == $password) && (isset($_GET['hash']) && $_GET['hash'] == $hash)) {
    $seedingbonus = 0;
    $meinvite = $whominvite = [];
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
        $query = 'username = ' . sqlesc("$search") . " AND status='confirmed'";
        $rows = $db->fetchAll("SELECT * FROM users WHERE $query ORDER BY username");
        $num = mysqli_num_rows($res);
        if ($num < 1) {
            echo $search . ' - ' . _('No such user, please try again.');
        }
        if ($num > 0) {
            $arr = mysqli_fetch_assoc($res);
            $id = isset($arr['id']) ? (int) $arr['id'] : 0;
            $seedingbonus = isset($arr['seedbonus']) ? (int) $arr['seedbonus'] : 0;
            $username = htmlsafechars($arr['username']);
            if (isset($_GET['func']) && $_GET['func'] === 'stats') {
                $ratio = (($arr['downloaded'] > 0) ? ($arr['uploaded'] / $arr['downloaded']) : '0.00');
                $lastseen = htmlsafechars($arr['last_access']);
                echo htmlsafechars($arr['username']) . ' - Uploaded: (' . mksize($arr['uploaded']) . ') - Downloaded: (' . mksize($arr['downloaded']) . ') - Ratio: (' . number_format($ratio, 2) . ') - Invites: (' . (int) $arr['invites'] . ') - Joined: (' . get_date((int) $arr['added'], 'DATE', 0, 1) . '' . ') - Online time: (' . time_return($arr['onlinetime']) . ') - Last Seen: (' . get_date((int) $lastseen, 'DATE', 0, 1) . ')';
            } elseif (isset($_GET['func']) && $_GET['func'] === 'check') {
                echo htmlsafechars($arr['username']) . ' - Seedbonus: (' . number_format((float) $arr['seedbonus'], 1) . ')';
            } elseif (isset($_GET['func']) && $_GET['func'] === 'ircbonus') {
                $ircbonus = (!empty($arr['irctotal']) ? number_format($arr['irctotal'] / ($site_config['irc']['autoclean_interval'] * 4), 1) : '0.0');
                echo $arr['username'] . ' - IRC Bonus: (' . $ircbonus . ')';
            } elseif (isset($_GET['func']) && $_GET['func'] === 'irctotal') {
                $irctotal = (!empty($arr['irctotal']) ? calctime($arr['irctotal']) : $arr['username'] . ' has never been on IRC!');
                echo $arr['username'] . ' - IRC Total: (' . $irctotal . ')';
            } elseif (isset($_GET['func']) && $_GET['func'] === 'connectable') {
                $res5 = $db->run(');
        } else {
            if ($nnewname) {
                echo $newname . ' - ' . _('Is taken, please try again.');
            } else {
                $modd = isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '';
                $newusername = isset($_GET['newname']) ? htmlsafechars($_GET['newname']) : '';
                $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's name was changed from: ' . $who . ' to ' . $newusername . ' by ' . $modd . "\n");
                $db->run(");
                $cache->update_row('user_' . $nsetusername['id'], [
                    'username' => $newname,
                    'modcomment' => $modcomment,
                ], $site_config['expires']['user_cache']);
                echo $who . 's name was changed from: ' . $who . ' to ' . $newusername . ' by ' . $modd;
            }
        }
    } elseif (isset($_GET['topirc'])) {
        $rows = $db->fetchAll("SELECT id, username, class, irctotal FROM users WHERE onirc = 'yes' GROUP BY class ORDER BY irctotal DESC");
        foreach ($rows as $arr) {
            $ircbonus = (!empty($arr['irctotal']) ? number_format($arr['irctotal'] / ($site_config['irc']['autoclean_interval'] * 4), 1) : '0.0');
            $ircusers = isset($ircusers) ? $ircusers : '';
            if ($ircusers) {
                $ircusers .= ",\n";
            }
            $arr['username'] = '' . get_user_class_name((int) $arr['class']) . ' Leader is : ' . $arr['username'] . '(' . $ircbonus . ')';
            $ircusers .= $arr['username'];
        }
        if (!isset($ircusers)) {
            $ircusers = 'wtf!';
        }
        echo $ircusers;
    } elseif (isset($_GET['torrents'])) {
        $rows = $db->fetchAll("SELECT COUNT(id) FROM torrents WHERE visible='yes'");
        $row = mysqli_fetch_array($res, MYSQLI_NUM);
        $count = $row[0];
        echo '-' . $count . ' torrents found';
    } elseif (isset($_GET['includedead'])) {
        $rows = $db->fetchAll('SELECT COUNT(id) FROM torrents');
        $row = mysqli_fetch_array($res, MYSQLI_NUM);
        $count = $row[0];
        echo '-' . $count . ' torrents found';
    } elseif (isset($_GET['onlydead'])) {
        $rows = $db->fetchAll("SELECT COUNT(id) FROM torrents WHERE visible='no'");
        $row = mysqli_fetch_array($res, MYSQLI_NUM);
        $count = $row[0];
        echo '-' . $count . ' torrents found';
    } elseif (isset($_GET['noseeds'])) {
        $rows = $db->fetchAll("SELECT COUNT(id) FROM torrents WHERE seeders = '0'");
        $row = mysqli_fetch_array($res, MYSQLI_NUM);
        $count = $row[0];
        echo '-' . $count . ' torrents found';
    } elseif (isset($_GET['func']) && $_GET['func'] === 'add') {
        if (isset($_GET['bonus'])) {
            $whom = isset($_GET['whom']) ? sqlesc($_GET['whom']) : '';
            $rows = $db->fetchAll("SELECT id, seedbonus FROM users WHERE username = $whom LIMIT 1");
            $nbonus = mysqli_fetch_assoc($res);
            $who = isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '';
            if ($nbonus < 1) {
                echo $who . ' - ' . _('No such user, please try again.');
            } else {
                $oldbonus = $nbonus['seedbonus'];
                $amount = isset($_GET['amount']) ? (int) ($_GET['amount']) : '';
                $db->run(');
            } else {
                $oldinvites = (int) $ninvites['invites'];
                $amount = isset($_GET['amount']) && $_GET['amount'] > 0 ? (int) $_GET['amount'] : '';
                $db->run(');
            } else {
                $oldfreeslots = (int) $nfreeslots['freeslots'];
                $amount = isset($_GET['amount']) && $_GET['amount'] > 0 ? (int) $_GET['amount'] : 0;
                $db->run(');
            } else {
                $oldreputation = (int) $nreputation['reputation'];
                $amount = isset($_GET['amount']) && $_GET['amount'] > 0 ? (int) $_GET['amount'] : 0;
                $db->run(');
            } else {
                $oldbonus = $nbonus['seedbonus'];
                $amount = isset($_GET['amount']) ? number_format((int) $_GET['amount']) : 0;
                $db->run(');
            } else {
                $oldinvites = (int) $ninvites['invites'];
                $amount = isset($_GET['amount']) && $_GET['amount'] > 0 ? (int) $_GET['amount'] : 0;
                $db->run(');
            } else {
                $oldfreeslots = (int) $nfreeslots['freeslots'];
                $amount = isset($_GET['amount']) && $_GET['amount'] > 0 ? (int) $_GET['amount'] : 0;
                $db->run(');
            } else {
                $oldreputation = (int) $nreputation['reputation'];
                $amount = isset($_GET['amount']) && $_GET['amount'] > 0 ? (int) $_GET['amount'] : 0;
                $db->run(');
            } else {
                $meoldbonus = $mebonus['seedbonus'];
                $whomoldbonus = $whombonus['seedbonus'];
                $amount = isset($_GET['amount']) && ($_GET['amount'] > 0) ? (int) ($_GET['amount']) : 0;
                if ($amount <= $meoldbonus) {
                    $db->run(');
            } else {
                $meoldfreeslots = $mefreeslots['freeslots'];
                $whomoldfreeslots = $whomfreeslots['freeslots'];
                $amount = isset($_GET['amount']) && ($_GET['amount'] > 0) ? (int) ($_GET['amount']) : 0;
                if ($amount <= $meoldfreeslots) {
                    $db->run(');
            } else {
                $meoldreputation = $mereputation['reputation'];
                $whomoldreputation = $whomreputation['reputation'];
                $amount = isset($_GET['amount']) && ($_GET['amount'] > 0) ? (int) ($_GET['amount']) : 0;
                if ($amount <= $meoldreputation) {
                    $db->run(');
            } else {
                $meoldinvites = $meinvites['invites'];
                $whomoldinvites = $whominvites['invites'];
                $amount = (isset($_GET['amount']) && ($_GET['amount'] > 0) ? (int) ($_GET['amount']) : '');
                if ($amount <= $meoldinvites) {
                    $db->run(');
            $who = (isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '');
            $rows = $db->fetchAll("SELECT id, uploadpos FROM users WHERE username = $whom AND class < $modclass LIMIT 1");
            $upos = mysqli_fetch_assoc($res);
            if ($upos < 1) {
                echo $who . ' - No such user or is staff, please try again.';
            } else {
                $newpos = (isset($upos['uploadpos']) ? htmlsafechars($upos['uploadpos']) : '');
                $modd = (isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '');
                $toggle = (isset($_GET['toggle']) ? htmlsafechars($_GET['toggle']) : '');
                $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's uploadpos changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd . "\n");
                $db->run(");
                $cache->update_row('user_' . $upos['id'], [
                    'uploadpos' => $toggle,
                    'modcomment' => $modcomment,
                ], $site_config['expires']['user_cache']);
                echo $who . 's uploadpos changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd;
            }
        }
    } elseif (isset($_GET['downloadpos'])) {
        if ((isset($_GET['toggle']) && $_GET['toggle'] == 1) || (isset($_GET['toggle']) && $_GET['toggle'] == 0)) {
            $whom = (isset($_GET['whom']) ? sqlesc($_GET['whom']) : '');
            $who = (isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '');
            $rows = $db->fetchAll("SELECT id, downloadpos FROM users WHERE username = $whom AND class < $modclass LIMIT 1");
            $dpos = mysqli_fetch_assoc($res);
            if ($dpos < 1) {
                echo $who . ' - No such user or is staff, please try again.';
            } else {
                $newpos = (isset($dpos['downloadpos']) ? htmlsafechars($dpos['downloadpos']) : '');
                $modd = (isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '');
                $toggle = (isset($_GET['toggle']) ? htmlsafechars($_GET['toggle']) : '');
                $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's downloadpos changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd . "\n");
                $db->run(");
                $cache->update_row('user_' . $dpos['id'], [
                    'downloadpos' => $toggle,
                    'modcomment' => $modcomment,
                ], $site_config['expires']['user_cache']);
                echo $who . 's downloadpos changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd;
            }
        }
    } elseif (isset($_GET['forum_post'])) {
        if ((isset($_GET['toggle']) && $_GET['toggle'] === 'yes') || (isset($_GET['toggle']) && $_GET['toggle'] === 'no')) {
            $whom = (isset($_GET['whom']) ? sqlesc($_GET['whom']) : '');
            $who = (isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '');
            $rows = $db->fetchAll("SELECT id, forum_post FROM users WHERE username = $whom AND class < $modclass LIMIT 1");
            $fpos = mysqli_fetch_assoc($res);
            if ($fpos < 1) {
                echo $who . ' - No such user or is staff, please try again.';
            } else {
                $newpos = (isset($fpos['forum_post']) ? htmlsafechars($fpos['forum_post']) : '');
                $modd = (isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '');
                $toggle = (isset($_GET['toggle']) ? htmlsafechars($_GET['toggle']) : '');
                $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's forumpost changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd . "\n");
                $db->run(");
                $cache->update_row('user_' . $fpos['id'], [
                    'forum_post' => $toggle,
                    'modcomment' => $modcomment,
                ], $site_config['expires']['user_cache']);
                echo $who . 's forumpost changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd;
            }
        }
    } elseif (isset($_GET['chatpost'])) {
        if ((isset($_GET['toggle']) && $_GET['toggle'] == 1) || (isset($_GET['toggle']) && $_GET['toggle'] == 0)) {
            $whom = (isset($_GET['whom']) ? sqlesc($_GET['whom']) : '');
            $who = (isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '');
            $rows = $db->fetchAll("SELECT id, chatpost FROM users WHERE username = $whom AND class < $modclass LIMIT 1");
            $cpos = mysqli_fetch_assoc($res);
            if ($cpos < 1) {
                echo $who . ' - No such user or is staff, please try again.';
            } else {
                $newpos = (isset($cpos['chatpost']) ? htmlsafechars($cpos['chatpost']) : '');
                $modd = (isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '');
                $toggle = (isset($_GET['toggle']) ? htmlsafechars($_GET['toggle']) : '');
                $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's chatpost changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd . "\n");
                $db->run(");
                $cache->update_row('user_' . $cpos['id'], [
                    'chatpost' => $toggle,
                    'modcomment' => $modcomment,
                ], $site_config['expires']['user_cache']);
                echo $who . 's chatpost changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd;
            }
        }
    } elseif (isset($_GET['avatarpos'])) {
        if ((isset($_GET['toggle']) && $_GET['toggle'] == 1) || (isset($_GET['toggle']) && $_GET['toggle'] == 0)) {
            $whom = (isset($_GET['whom']) ? sqlesc($_GET['whom']) : '');
            $who = (isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '');
            $rows = $db->fetchAll("SELECT id, avatarpos FROM users WHERE username = $whom AND class < $modclass LIMIT 1");
            $apos = mysqli_fetch_assoc($res);
            if ($apos < 1) {
                echo $who . ' - No such user or is staff, please try again.';
            } else {
                $newpos = (isset($apos['avatarpos']) ? htmlsafechars($apos['avatarpos']) : '');
                $modd = (isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '');
                $toggle = (isset($_GET['toggle']) ? htmlsafechars($_GET['toggle']) : '');
                $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's avatarpos changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd . "\n");
                $db->run(");
                $cache->update_row('user_' . $apos['id'], [
                    'avatarpos' => $toggle,
                    'modcomment' => $modcomment,
                ], $site_config['expires']['user_cache']);
                echo $who . 's avatarpos changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd;
            }
        }
    } elseif (isset($_GET['invite_rights'])) {
        if ((isset($_GET['toggle']) && $_GET['toggle'] === 'yes') || (isset($_GET['toggle']) && $_GET['toggle'] === 'no')) {
            $whom = (isset($_GET['whom']) ? sqlesc($_GET['whom']) : '');
            $who = (isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '');
            $rows = $db->fetchAll("SELECT id, invite_rights FROM users WHERE username = $whom AND class < $modclass LIMIT 1");
            $ipos = mysqli_fetch_assoc($res);
            if ($ipos < 1) {
                echo $who . ' - No such user or is staff, please try again.';
            } else {
                $newpos = (isset($ipos['invite_on']) ? htmlsafechars($ipos['invite_on']) : '');
                $modd = (isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '');
                $toggle = (isset($_GET['toggle']) ? htmlsafechars($_GET['toggle']) : '');
                $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's invite rights changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd . "\n");
                $db->run(");
                $cache->update_row('user_' . $ipos['id'], [
                    'invite_rights' => $toggle,
                    'modcomment' => $modcomment,
                ], $site_config['expires']['user_cache']);
                echo $who . 's invite rights changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd;
            }
        }
    } elseif (isset($_GET['enabled'])) {
        if ((isset($_GET['toggle']) && $_GET['toggle'] === 'yes') || (isset($_GET['toggle']) && $_GET['toggle'] === 'no')) {
            $whom = (isset($_GET['whom']) ? sqlesc($_GET['whom']) : '');
            $who = (isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '');
            $rows = $db->fetchAll("SELECT id, enabled FROM users WHERE username = $whom AND class < $modclass LIMIT 1");
            $epos = mysqli_fetch_assoc($res);
            if ($epos < 1) {
                echo $who . ' - No such user or is staff, please try again.';
            } else {
                $newpos = (isset($epos['enabled']) ? htmlsafechars($epos['enabled']) : '');
                $modd = (isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '');
                $toggle = (isset($_GET['toggle']) ? htmlsafechars($_GET['toggle']) : '');
                $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's enabled changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd . "\n");
                $db->run(");
                $cache->update_row('user_' . $epos['id'], [
                    'enabled' => $toggle,
                    'modcomment' => $modcomment,
                ], $site_config['expires']['user_cache']);
                echo $who . 's enabled changed from: ' . $newpos . ' to ' . $toggle . ' by ' . $modd;
            }
        }
    } elseif (isset($_GET['addsupport'])) {
        //if((isset($_GET['toggle']) && $_GET['toggle'] == "yes") || (isset($_GET['toggle']) && $_GET['toggle'] == "no")){
        $whom = (isset($_GET['whom']) ? sqlesc($_GET['whom']) : '');
        $who = (isset($_GET['whom']) ? htmlsafechars($_GET['whom']) : '');
        $rows = $db->fetchAll("SELECT id, support, supportfor FROM users WHERE username = $whom AND class < $modclass LIMIT 1");
        $support = mysqli_fetch_assoc($res);
        if ($support < 1) {
            echo $who . ' - No such user or is staff, please try again.';
        } else {
            $newsupp = (isset($support['support']) ? htmlsafechars($support['support']) : '');
            $modd = (isset($_GET['mod']) ? htmlsafechars($_GET['mod']) : '');
            $supportfors = (isset($_GET['supportfor']) ? htmlsafechars($_GET['supportfor']) : '');
            $toggle = (isset($_GET['toggle']) ? htmlsafechars($_GET['toggle']) : '');
            $modcomment = sqlesc(get_date((int) TIME_NOW, 'DATE', 1) . ' IRC: ' . $who . 's support changed by ' . $modd . "\n");
            $db->run(");
            $cache->update_row('user_' . $support['id'], [
                'support' => 'yes',
                'supportfor' => $supportfors,
                'modcomment' => $modcomment,
            ], $site_config['expires']['user_cache']);
            echo $who . 's support changed added to First line support to cover ' . $supportfors . ' by ' . $modd;
        }
    }
    //} from ' . $newsupp . ' to '. $toggle . ', //== from: " . $newsupp . " to ". $toggle . "
} else {
    app_halt('your actions have been logged!');
}

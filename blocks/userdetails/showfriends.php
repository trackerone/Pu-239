<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;

global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
require_once INCL_DIR . 'function_users.php';
$db = $container->get(Database::class);
$dt = TIME_NOW - 180;
$keys['user_friends'] = 'user_friends_' . $id;
$cache = $container->get(Cache::class);
$users_friends = $cache->get($keys['user_friends']);
if ($users_friends === false || is_null($users_friends)) {
    $users_friends = $db->fetchAll(
        'SELECT friendid AS uid, userid, username, last_access, perms, uploaded, downloaded
            FROM friends
            INNER JOIN users ON users.id = friendid
            WHERE userid = ?
            ORDER BY username
            LIMIT 100',
        [$id]
    );
    $cache->set($keys['user_friends'], $users_friends, 86400);
}

if (!empty($users_friends)) {
    $user_friends = "<table>\n" . "<tr><td class='colhead'>" . _('Avatar') . "</td><td class='colhead'>" . _('Username') . '' . ($CURUSER['class'] >= UC_STAFF ? _('/Ip') : '') . "</td><td class='colhead'>" . _('Uploaded') . '</td>' . ($config->get('site.ratio_free') ? '' : "<td class='colhead'>" . _('Downloaded') . '</td>') . "<td class='colhead'>" . _('Ratio') . "</td><td class='colhead'>" . _('Status') . "</td></tr>\n";
    if ($users_friends) {
        foreach ($users_friends as $a) {
            $avatar = get_avatar($a);
            $status = "<img style='vertical-align: middle;' src='{$config->get('paths.images_baseurl')}" . ($a['last_access'] > $dt && !get_anonymous($a['uid']) ? 'online.png' : 'offline.png') . "' alt=''>";
            $user_friends .= "<tr><td class='has-text-centered w-15 mw-150'>" . $avatar . '</td><td>' . format_username($a['uid']) . "<br></td><td style='padding: 1px'>" . mksize($a['uploaded']) . '</td>' . ($config->get('site.ratio_free') ? '' : "<td style='padding: 1px'>" . mksize($a['downloaded']) . '</td>') . "<td style='padding: 1px'>" . member_ratio($a['uploaded'], $a['downloaded']) . "</td><td style='padding: 1px'>" . $status . "</td></tr>\n";
        }
        $user_friends .= '</table>';
        $HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Friends ') . "</td>
        <td>
            <span class='flipper'><i class='icon-up-open size_2' aria-hidden='true'></i>" . _('Friends ') . "</span>
            <div style='display: none;'>$user_friends</div>
        </td>
    </tr>";
    }
} else {
    if (empty($users_friends)) {
        $HTMLOUT .= '
        <tr>
            <td>' . _('Friends ') . '</td>
            <td>' . _('No Friends yet.') . '</td>
        </tr>';
    }
}

<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Delight\Auth\AuthError;
use Delight\Auth\NotLoggedInException;
use DI\DependencyException;
use DI\NotFoundException;
// removed FluentPDO Literal
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;
use Spatie\Image\Exceptions\InvalidManipulation;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $container, $site_config, $CURUSER;

$HTMLOUT = '';

/**
 * @param $val
 *
 * @return string
 */
function calctime($val)
{
    $days = intval($val / 86400);
    $val -= $days * 86400;
    $hours = intval($val / 3600);
    $val -= $hours * 3600;
    $mins = intval($val / 60);
    //$secs = $val - ($mins * 60);

    return "$days " . _('days') . ", $hours " . _('hrs') . ", $mins " . _('minutes') . '';
}

/**
 *
 * @param array $tids
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws AuthError
 * @throws NotLoggedInException
 * @throws \Envms\FluentPDO\Exception
 * @throws UnbegunTransaction
 * @throws \PHPMailer\PHPMailer\Exception
 * @throws InvalidManipulation
 *
 * @return bool|int
 */
function notify_owner(array $tids)
{
    global $container, $site_config;

    if (empty($tids)) {
        return false;
    }
    $fluent = $container->get(Database::class);
    $torrents = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

    $HTMLOUT .= "
        <h1 class='has-text-centered'>" . _pfe('{0} Torrent On Deathrow', '{0} Torrents On Deathrow', $count) . '</h1>' . ($count > $perpage ? $pager['pagertop'] : '') . "
        <form action='' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";
    $heading = '
        <tr>
            <th>' . _('Username') . '</th>
            <th>' . _('Torrent name') . '</th>
            <th>' . _('Delete Reason') . '</th>
            <th>' . _(' Notified') . "</th>
            <th class='has-text-centered w-1'><input type='checkbox' id='checkThemAll' class='tooltipper' title='Select All'></th>
        </tr>";
    $body = '';
    foreach ($torrents as $queued) {
        if ($queued['reason'] == 1) {
            $reason = _fe('no peers, not seeded within {0}', calctime($x_time));
        } elseif ($queued['reason'] == 2) {
            $reason = _fe('no peers, not snatched in {0}', calctime($y_time));
        } else {
            $reason = _fe('no seeder activity within {0} on new torrent', calctime($z_time));
        }
        $id = (int) $queued['tid'];
        $body .= '
        <tr>' . ($CURUSER['class'] >= UC_STAFF ? '
            <td>' . format_username((int) $queued['uid']) . '</td>' : '
            <td>' . _('Hidden') . '</td>') . "
            <td><a href='{$site_config['paths']['baseurl']}/details.php?id={$id}&amp;hit=1'>" . format_comment($queued['torrent_name']) . "</a></td>
            <td>{$reason}</td>
            <td>" . get_date((int) $queued['notified'], 'LONG', 0, 1) . "</td>
            <td><input type='checkbox' name='remove[]' value='{$id}' class='tooltipper' title='" . _('Delete') . "'></td>
        </tr>";
    }
    $HTMLOUT .= main_table($body, $heading) . ($count > $perpage ? $pager['pagerbottom'] : '');
    $HTMLOUT .= "
        <div class='has-text-centered margin20'>
            <input type='submit' name='submit' class='button is-small' value='" . _(' Notify') . "'>
        </div>
        </form>";
    $title = _('Deatchrow');
    $breadcrumbs = [
        "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
} else {
    $HTMLOUT = "<h1 class='has-text-centered'>" . _(' Torrents On Deathrow') . '</h1>';
    $HTMLOUT .= stdmsg(_('Awesome'), _('There are not torrents on deathrow'));
    $title = _('Deathrow');
    $breadcrumbs = [
        "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
}

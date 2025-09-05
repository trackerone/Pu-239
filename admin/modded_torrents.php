<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$modes = [
    'today',
    'yesterday',
    'unmodded',
];
$HTMLOUT = '';
$links = "
    <ul class='level-center bg-06'>
        <li class='is-link margin10'>
            <a href='{$_SERVER['PHP_SELF']}?tool={$_GET['tool']}&amp;type=today' data-toggle='tooltip' data-placement='top' title='Tooltip on top'>" . _('Modded Today') . "</a>
        </li>
        <li class='is-link margin10'>
            <a href='{$_SERVER['PHP_SELF']}?tool={$_GET['tool']}&amp;type=yesterday'>" . _('Modded Yesterday') . "</a>
        </li>
        <li class='is-link margin10'>
            <a href='{$_SERVER['PHP_SELF']}?tool={$_GET['tool']}&amp;type=unmodded'>" . _('All Unmodded Torrents') . '</a>
        </li>
    </ul>';

/**
 * @param      $arr
 * @param bool $empty
 *
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 * @throws NotFoundException
 *
 * @return string
 */
function do_sort($arr, $empty = false)
{
    global $site_config;

    $returnto = !empty($_SERVER['REQUEST_URI']) ? '&amp;returnto=' . urlencode($_SERVER['REQUEST_URI']) : '';
    $ret_html = '';

    foreach ($arr as $res) {
        if (isset($res['checked_when'])) {
            $ret_html .= "
                <tr>
                    <td>
                        <a href='{$site_config['paths']['baseurl']}/details.php?id=" . (int) $res['id'] . "'>" . htmlsafechars($res['name']) . '</a>
                    </td>
                    <td>' . format_username((int) $res['checked_by']) . '</td>
                    <td>' . get_date((int) $res['checked_when'], 'LONG') . '</td>
                </tr>';
        } else {
            $ret_html .= "
                <tr>
                    <td>
                        <a href='{$site_config['paths']['baseurl']}/details.php?id={$res['id']}{$returnto}'>" . htmlsafechars($res['name']) . '</a>
                    </td>
                    <td>' . get_date((int) $res['added'], 'LONG') . "</td>
                    <td>
                        <a href='{$site_config['paths']['baseurl']}/edit.php?id={$res['id']}{$returnto}' class='tooltipper' title='" . _('Edit') . "'>
                            <i class='icon-edit icon has-text-info' aria-hidden='true'></i>
                        </a>
                    </td>
                </tr>";
        }
    }

    return $ret_html;
}

global $container;

$fluent = $container->get(Database::class);
if (isset($_GET['type']) && in_array($_GET['type'], $modes)) {
    if (isset($_GET['type']) && in_array($_GET['type'], $modes)) {
        $mode = $_GET['type'];
    } else {
        stderr(_('Error'), _('Please Try That Previous request again.'));
    }
    if ($mode === 'yesterday') {
        $count = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            if ($data) {
                $data = do_sort($data);
                $HTMLOUT .= $links . "
                <div class='has-text-centered'>
                    <h2>" . _('Summary') . '</h2>
                </div>' . ($count > $perpage ? $pager['pagertop'] : '');
                $heading = '
                    <tr>
                       <th>' . _('Torrent') . '</th>
                       <th>' . _('Modded by') . '</th>
                       <th>' . _('Time') . '</th>
                    </tr>';
                $HTMLOUT .= main_table($data, $heading);
                $HTMLOUT .= $count > $perpage ? $pager['pagerbottom'] : '';
            }
            $title = "$count " . _('Modded Torrents') . " $mode";
        }
    } elseif ($mode === 'today') {
        $count = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            if ($data) {
                $data = do_sort($data);
                $HTMLOUT .= $links . "
                <div class='has-text-centered'>
                    <h2>" . _('Summary') . '</h2>
                </div>' . ($count > $perpage ? $pager['pagertop'] : '');
                $heading = '
                    <tr>
                       <th>' . _('Torrent') . '</th>
                       <th>' . _('Modded by') . '</th>
                       <th>' . _('Time') . '</th>
                    </tr>';
                $HTMLOUT .= main_table($data, $heading);
                $HTMLOUT .= $count > $perpage ? $pager['pagerbottom'] : '';
            }
            $title = "$count " . _('Modded Torrents') . " $mode";
        }
    } elseif ($mode === 'unmodded') {
        $count = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $HTMLOUT .= main_table(do_sort($data), $heading);
            $HTMLOUT .= $count > $perpage ? $pager['pagerbottom'] : '';
            $title = $put;
        }
    } else {
        $HTMLOUT .= $links . main_div('<h3>' . _('No Torrents have been modded') . ' ' . $mode . '.</h3>', 'top20');
        $title = _('No Torrents Modded') . " $mode";
    }
    $breadcrumbs = [
        "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $where = false;
    $ts = strtotime(date('F', time()) . ' ' . date('Y', time()));
    $last_day = date('t', $ts);
    $whom = !empty($_POST['username']) ? $_POST['username'] : false;
    $when = !empty($_POST['time']) && $_POST['time'] > 0 && $_POST['time'] < $last_day ? (int) $_POST['time'] : false;
    $date = isset($_POST['date']) ? $_POST['date'] : false;
    $perpage = 15;

    if ($when && $when > 0) {
        $when = TIME_NOW - ($when * 24 * 60 * 60);
    }
    if ($whom || $when || $date) {
        if ($date && $whom) {
            $beginOfDay = strtotime('midnight', strtotime($date));
            $endOfDay = strtotime('midnight', strtotime($date) + 86400);
            $data = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $text = _fe('by {0} on {1}', $_POST['username'], $date);
            $title = _fe('{0}: Modded Torrents on {1}', $_POST['username'], $date);
        } elseif ($date) {
            $beginOfDay = strtotime('midnight', strtotime($date));
            $endOfDay = strtotime('midnight', strtotime($date) + 86400);
            $data = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $text = _fe('on {0}', $date);
            $title = _fe('Modded Torrents on {0}', $date);
        } elseif ($whom && $when) {
            $data = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $text = _pfe('by {1} within the last {0} day', 'by {1} within the last {0} days', $_POST['time'], $_POST['username']);
            $title = _pfe('{1}: Modded Torrents from {0} day ago', '{1}: Modded Torrents from {0} days ago', $_POST['time'], $_POST['username']);
        } elseif ($when) {
            $data = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $text = _pf('from the past {0} day.', 'from the past {0} days.', $_POST['time']);
            $title = _pfe('{1}: Modded Torrents from {0}, number day ago', '{1}: Modded Torrents from {0}, number days ago', $_POST['time'], $_POST['username']);
        } elseif ($whom) {
            $data = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $text = _fe('by {0}', $_POST['username']);
            $title = _fe('{0}: Modded Torrents', $_POST['username']);
        }
        $count = count($data);
        if (!$count) {
            $HTMLOUT .= stdmsg(_('Error'), _('No Torrents have been modded'), 'top20');
        } else {
            $HTMLOUT = $trim = '';
            if (isset($data)) {
                $data = do_sort($data);
                $HTMLOUT .= $links;
                $HTMLOUT .= "
                <div class='has-text-centered'>
                    <h2>" . _('Summary') . '</h2>
                </div>';
                $heading = '
                    <tr>
                        <th>' . _('Torrent') . '</th>
                        <th>' . _('Modded by') . '</th>
                        <th>' . _('Time') . '</th>
                    </tr>';
                $HTMLOUT .= main_table($data, $heading);
            }
        }
    } else {
        stderr(_('Error'), _('Empty Data Supplied! Please Try Again'));
    }
    $breadcrumbs = [
        "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
}
$HTMLOUT = '';
$HTMLOUT .= $links . "
    <h1 class='has-text-centered'>" . _('Modded Torrents Complete Panel') . '</h1>';

$HTMLOUT .= main_div("
    <div class='has-text-centered padding20'>
        <form method='post' action='{$_SERVER['PHP_SELF']}?tool=modded_torrents&amp;type=search_modded' enctype='multipart/form-data' accept-charset='utf-8'>
            <div class='columns is-gapless level'>
                <div class='column has-text-right'>
                    <label for='username' class='right10'>" . _('Username') . "</label>
                </div>
                <div class='column has-text-left'>
                    <input type='text' placeholder='" . _('Username') . "' name='username' id='username'>
                </div>
            </div>
            <div class='columns is-gapless level'>
                <div class='column has-text-right'>
                    <label for='time' class='right10'>" . _('From') . ' ' . _('Numbers of Days Ago') . "</label>
                </div>
                <div class='column has-text-left'>
                    <input type='text' placeholder='" . _('Day') . "' name='time' id='time'>
                </div>
            </div>
            <div class='columns is-gapless level'>
                <div class='column has-text-right'>
                    <label for='date' class='right10'>" . _('On Which Day') . "</label>
                </div>
                <div class='column has-text-left'>
                    <input type='date' id='date' name='date'>
                </div>
            </div>
            <button type='submit' class='button is-small'>" . _('Search') . '</button>
        </form>
  </div>');

$title = _('Modded Torrents');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

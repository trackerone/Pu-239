<?php

declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
check_user_status();
$lang = array_merge(load_language('global'), load_language('report'));
global $site_config, $CURUSER;

$HTMLOUT = $id_2 = $id_2b = '';

if (!$site_config['staff']['reports']) {
    stderr('Error', 'The report system is offline');
}
$id = ($_GET['id'] ? (int) $_GET['id'] : (int) $_POST['id']);
if (!is_valid_id($id)) {
    stderr("{$lang['report_error']}", "{$lang['report_error1']}");
}
$type = (isset($_GET['type']) ? htmlsafechars($_GET['type']) : htmlsafechars($_POST['type']));
$typesallowed = [
    'User',
    'Comment',
    'Request_Comment',
    'Offer_Comment',
    'Request',
    'Offer',
    'Torrent',
    'Hit_And_Run',
    'Post',
];
if (!in_array($type, $typesallowed)) {
    stderr("{$lang['report_error']}", "{$lang['report_error2']}");
}

if ((isset($_GET['id_2'])) || (isset($_POST['id_2']))) {
    $id_2 = ($_GET['id_2'] ? (int) $_GET['id_2'] : (int) $_POST['id_2']);
    if (!is_valid_id($id_2)) {
        stderr("{$lang['report_error']}", "{$lang['report_error3']}");
    }
    $id_2b = "&amp;id_2=$id_2";
}
if ((isset($_GET['do_it'])) || (isset($_POST['do_it']))) {
    $id_2 = ($_GET['id_2b'] ? (int) $_GET['id_2b'] : (int) $_POST['id_2']);
    $do_it = ($_GET['do_it'] ? (int) $_GET['do_it'] : (int) $_POST['do_it']);
    if (!is_valid_id($do_it)) {
        stderr("{$lang['report_error']}", "{$lang['report_error3']}");
    }

    $reason = htmlsafechars($_POST['reason']);
    if (!$reason) {
        stderr("{$lang['report_error']}", "{$lang['report_error4']}");
    }

    $res = sql_query('SELECT id FROM reports WHERE reported_by =' . sqlesc($CURUSER['id']) . ' AND reporting_what =' . sqlesc($id) . ' AND reporting_type = ' . sqlesc($type)) or sqlerr(__FILE__, __LINE__);
    if (mysqli_num_rows($res) != 0) {
        stderr("{$lang['report_error5']}", "{$lang['report_error6']} <b>" . str_replace('_', ' ', $type) . "</b> {$lang['report_id']} <b>$id</b>!");
    }

    $dt = TIME_NOW;
    sql_query('INSERT into reports (reported_by, reporting_what, reporting_type, reason, added, 2nd_value) 
        VALUES (' . sqlesc($CURUSER['id']) . ', ' . sqlesc($id) . ', ' . sqlesc($type) . ', ' . sqlesc($reason) . ", $dt, " . sqlesc($id_2) . ')') or sqlerr(__FILE__, __LINE__);
    $cache = $container->get(Cache::class);
    $cache->delete('new_report_');
    $session = $container->get(Session::class);
    $session->set('is-success', '[h3]' . str_replace('_', ' ', $type) . " {$lang['report_id']} {$id} report sent.[/h3][p]{$lang['report_reason']} {$reason}[/p]");
    header("Location: {$site_config['paths']['baseurl']}");
    die();
}

$HTMLOUT .= main_div("
    <form method='post' action='{$site_config['paths']['baseurl']}/report.php?type=$type$id_2b&amp;id=$id&amp;do_it=1' accept-charset='utf-8'>
    <h1>Report: " . str_replace('_', ' ', $type) . "</h1>
        <img src='{$site_config['paths']['images_baseurl']}warned.png' alt='warned' title='Warned'> {$lang['report_report']} <b>" . str_replace('_', ' ', $type) . "</b> {$lang['report_id']} <b>$id</b>
        <img src='{$site_config['paths']['images_baseurl']}warned.png' alt='warned' title='Warned'><br>{$lang['report_report1']} <a class='altlink' href='{$site_config['paths']['baseurl']}/rules.php' target='_blank'>{$lang['report_rules']}</a>?</td></tr>
        <b>{$lang['report_reason']}</b>
        <textarea name='reason' class='w-100' rows='5'></textarea> [ {$lang['report_req']} ]<br>
        <input type='submit' class='button is-small margin20' value='{$lang['report_confirm']}'>
    </form>");
echo stdhead('Report') . wrapper($HTMLOUT) . stdfoot();
die();

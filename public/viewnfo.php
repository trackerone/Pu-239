<?php

declare(strict_types = 1);

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_bbcode.php';
check_user_status();
$lang = array_merge(load_language('global'), load_language('viewnfo'));

$id = (int) $_GET['id'];
global $CURUSER, $site_config;
if ($CURUSER['class'] === UC_MIN || !is_valid_id($id)) {
    die();
}
$r = sql_query('SELECT name, nfo FROM torrents WHERE id=' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$a = mysqli_fetch_assoc($r) or die("{$lang['text_puke']}");

$HTMLOUT = "
        <h1 class='has-text-centered'>{$lang['text_nfofor']}<a href='{$site_config['paths']['baseurl']}/details.php?id=$id'>" . htmlsafechars($a['name']) . "</a></h1>
        <div class='size_5 has-text-centered bottom10'>{$lang['text_forbest']}<a href='" . url_proxy('https://www.fontpalace.com/font-download/MS+LineDraw/') . "' target='_blank'>{$lang['text_linedraw']}</a>{$lang['text_font']}</div>";

$HTMLOUT .= main_div("<pre class='pre round10 margin20 noselect'>" . format_urls(htmlsafechars($a['nfo'])) . '</pre>');

echo stdhead($lang['text_stdhead']) . wrapper($HTMLOUT) . stdfoot();

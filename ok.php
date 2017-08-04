<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'user_functions.php';
global $CURUSER, $INSTALLER09;
if (!$CURUSER) {
    get_template();
}
dbconn();
$lang = array_merge(load_language('global'), load_language('ok'));
$type = isset($_GET['type']) ? $_GET['type'] : '';
$HTMLOUT = '';
if ($type == 'signup' && isset($_GET['email'])) {
    stderr("{$lang['ok_success']}", sprintf((!$INSTALLER09['email_confirm'] ? $lang['ok_email'] : $lang['ok_email_confirm']), htmlsafechars($_GET['email'], ENT_QUOTES)));
} elseif ($type == 'invite' && isset($_GET['email'])) {
    stderr("{$lang['ok_invsuccess']}", sprintf($lang['ok_email2'], htmlsafechars($_GET['email'], ENT_QUOTES)));
} elseif ($type == 'sysop') {
    $HTMLOUT = stdhead("{$lang['ok_sysop_account']}");
    $HTMLOUT .= "{$lang['ok_sysop_activated']}";
    if (isset($CURUSER)) {
        $HTMLOUT .= "{$lang['ok_account_activated']}";
    } else {
        $HTMLOUT .= "{$lang['ok_account_login']}";
    }
    $HTMLOUT .= stdfoot();
    echo $HTMLOUT;
} elseif ($type == 'confirmed') {
    $HTMLOUT .= stdhead("{$lang['ok_confirmed']}");
    $HTMLOUT .= "<h1>{$lang['ok_confirmed']}</h1>\n";
    $HTMLOUT .= "{$lang['ok_user_confirmed']}";
    $HTMLOUT .= stdfoot();
    echo $HTMLOUT;
} elseif ($type == 'confirm') {
    if (isset($CURUSER)) {
        $HTMLOUT .= stdhead("{$lang['ok_signup_confirm']}");
        $HTMLOUT .= "<h1>{$lang['ok_success_confirmed']}</h1>\n";
        $HTMLOUT .= '<p>' . sprintf($lang['ok_account_active_login'], "<a href='{$INSTALLER09['baseurl']}/index.php'><b>{$lang['ok_account_active_login_link']}</b></a>") . "</p>\n";
        $HTMLOUT .= sprintf($lang['ok_read_rules'], $INSTALLER09['site_name']);
        $HTMLOUT .= stdfoot();
        echo $HTMLOUT;
    } else {
        $HTMLOUT .= stdhead("{$lang['ok_signup_confirm']}");
        $HTMLOUT .= "<h1>{$lang['ok_success_confirmed']}</h1>\n";
        $HTMLOUT .= "{$lang['ok_account_cookies']}";
        $HTMLOUT .= stdfoot();
        echo $HTMLOUT;
    }
} else {
    stderr("{$lang['ok_user_error']}", "{$lang['ok_no_action']}");
}

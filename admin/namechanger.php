<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_users.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $site_config;

$HTMLOUT = '';
$mode = (isset($_GET['mode']) && htmlsafechars($_GET['mode']));
if (isset($mode) && $mode == 'change') {
    $uid = (int) $_POST['uid'];
    $uname = htmlsafechars($_POST['uname']);
    if ($_POST['uname'] == '' || $_POST['uid'] == '') {
        stderr(_('Error'), _('UserName or ID missing'));
    }

    if (strlen($_POST['uname']) < 3 || !valid_username($_POST['uname'])) {
        stderr(_('Error'), "<b>'{$_POST['uname']}'</b> " . _('is invalid') . '');
    }

    $nc_sql = $db->run(');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

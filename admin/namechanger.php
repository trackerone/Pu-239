<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Database;


global $container, $site_config;

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

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

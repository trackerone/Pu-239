<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Session;

require_once INCL_DIR . 'function_users.php';
require_once CLASS_DIR . 'class_check.php';
require_once INCL_DIR . 'function_password.php';
require_once INCL_DIR . 'function_account_delete.php';
require_once INCL_DIR . 'function_html.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $container, $CURUSER, $site_config;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = (int) trim($_POST['userid']);
    $username = trim(htmlsafechars((string) $_POST['username']));
    if (empty($username) || empty($userid)) {
        stderr(_('Error'), _('Please fill out the form correctly.'));
    }
    $fluent = $container->get(Database::class);
    $id = $fluent->from('users')
                 ->select(null)
                 ->select('id')
                 ->where('username = ?', $username)
                 ->where('id = ?', $userid)
                 ->fetch('id');

    if (!$id) {
        stderr(_('Error'), _('Invalid UserID/Username Combination'));
    }

    if (account_delete($id)) {
        write_log("User: $username Was deleted by {$CURUSER['username']}");
        $session = $container->get(Session::class);
        $session->set('is-success', _('The account was deleted.'));
    } else {
        stderr(_('Error'), _('Unable to delete the account.'));
    }
}

$HTMLOUT = "
<script>
    function deleteConfirm(){
        var result = confirm('Are you sure to delete this user?');
        if (result) {
            return true;
        } else {
            return false;
        }
    }
</script>
<div class='row'>
    <div class='col-md-12'>
        <h1 class='has-text-centered'>" . _('Delete account') . "</h1>
            <form method='post' action='{$_SERVER['PHP_SELF']}?tool=delacct&amp;action=delacct' onsubmit='return deleteConfirm();' enctype='multipart/form-data' accept-charset='utf-8'>
                <table class='table table-bordered'>
                    <tr>
                        <td class='rowhead'>" . _('User ID') . "</td>
                        <td><input class='w-100' name='userid'></td>
                    </tr>
                    <tr>
                        <td class='rowhead'>" . _('Username') . "</td>
                        <td><input class='w-100' name='username'></td>
                    </tr>
                    <tr>
                        <td colspan='2' class='has-text-centered'><input type='submit' class='button is-small' value='" . _('Delete') . "'></td>
                    </tr>
                </table>
            </form>
        </div>
</div>";
$title = _('Delete Account');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

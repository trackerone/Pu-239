<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use Pu239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use Pu239\Database;
use Pu239\Session;
use PU239\Security\AuthZ;

if (strpos(__FILE__, '/admin/') !== false) {
    AuthZ::requireRole('admin');
} else {
    AuthZ::requireAnyRole(['staff', 'admin']);
}

global $container, $CURUSER;
/** @var ContainerInterface $container */
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
// AUTO_ADMIN_MEDIUM: 2025-10-23; tool=codex-admin-medium-sweep; rules=2025.10.23-admin-medium

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$self = $s($_SERVER['PHP_SELF'] ?? '');
$baseurl = $s($config->get('paths.baseurl'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): csrf
    $userid = (int) trim($_POST['userid']);
    $username = trim(htmlsafechars((string) $_POST['username']));
    if (empty($username) || empty($userid)) {
        stderr(_('Error'), _('Please fill out the form correctly.'));
    }
    // $fluent removed — use $this->db (ExtendedPdo)
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
        audit_log($CURUSER['id'] ?? null, 'user.ban', ['target' => $id, 'reason' => 'delete_account']);
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
            <form method='post' action='{$self}?tool=delacct&amp;action=delacct' onsubmit='return deleteConfirm();' enctype='multipart/form-data' accept-charset='utf-8'>
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
    "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$self}'>" . $s($title) . '</a>',
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

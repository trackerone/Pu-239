<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Delight\Auth\Auth;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\User;
use PU239\Support\Audit;


global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $username = !empty($_GET['username']) ? $_GET['username'] : '';
    $userid   = !empty($_GET['userid']) ? $_GET['userid'] : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_class = $container->get(User::class);
    $username = htmlsafechars($_POST['username']);
    $uid = (int) $_POST['uid'];
    $user = $user_class->getUserFromId($uid);
    $password = bin2hex(random_bytes(12));
    $auth = $container->get(Auth::class);
    $auth->forgotPassword($user['email'], function ($selector, $token) use ($password, $CURUSER, $username, $user_class, $uid) {
        $details = [
            'selector' => $selector,
            'token' => $token,
            'password' => $password,
        ];
        if ($user_class->reset_password($details, true)) {
            Audit::log(
                $CURUSER['id'] ?? null,
                'password.change',
                [
                    'target' => $uid,
                ],
            );
            write_log(_fe('Password reset for {0} by {1}', $username, htmlsafechars($CURUSER['username'])));
            stderr(_('Success'), _fe('The password for account {0} is now {1}', $username, format_comment($password)) . '</b>.');
        } else {
            stderr(_('Error'), _('Password reset failed.'));
        }
    });
}
$body = '
    <tr>
        <td>' . _('ID') . ": </td>
        <td><input type='number' name='uid' size='10' value='$userid' class='w-100'></td>
    </tr>
    <tr>
        <td>" . _('Username') . ": </td>
        <td><input name='username' value='$username' class='w-100'></td>
    </tr>
    <tr>
        <td colspan='2' class='has-text-centered'>
            <input type='submit' class='button is-small' value='reset'>
        </td>
    </tr>";
$HTMLOUT .= "
<h1 class='has-text-centered'>" . _("Reset User's Lost Password") . "</h1>
<form method='post' action='{$config->get('paths.baseurl')}/staffpanel.php?tool=reset&amp;action=reset' enctype='multipart/form-data' accept-charset='utf-8'>" . main_table($body) . '
</form>';
$title = _('Reset Password');
$breadcrumbs = [
    "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

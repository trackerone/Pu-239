<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Delight\Auth\Auth;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TooManyRequestsException;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

$user = check_user_status();
$baseUrl = (string) $config->get('paths.baseurl');

get_template();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session = $container->get(Session::class);
    $auth = $container->get(Auth::class);
    $url = get_return_to($_POST['page']);
    if (empty($url)) {
        $session->set('is-warning', _('Invalid Page Requested.'));
        header("Location: {$baseUrl}/index.php");
        app_halt('Exit called');
    }
    try {
        if ($auth->reconfirmPassword($_POST['password'])) {
            $session->set('is-success', _('Your identity has been confirmed.'));
            $session->set('auth_remembered', false, false);
            header("Location: {$url}");
            app_halt('Exit called');
        } else {
            $auth->logOutEverywhere();
            $session->set('is-danger', _('Password verification failed.'));
            header("Location: {$baseUrl}/login.php");
            app_halt('Exit called');
        }
    } catch (NotLoggedInException $e) {
        $session->set('is-danger', _('The user is not signed in.'));
        header("Location: {$baseUrl}/login.php");
        app_halt('Exit called');
    } catch (TooManyRequestsException $e) {
        $session->set('is-danger', _('Too many requests from your IP..'));
        header("Location: {$baseUrl}/index.php");
        app_halt('Exit called');
    }
}
$HTMLOUT = "
            <form id='site_login' class='form-inline table-wrapper' method='post' action='{$baseUrl}/verify.php' enctype='multipart/form-data' accept-charset='utf-8'>";
$body = "
                <h1 class='has-text-centered'>" . _('Verify Your Identity') . "</h1>
                <div class='columns'>
                    <div class='column is-one-quarter'>" . _('Password') . "</div>
                    <div class='column'>
                        <input type='password' class='w-100' name='password' autocomplete='on' placeholder='" . _('Password') . "' required>
                        <input type='hidden' name='page' value='{$_GET['page']}'>
                    </div>
                </div>
                <div class='has-text-centered'>
                    <input id='login' type='submit' value='" . ('Verify') . "' class='button is-small'>
                </div>";

$HTMLOUT .= main_div($body, '', 'padding20') . '
            </div>
        </form>';
$title = _('Verify Identity');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

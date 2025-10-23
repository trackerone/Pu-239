<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\PublicSite;

use Delight\Auth\Auth;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TooManyRequestsException;
use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use Pu239\Session;

use function htmlspecialchars;

global $container;
/** @var ContainerInterface $container */
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

final class VerifyHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-22T04:56:01Z; tool=codex-safe-handler-convert; rules=2025.10.22; commit=TO_BE_FILLED
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $s = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            check_user_status();

            $baseUrl = (string) $config->get('paths.baseurl');
            $page = $_GET['page'] ?? '';

            get_template();

            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                // TODO(2025): add CSRF verification
                /** @var Session $session */
                $session = $container->get(Session::class);
                /** @var Auth $auth */
                $auth = $container->get(Auth::class);

                $url = get_return_to($_POST['page'] ?? '');
                if (empty($url)) {
                    $session->set('is-warning', _('Invalid Page Requested.'));
                    header("Location: {$baseUrl}/index.php");
                    app_halt('Exit called');
                }

                try {
                    if ($auth->reconfirmPassword($_POST['password'] ?? '')) {
                        $session->set('is-success', _('Your identity has been confirmed.'));
                        $session->set('auth_remembered', false, false);
                        header("Location: {$url}");
                        app_halt('Exit called');
                    }

                    $auth->logOutEverywhere();
                    $session->set('is-danger', _('Password verification failed.'));
                    header("Location: {$baseUrl}/login.php");
                    app_halt('Exit called');
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
                        <input type='hidden' name='page' value='" . $s($page) . "'>
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
                sprintf("<a href='%s'>%s</a>", $s($_SERVER['PHP_SELF'] ?? ''), $s($title)),
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

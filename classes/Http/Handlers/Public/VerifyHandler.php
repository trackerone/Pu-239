<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=50-5

namespace PU239\Http\Handlers\Public;

use Delight\Auth\Auth;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TooManyRequestsException;
use Pu239\Config\ConfigRepository;
use Pu239\Session;

final class VerifyHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=50-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $s = $s ?? static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $user = check_user_status();
            $baseUrl = (string) $config->get('paths.baseurl');

            $page = $_GET['page'] ?? '';

            get_template();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): add CSRF verification
                /** @var Session $session */
                $session = $container->get(Session::class);
                /** @var Auth $auth */
                $auth = $container->get(Auth::class);
                $url = get_return_to($_POST['page'] ?? '');
                if (empty($url)) {
                    $session->set('is-warning', _('Invalid Page Requested.'));
                    header(sprintf('Location: %s/index.php', $baseUrl));
                    app_halt('Exit called');

                    return;
                }
                try {
                    if ($auth->reconfirmPassword($_POST['password'] ?? '')) {
                        $session->set('is-success', _('Your identity has been confirmed.'));
                        $session->set('auth_remembered', false, false);
                        header(sprintf('Location: %s', $url));
                        app_halt('Exit called');

                        return;
                    }
                    $auth->logOutEverywhere();
                    $session->set('is-danger', _('Password verification failed.'));
                    header(sprintf('Location: %s/login.php', $baseUrl));
                    app_halt('Exit called');

                    return;
                } catch (NotLoggedInException $e) {
                    $session->set('is-danger', _('The user is not signed in.'));
                    header(sprintf('Location: %s/login.php', $baseUrl));
                    app_halt('Exit called');

                    return;
                } catch (TooManyRequestsException $e) {
                    $session->set('is-danger', _('Too many requests from your IP..'));
                    header(sprintf('Location: %s/index.php', $baseUrl));
                    app_halt('Exit called');

                    return;
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

<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-17T04:20:10Z via handler-convert offset=170 size=5

namespace PU239\Http\Handlers\Public;

use Delight\Auth\Auth;
use PU239\Support\Audit;
use Pu239\Ban;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\IP;
use Pu239\Session;
use Pu239\User;
use Rakit\Validation\Validator;
use RuntimeException;

final class LoginHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-17T04:20:10Z via handler-convert offset=170 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class); // @phpstan-ignore-line preserved for parity
            $baseUrl = (string) $config->get('paths.baseurl');
            $ipLogging = (bool) $config->get('site.ip_logging');
            $limitIps = (bool) $config->get('site.limit_ips');
            $limitIpsCount = (int) $config->get('site.limit_ips_count');
            $smtpEnabled = (bool) $config->get('mail.smtp_enable');

            /** @var Auth $auth */
            $auth = $container->get(Auth::class);
            if ($auth->isLoggedIn()) {
                header("Location: {$baseUrl}");
                app_halt('Exit called');
            }

            get_template();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                [$loginLimit, $loginWindow] = \PU239\Security\RateLimiter::loginDefaults();
                rate_limit_or_fail($loginLimit, $loginWindow);
                // TODO(2025): add CSRF verification for login form
            }

            /** @var Ban $banRepository */
            $banRepository = $container->get(Ban::class);
            $ip = getip(0);
            if ($banRepository->get_count($ip) > 0) {
                stderr(_('Error'), _fe('This IP ({0}) address has been banned.', $ip));
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                /** @var Session $session */
                $session = $container->get(Session::class);
                /** @var Validator $validator */
                $validator = $container->get(Validator::class);
                $post = $_POST;
                unset($_POST, $_GET, $_FILES);

                $validation = $validator->validate($post, [
                    'email' => 'required|email',
                    'password' => 'required',
                    'remember' => 'in:1',
                ]);

                if ($validation->fails()) {
                    write_log(_fe('{0} has tried to login using invalid data. ', getip(0)) . json_encode($post, JSON_PRETTY_PRINT));
                    header("Location: {$_SERVER['PHP_SELF']}");
                    app_halt('Exit called');
                }

                /** @var User $userService */
                $userService = $container->get(User::class);
                $rememberFlag = isset($post['remember']) ? 1 : 0;
                if ($userService->login($post['email'], $post['password'], $rememberFlag)) {
                    $userId = $auth->getUserId();
                    $user = $userService->getUserFromId($userId);

                    if ($ipLogging || !($user['perms'] & PERMS_NO_IP)) {
                        insert_update_ip('login', $userId);
                    }

                    if ($limitIps) {
                        /** @var IP $ipRepository */
                        $ipRepository = $container->get(IP::class);
                        $count = $ipRepository->get_ip_count($userId, 3, 'login');
                        if ($count > $limitIpsCount) {
                            Audit::log($userId ?? null, 'login.blocked.ip_limit', [
                                'limit' => $limitIpsCount,
                                'count' => $count,
                                'window_days' => 3,
                                'context' => 'login',
                            ]);
                            $userService->logout($userId, false);
                            $session->set('is-danger', _('You have exceeded the maximum number of IPs allowed'));
                            stderr(_('Error'), _fe('You are allowed {0} in the previous 3 days. You have used {1} different IPs', $limitIpsCount, $count));
                        }
                    }

                    Audit::log($userId ?? null, 'login.success', ['context' => 'login']);
                    if (!empty($post['returnto'])) {
                        $returnTo = get_return_to($post['returnto']);
                        if (!empty($returnTo)) {
                            header("Location: {$returnTo}");
                            app_halt('Exit called');
                        }
                    }

                    header("Location: {$baseUrl}");
                    app_halt('Exit called');
                } else {
                    unset($_POST, $_GET, $_FILES);
                }
            }

            $stdfoot = [];
            $returnHidden = '';
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['returnto']) && !is_array($_GET['returnto'])) {
                $return = explode('?', urldecode((string) $_GET['returnto']));
                if (file_exists(ROOT_DIR . trim('/', $return[0]))) {
                    $returnto = urlencode(urldecode((string) $_GET['returnto']));
                    $returnHidden = "
                        <input type='hidden' name='returnto' value='$returnto'>";
                }
            }

            $html = "
            <form id='site_login' class='form-inline table-wrapper' method='post' action='{$baseUrl}/login.php' enctype='multipart/form-data' accept-charset='utf-8'>";
            $body = "
                <div class='columns level'>
                    <div class='column is-one-quarter'>" . _('Email Address') . "</div>
                    <div class='column'>
                        <input type='email' class='w-100' name='email' autocomplete='on' placeholder='" . _('Email Address') . "' required>
                    </div>
                </div>
                <div class='columns level'>
                    <div class='column is-one-quarter'>" . _('Password') . "</div>
                    <div class='column'>
                        <input type='password' class='w-100' name='password' autocomplete='on' placeholder='" . _('Password') . "' required>
                    </div>
                </div>$returnHidden
                <div class='level-center-center bottom10'>
                    <input type='checkbox' name='remember' value='1' id='remember' class='right10' checked>
                    <label for='remember' class='level-item tooltipper' title='" . _('Keep me logged in.') . "'>" . _('Remember Me?') . "</label>
                </div>
                <div class='has-text-centered'>
                    <input id='login' type='submit' value='" . _('Login') . "' class='button is-small'>
                </div>
                <div class='level-center top20'>
                    <a href='{$baseUrl}/signup.php'>" . _('Signup') . '</a>' . ($smtpEnabled ? "
                    <a href='{$baseUrl}/recover.php'>" . _('Forgot Password') . '</a>' : '') . '
                </div>';

            $html .= main_div($body, '', 'padding20') . '
            </form>';

            $title = _('Login');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'w-50 min-350 has-text-centered', $breadcrumbs) . wrapper($html) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

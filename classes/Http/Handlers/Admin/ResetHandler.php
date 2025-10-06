<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-03-19 via tools/handler-convert batch=offset40

namespace PU239\Http\Handlers\Admin;

use Delight\Auth\Auth;
use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use PU239\Security\PasswordHasher;
use Pu239\Database;
use Pu239\User;

final class ResetHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-03-19
        try {
            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container, $CURUSER;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $username = '';
            $userid = '';

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $username = !empty($_GET['username']) ? $_GET['username'] : '';
                $userid = !empty($_GET['userid']) ? $_GET['userid'] : '';
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                /** @var User $userClass */
                $userClass = $container->get(User::class);
                $username = htmlsafechars($_POST['username'] ?? '');
                $uid = isset($_POST['uid']) ? (int) $_POST['uid'] : 0;
                $user = $userClass->getUserFromId($uid);

                $password = (static function (): string {
                    while (true) {
                        $candidate = substr(strtr(base64_encode(random_bytes(12)), '+/=', '!*@'), 0, 16);
                        try {
                            PasswordHasher::assertPolicy($candidate);
                            // TODO(2025): admin/reset.php contained merge markers around password policy enforcement.
                            return $candidate;
                        } catch (\InvalidArgumentException $e) {
                            continue;
                        }
                    }
                })();

                /** @var Auth $auth */
                $auth = $container->get(Auth::class);
                $auth->forgotPassword($user['email'], function ($selector, $token) use ($password, $CURUSER, $username, $userClass) {
                    $argonHash = null;
                    try {
                        $argonHash = PasswordHasher::hash($password);
                    } catch (\InvalidArgumentException | \RuntimeException $e) {
                        stderr(_('Error'), $e->getMessage());
                    }

                    $details = [
                        'selector' => $selector,
                        'token' => $token,
                        'password' => $password,
                        'argon_hash' => $argonHash,
                    ];
                    if ($userClass->reset_password($details, true)) {
                        write_log(_fe('Password reset for {0} by {1}', $username, htmlsafechars($CURUSER['username'])));
                        stderr(_('Success'), _fe('The password for account {0} is now {1}', $username, format_comment($password)) . '</b>.');
                    } else {
                        stderr(_('Error'), _('Password reset failed.'));
                    }
                });
            }

            $body = sprintf(
                <<<'HTML'
        <tr>
            <td>%s: </td>
            <td><input type='number' name='uid' size='10' value='%s' class='w-100'></td>
        </tr>
        <tr>
            <td>%s: </td>
            <td><input name='username' value='%s' class='w-100'></td>
        </tr>
        <tr>
            <td colspan='2' class='has-text-centered'>
                <input type='submit' class='button is-small' value='reset'>
            </td>
        </tr>
HTML,
                _('ID'),
                $userid,
                _('Username'),
                $username
            );

            $htmlOut = sprintf(
                <<<'HTML'
<h1 class='has-text-centered'>%s</h1>
<form method='post' action='%s/staffpanel.php?tool=reset&amp;action=reset' enctype='multipart/form-data' accept-charset='utf-8'>%s
</form>
HTML,
                _("Reset User's Lost Password"),
                $config->get('paths.baseurl'),
                main_table($body)
            );

            $title = _('Reset Password');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo $e instanceof \RuntimeException ? $e->getMessage() : 'Internal error';
        }
    }
}

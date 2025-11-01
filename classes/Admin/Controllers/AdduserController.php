<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PDO;
use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use PU239\Security\PasswordHasher;
use Pu239\Cache;
use Pu239\Session;
use Pu239\User;
use Psr\Container\ContainerInterface;
use Rakit\Validation\Validator;

final class AdduserController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
        private readonly PDO $pdo,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23; tool=codex-admin-medium-require; rules=2025.10.23-admin-require
        try {
            global $container, $cache;
            $container = $this->container;
            $config = $this->config;
            $pdo = $this->pdo;
            $cache ??= $container->get(Cache::class);
            $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
            if (strpos($scriptPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $cache->delete('chat_users_list_');

            $stdfoot = [
                'js' => [
                    get_file_name('check_username_js'),
                ],
            ];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $post = $_POST;
                unset($_POST, $_GET, $_FILES);
                $validator = $container->get(Validator::class);
                $validation = $validator->validate($post, [
                    'username' => 'required|between:3,64',
                    'email' => 'required|email',
                ]);
                if ($validation->fails() || !valid_username($post['username'], false, true)) {
                    write_log(getip(0,) . ' has used invalid data to signup. ' . json_encode($post, JSON_PRETTY_PRINT));
                    header("Location: {$_SERVER['PHP_SELF']}");
                    app_halt('Exit called');
                } else {
                    $password = (static function (): string {
                        while (true) {
                            $candidate = substr(strtr(base64_encode(random_bytes(12)), '+/=', '!*@'), 0, 16);
                            try {
                                PasswordHasher::assertPolicy($candidate);

                                return $candidate;
                            } catch (\InvalidArgumentException $e) {
                                continue;
                            }
                        }
                    })();
                    $argonHash = null;
                    try {
                        $argonHash = PasswordHasher::hash($password);
                    } catch (\InvalidArgumentException | \RuntimeException $e) {
                        stderr(_('Error'), $e->getMessage());
                    }
                    $data = [
                        'email' => $post['email'],
                        'password' => $password,
                        'username' => $post['username'],
                        'argon_hash' => $argonHash ?? null,
                        'send_email' => false,
                    ];
                    $user = $container->get(User::class);
                    $userid = $user->add($data);
                    $session = $container->get(Session::class);
                    if (empty($userid)) {
                        $session->set('is-warning', _('User or email already exists.'));
                    } else {
                        stderr(_('Success'), _fe('{0} account created successfully. The password has been set to {1}', format_username($userid), $password));
                    }
                }
            }

            $HTMLOUT = '
    <h1 class="has-text-centered">' . _('Add User') . '</h1>
    <form method="post" action="' . $config->get('paths.baseurl') . '/staffpanel.php?tool=adduser&amp;action=adduser" accept-charset="utf-8">';
            $body = "
        <div class='columns'>
            <div class='column is-one-quarter'>" . _('Username') . "</div>
            <div class='column'>
                <input type='text' name='username' id='username' class='w-100' onblur='check_name();' value='' autocomplete='on' required pattern='[\\p{L}\\p{N}_-]{3,64}'>
                <div id='namecheck'></div>
            </div>
        </div>
        <div class='columns'>
            <div class='column is-one-quarter'>" . _('Email') . "</div>
            <div class='column'>
                <input type='email' name='email' id='email' class='w-100' onblur='check_email();' autocomplete='on' required>
                <div id='emailcheck'></div>" . ($config->get('signup.email_confirm') ? "
                <div class='alt_bordered top10 padding10'>" . _("The email address must be valid. You will receive a confirmation email which you need to respond to. The email address won't be publicly shown anywhere.") . '</div>' : '') . "
            </div>
        </div>
        <div class='has-text-centered margin20'>
            <input type='submit' id='submit' value='" . _('Okay') . "' class='button is-small'>
        </div>
    </form>";

            $HTMLOUT .= main_div($body, '', 'padding20');
            $title = _('Add User');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Admin controller error (adduser): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}

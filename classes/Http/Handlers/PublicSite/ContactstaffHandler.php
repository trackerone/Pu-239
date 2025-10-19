<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:55:00Z via handler-convert offset=280 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;

use function dirname;
use function htmlspecialchars;

final class ContactstaffHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:55:00Z via handler-convert offset=280 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $user = check_user_status();

            $baseUrl = (string) $config->get('paths.baseurl');
            $selfRaw = $_SERVER['PHP_SELF'] ?? '';
            $selfEscaped = $escape($selfRaw);
            $msg = '';
            $subject = '';
            $returnTo = $_GET['returnto'] ?? '';

            $stdhead = [
                'css' => [
                    get_file_name('sceditor_css'),
                ],
            ];
            $stdfoot = [
                'js' => [
                    get_file_name('upload_js'),
                    get_file_name('sceditor_js'),
                ],
            ];

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                // TODO(2025): add CSRF verification
                $msg = htmlsafechars($_POST['body'] ?? '');
                $subject = htmlsafechars($_POST['subject'] ?? '');
                $returnTo = htmlsafechars($_POST['returnto'] ?? $returnTo);
                $hasFailure = false;

                if ($msg === '') {
                    $session->set('is-warning', _("Your messages doesn't have a body"));
                    $hasFailure = true;
                }

                if ($subject === '') {
                    $session->set('is-warning', _("Your messages doesn't have a subject"));
                    $hasFailure = true;
                }

                if (!$hasFailure) {
                    $statement = $db->run(
                        'INSERT INTO staffmessages (sender, added, msg, subject) VALUES (:sender, :added, :msg, :subject)',
                        [
                            ':sender' => $user['id'],
                            ':added' => TIME_NOW,
                            ':msg' => $msg,
                            ':subject' => $subject,
                        ],
                    );

                    if ($statement->rowCount() > 0) {
                        $cache->delete('staff_mess_');
                        audit_log($user['id'] ?? null, 'contactstaff.send', [
                            'subject' => $subject,
                        ]);
                        $session->set('is-success', _('Message was sent! Wait for staff to respond now!'));
                        header('Location: ' . $baseUrl);
                        app_halt('Exit called');
                    }

                    $session->set('is-warning', _('There was something wrong!'));
                    header('Location: ' . $baseUrl);
                    app_halt('Exit called');
                }

                // Keep entered data when validation fails.
                $session->set('is-warning', _('Please review the highlighted errors.'));
            }

            $formAction = $selfEscaped;
            $form = "
            <form method='post' name='message' action='{$formAction}' enctype='multipart/form-data' accept-charset='utf-8'>";
            $header = "
                    <tr>
                        <th colspan='2'>
                            <div class='has-text-centered'>
                                <h1>" . _('Send message to staff') . "</h1>
                                <p class='small'>" . _('If you wish to contact the staff due to a certain user or just a general problem please use this!') . "</p>
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <th class='w-10'>
                            " . _('Subject') . "
                        </th>
                        <th>
                            <input type='text' name='subject' class='w-100' value='" . $subject . "'>
                        </th>
                    </tr>";

            $body = "
                    <tr>
                        <td colspan='2' class='is-paddingless'>" . BBcode($msg) . "
                       </td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            <div class='has-text-centered'>
                                <input type='submit' value='" . _('Send It!') . "' class='button is-small'>
                            </div>
                        </td>
                    </tr>";

            if ($returnTo !== '') {
                $body .= "
                    <input type='hidden' name='returnto' value='" . urlencode($returnTo) . "'>";
            }

            $form .= main_table($body, $header);
            $form .= '
            </form>';

            $title = _('Contact Staff');
            $breadcrumbs = [
                "<a href='{$selfEscaped}'>$title</a>",
            ];

            echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($form) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

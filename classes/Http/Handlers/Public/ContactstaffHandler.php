<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:24:28Z via handler-convert offset=205 size=5

namespace PU239\Http\Handlers\Public;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;
use RuntimeException;

final class ContactstaffHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:24:28Z via handler-convert offset=205 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $user = check_user_status();

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

            $self = $_SERVER['PHP_SELF'] ?? '';
            $escapedSelf = htmlsafechars($self);
            $messageBody = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): add CSRF verification
                $messageBody = isset($_POST['body']) ? (string) $_POST['body'] : '';
                $subject = isset($_POST['subject']) ? (string) $_POST['subject'] : '';
                $returnToRaw = isset($_POST['returnto']) ? (string) $_POST['returnto'] : $self;
                $hasFailure = false;

                if ($messageBody === '') {
                    $session->set('is-warning', _("Your messages doesn't have a body"));
                    $hasFailure = true;
                }
                if ($subject === '') {
                    $session->set('is-warning', _("Your messages doesn't have a subject"));
                    $hasFailure = true;
                }

                if (!$hasFailure) {
                    $stmt = $db->run(
                        'INSERT INTO staffmessages (sender, added, msg, subject) VALUES (:sender, :added, :msg, :subject)',
                        [
                            ':sender' => [$user['id'], \PDO::PARAM_INT],
                            ':added' => [TIME_NOW, \PDO::PARAM_INT],
                            ':msg' => htmlsafechars($messageBody),
                            ':subject' => htmlsafechars($subject),
                        ],
                    );

                    if ($stmt->rowCount() > 0) {
                        $cache->delete('staff_mess_');
                        $session->set('is-success', _('Message was sent! Wait for staff to respond now!'));
                        audit_log($user['id'] ?? null, 'contactstaff.send', [
                            'subject' => htmlsafechars($subject),
                        ]);
                        header('Location: ' . $config->get('paths.baseurl'));

                        return;
                    }

                    $session->set('is-warning', _('There was something wrong!'));
                }

                $redirectTarget = filter_var($returnToRaw, FILTER_VALIDATE_URL) ? $returnToRaw : $self;
                header('Location: ' . $redirectTarget);

                return;
            } else {
                $HTMLOUT = "
            <form method='post' name='message' action='{$escapedSelf}' enctype='multipart/form-data' accept-charset='utf-8'>";
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
                            <input type='text' name='subject' class='w-100'>
                        </th>
                    </tr>";

                $body = "
                    <tr>
                        <td colspan='2' class='is-paddingless'>" . BBcode(htmlsafechars($messageBody)) . "
                       </td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            <div class='has-text-centered'>
                                <input type='submit' value='" . _('Send It!') . "' class='button is-small'>
                            </div>
                        </td>
                    </tr>";

                if (isset($_GET['returnto'])) {
                    $body .= "
                    <input type='hidden' name='returnto' value='" . urlencode((string) $_GET['returnto']) . "'>";
                }

                $HTMLOUT .= main_table($body, $header);
                $HTMLOUT .= '
            </form>';

                $title = _('Contact Staff');
                $breadcrumbs = [
                    "<a href='{$escapedSelf}'>$title</a>",
                ];
                echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);

                return;
            }

            header('Location: ' . $config->get('paths.baseurl'));
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=190 batch=3)

namespace PU239\Http\Handlers\Public;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;
use function htmlspecialchars;

final class ReportHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=190 batch=3)
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
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();

            $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $baseUrl = (string) $config->get('paths.baseurl');
            $self = $escape($_SERVER['PHP_SELF'] ?? '');
            $reportAction = $escape($baseUrl . '/report.php');
            $rulesUrl = $escape($baseUrl . '/rules.php');
            $stdhead = [
                'css' => [
                    get_file_name('sceditor_css'),
                ],
            ];
            $stdfoot = [
                'js' => [
                    get_file_name('sceditor_js'),
                ],
            ];
            $HTMLOUT = '';
            $idSecond = 0;

            if (!(bool) $config->get('staff.reports')) {
                stderr(_('Error'), _('The report system is offline'));
            }

            $id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Bad ID!'));
            }

            $type = isset($_GET['type']) ? htmlsafechars($_GET['type']) : (isset($_POST['type']) ? htmlsafechars($_POST['type']) : '');
            $typesAllowed = [
                'User',
                'Comment',
                'Request_Comment',
                'Offer_Comment',
                'Request',
                'Offer',
                'Torrent',
                'Hit_And_Run',
                'Post',
            ];
            if (!in_array($type, $typesAllowed, true)) {
                stderr(_('Error'), _('Invalid action'));
            }

            if (isset($_POST['do_it'])) {
                // TODO(2025): add CSRF verification
                $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
                $idSecond = isset($_POST['id_2']) ? (int) $_POST['id_2'] : 0;
                $doIt = isset($_POST['do_it']) ? (int) $_POST['do_it'] : 0;
                if (!is_valid_id($doIt)) {
                    stderr(_('Error'), _('Invalid data'));
                }

                $reason = isset($_POST['body']) ? htmlsafechars($_POST['body']) : '';
                if ($reason === '') {
                    stderr(_('Error'), _('You MUST enter a reason for this report! Use your back button and fill in the reason'));
                }

                $previous = $db->fetchValue(
                    'SELECT id FROM reports WHERE reported_by = :reported_by AND reporting_what = :reporting_what AND reporting_type = :reporting_type',
                    [
                        ':reported_by' => [$user['id'], \PDO::PARAM_INT],
                        ':reporting_what' => [$id, \PDO::PARAM_INT],
                        ':reporting_type' => $type,
                    ]
                );

                if (!empty($previous)) {
                    stderr(_('Report Failure!'), _fe('You have already reported: {0} with id: {1}!', str_replace('_', ' ', $type), $id));
                }

                $db->run(
                    'INSERT INTO reports (reported_by, reporting_what, reporting_type, reason, added, `2nd_value`) VALUES (:reported_by, :reporting_what, :reporting_type, :reason, :added, :second_value)',
                    [
                        ':reported_by' => [$user['id'], \PDO::PARAM_INT],
                        ':reporting_what' => [$id, \PDO::PARAM_INT],
                        ':reporting_type' => $type,
                        ':reason' => $reason,
                        ':added' => [TIME_NOW, \PDO::PARAM_INT],
                        ':second_value' => [$idSecond, \PDO::PARAM_INT],
                    ]
                );

                /** @var Cache $cache */
                $cache = $container->get(Cache::class);
                $cache->delete('new_report_');

                /** @var Session $session */
                $session = $container->get(Session::class);
                $session->set('is-success', _fe('{0} with id: {1} report sent.', str_replace('_', ' ', $type), $id));

                header('Location: ' . $baseUrl);
                app_halt('Exit called');
            }

            $typeEscaped = $escape($type);
            $HTMLOUT .= main_div(
                "<form method='post' action='{$reportAction}' enctype='multipart/form-data' accept-charset='utf-8'>
    <h1>" . _('Report') . ': ' . str_replace('_', ' ', $type) . "</h1>
        " . _fe('Are you sure you would like to report {0} with id {1} to the Staff for violation of the {2}rules{3}?', str_replace('_', ' ', $type), $id, "<a class='is-link' href='{$rulesUrl}' target='_blank'>", '</a>') . "</td></tr>
        <p class='top10'><b>" . _('Reason') . ': </b></p>' . BBcode('', 'w-100', 200) . "
        <input type='hidden' name='id' value='$id'>
        <input type='hidden' name='type' value='{$typeEscaped}'>
        <input type='hidden' name='do_it' value='1'>
        <input type='submit' class='button is-small margin20' value='" . _('Confirm Report') . "'>
    </form>",
                '',
                'padding20 has-text-centered'
            );

            $title = _('Report');
            $breadcrumbs = [
                "<a href='{$self}'>$title</a>",
            ];
            echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:54:37Z via handler-convert offset=215 size=5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class NewAnnouncementHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:54:37Z via handler-convert offset=215 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();
            if ($user['class'] < UC_MAX) {
                stderr(_('Error'), _("You're not authorized"));
            }

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

            $baseUrl = (string) $config->get('paths.baseurl');
            $htmlOut = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): add CSRF verification
                $days = [
                    7 => _fe('{0} Days', 7),
                    14 => _fe('{0} Days', 14),
                    21 => _fe('{0} Days', 21),
                    28 => _fe('{0} Days', 28),
                    56 => _fe('{0} Months', 7),
                ];

                $numRecipients = isset($_POST['n_pms']) ? (int) $_POST['n_pms'] : 0;
                $announcementQuery = isset($_POST['ann_query']) ? rawurldecode(trim((string) $_POST['ann_query'])) : '';
                if (!preg_match('/\ASELECT.+?FROM.+?WHERE.+?\z/i', $announcementQuery)) {
                    stderr(_('Error'), _('Misformed Query'));
                }
                if ($numRecipients === 0) {
                    stderr(_('Error'), _('No recipients'));
                }

                $body = trim((string) ($_POST['body'] ?? ''));
                $subject = trim((string) ($_POST['subject'] ?? ''));
                $expiry = isset($_POST['expiry']) ? (int) $_POST['expiry'] : 0;

                if (isset($_POST['buttonval']) && $_POST['buttonval'] === 'Submit') {
                    if ($body === '') {
                        stderr(_('Error'), _('No body to announcement'));
                    }
                    if ($subject === '') {
                        stderr(_('Error'), _('No subject to announcement'));
                    }
                    if (!isset($days[$expiry])) {
                        stderr(_('Error'), _('Invalid expiry selection'));
                    }

                    $expires = TIME_NOW + (86400 * $expiry);
                    $created = TIME_NOW;
                    $statement = $db->run(
                        'INSERT INTO announcement_main (owner_id, created, expires, sql_query, subject, body) VALUES (:owner_id, :created, :expires, :sql_query, :subject, :body)',
                        [
                            ':owner_id' => [$user['id'], \PDO::PARAM_INT],
                            ':created' => [$created, \PDO::PARAM_INT],
                            ':expires' => [$expires, \PDO::PARAM_INT],
                            ':sql_query' => $announcementQuery,
                            ':subject' => $subject,
                            ':body' => $body,
                        ],
                    );

                    if ($statement->rowCount() > 0) {
                        stderr('Success', _('Announcement was successfully created'));
                    }
                    stderr(_('Error'), _('Contact an administrator'));
                }

                $htmlOut .= "<table class='main'>
     <tr>
     <td class='embedded'><div class='has-text-centered'>
     <h1>Create Announcement for " . $numRecipients . ' user' . ($numRecipients > 1 ? 's' : '') . '&#160;!</h1>';
                $htmlOut .= "<form name='compose' method='post' action='{$baseUrl}/new_announcement.php' enctype='multipart/form-data' accept-charset='utf-8'>
     <table>
     <tr>
     <td colspan='2'><b>" . _('Subject') . ": </b>
     <input name='subject' type='text' size='76' value='" . htmlsafechars($subject) . "'></td>
     </tr>
     <tr><td colspan='2'><div class='has-text-centered'>
                       " . BBcode($body) . '
  </div></td></tr>';
                $htmlOut .= "<tr><td colspan='2' class='has-text-centered'>";
                $htmlOut .= "<select name='expiry'>";
                foreach ($days as $key => $label) {
                    $selected = $expiry === $key ? ' selected' : '';
                    $htmlOut .= "<option value='{$key}'{$selected}>{$label}</option>";
                }
                $htmlOut .= "</select>

     <input type='submit' name='buttonval' value='Submit' class='button is-small'>
     </td></tr></table>
     <input type='hidden' name='n_pms' value='{$numRecipients}'>
     <input type='hidden' name='ann_query' value='" . rawurlencode($announcementQuery) . "'>
     </form><br><br>
     </div></td></tr></table>";

                if ($body !== '') {
                    $newTime = TIME_NOW + (86400 * ($expiry ?: 0));
                    $htmlOut .= "<table class='main'>
     <tr><td class='has-text-centered'><h2><span class='has-text-primary'>" . _('Announcement') . ':
     ' . htmlsafechars($subject) . "</span></h2></td></tr>
     <tr><td class='text'>
     " . format_comment($body) . '<br><hr>' . _('Expires') . ': ' . get_date((int) $newTime, 'DATE') . '';
                    $htmlOut .= '</td></tr></table>';
                }
            } else {
                header('Location: 404.html');
                app_halt('Exit called');
            }

            $title = _('New Announcement');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=190 batch=3)

namespace PU239\Http\Handlers\Public;

use Pu239\Database;

final class ViewAnnounceHistoryHandler
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

            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();

            stderr(_('Error'), 'This page is not completed.');

            $action = isset($_GET['action']) ? htmlsafechars($_GET['action']) : '';
            $HTMLOUT = "<h2><span class='size_6'>" . _('Announcement History') . '</span></h2>';
            $annList = $db->fetchAll(
                'SELECT m.main_id, m.subject, m.body
                 FROM announcement_main AS m
                 LEFT JOIN announcement_process AS p ON m.main_id = p.main_id AND p.user_id = :user_id
                 WHERE p.status = :status',
                [
                    ':user_id' => [$user['id'], \PDO::PARAM_INT],
                    ':status' => 2,
                ]
            );

            $body = '';
            if ($action === 'read_announce') {
                $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
                if (!is_valid_id($id)) {
                    $HTMLOUT .= stdmsg(_('Error'), _('Invalid ID'));
                    $title = _('Announcement History');
                    $breadcrumbs = [
                        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                    ];
                    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

                    return;
                }

                $subject = '';
                $announceBody = '';
                foreach ($annList as $announcement) {
                    if ((int) ($announcement['main_id'] ?? 0) === $id) {
                        $subject = (string) ($announcement['subject'] ?? '');
                        $announceBody = (string) ($announcement['body'] ?? '');
                        break;
                    }
                }

                if ($subject === '' || $announceBody === '') {
                    $HTMLOUT .= stdmsg(_('Error'), _('Invalid ID'));
                    $title = _('Announcement History');
                    $breadcrumbs = [
                        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                    ];
                    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

                    return;
                }

                $header = '        <tr>
            <th>' . _('Subject: ') . '<b>' . htmlsafechars($subject) . '</b></th>
        </tr>';
                $body = '        <tr>
            <td>' . format_comment($announceBody) . "</td>
        </tr>
        <tr>
            <td>
                <a href='" . $_SERVER['PHP_SELF'] . "'>" . _('Back') . '</a>
            </td>
        </tr>';
                $HTMLOUT .= main_table($body, $header);
            }

            $header = '        <tr>
            <th><b>' . _('Subject') . '</b></th>
        </tr>';
            $body = '';
            if ($annList !== []) {
                foreach ($annList as $announcement) {
                    $subject = (string) ($announcement['subject'] ?? '');
                    $identifier = (int) ($announcement['main_id'] ?? 0);
                    $body .= "        <tr>
            <td>
                <a href='" . $_SERVER['PHP_SELF'] . '?action=read_announce&amp;id=' . $identifier . "'>" . htmlsafechars($subject) . '</a>
            </td>
        </tr>';
                }
            } else {
                $body .= '        <tr>
            <td>
                Nothing to see here!
            </td>
        </tr>';
            }

            $HTMLOUT .= main_table($body, $header);
            $title = _('Announcement History');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

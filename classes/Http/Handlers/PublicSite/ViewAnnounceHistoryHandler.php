<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-20T04:13:49Z via handler-convert offset=320 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Database;

use function dirname;

final class ViewAnnounceHistoryHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-20T04:13:49Z via handler-convert offset=320 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();

            stderr(_('Error'), 'This page is not completed.');

            $action = isset($_GET['action']) ? htmlsafechars($_GET['action']) : '';
            $html = "<h2><span class='size_6'>" . _('Announcement History') . '</span></h2>';

            $announcements = $db->fetchAll(
                'SELECT m.main_id, m.subject, m.body
                 FROM announcement_main AS m
                 LEFT JOIN announcement_process AS p ON m.main_id = p.main_id AND p.user_id = :user_id
                 WHERE p.status = :status',
                [
                    ':user_id' => $user['id'],
                    ':status' => 2,
                ],
            );

            if ($action === 'read_announce') {
                $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
                if ($id < 1) {
                    $html .= stdmsg(_('Error'), _('Invalid ID'));
                    $title = _('Announcement History');
                    $breadcrumbs = [
                        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                    ];
                    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();

                    return;
                }

                $selected = null;
                foreach ($announcements as $announcement) {
                    if ((int) $announcement['main_id'] === $id) {
                        $selected = $announcement;
                        break;
                    }
                }

                if ($selected === null) {
                    $html .= stdmsg(_('Error'), _('Invalid ID'));
                    $title = _('Announcement History');
                    $breadcrumbs = [
                        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                    ];
                    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();

                    return;
                }

                $header = "
         <tr>
             <th>" . _('Subject: ') . '<b>' . htmlsafechars($selected['subject']) . '</b></th>
         </tr>';
                $body = "
         <tr>
             <td>" . format_comment($selected['body']) . "</td>
         </tr>
         <tr>
             <td>
                 <a href='" . $_SERVER['PHP_SELF'] . "'>" . _('Back') . '</a>
             </td>
         </tr>';
                $html .= main_table($body, $header);
            }

            $header = '
        <tr>
            <th><b>' . _('Subject') . '</b></th>
        </tr>';
            $body = '';
            if ($announcements !== []) {
                foreach ($announcements as $announcement) {
                    $body .= "
        <tr>
            <td>
                <a href='" . $_SERVER['PHP_SELF'] . '?action=read_announce&amp;id=' . (int) $announcement['main_id'] . "'>" . htmlsafechars($announcement['subject']) . '</a>
            </td>
        </tr>';
                }
            } else {
                $body .= '
        <tr>
            <td>
                Nothing to see here!
            </td>
        </tr>';
            }

            $html .= main_table($body, $header);
            $title = _('Announcement History');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

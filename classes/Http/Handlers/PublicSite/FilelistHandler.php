<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-20T04:13:49Z via handler-convert offset=320 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PDO;
use PU239\Config\ConfigRepository;
use Pu239\Database;

use function dirname;
use function pathinfo;

final class FilelistHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-20T04:13:49Z via handler-convert offset=320 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');

            check_user_status();

            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $countRow = $db->fetch(
                'SELECT COUNT(id) AS count FROM files WHERE torrent = :torrent_id',
                [
                    ':torrent_id' => $id,
                ],
            );
            $count = $countRow !== null ? (int) $countRow['count'] : 0;

            $perpage = 50;
            $pager = pager($perpage, $count, $baseUrl . "/filelist.php?id={$id}&amp;");

            $html = '';
            if ($count > $perpage) {
                $html .= $pager['pagertop'];
            }

            $files = $db->fetchAll(
                'SELECT id, filename, size
                 FROM files
                 WHERE torrent = :torrent_id
                 ORDER BY id
                 LIMIT :limit OFFSET :offset',
                [
                    ':torrent_id' => $id,
                    ':limit' => [$pager['pdo']['limit'], PDO::PARAM_INT],
                    ':offset' => [$pager['pdo']['offset'], PDO::PARAM_INT],
                ],
            );

            $header = "
            <tr>
                <th class='has-text-centered w-1'>" . _('Type') . '</th>
                <th>' . _('Path') . "</th>
                <th class='has-text-right w-10'>" . _('Size') . '</th>
            </tr>';
            $body = '';
            foreach ($files as $file) {
                $ext = pathinfo($file['filename'], PATHINFO_EXTENSION);
                $ext = !empty($ext) ? $ext : 'Unknown';
                if (!file_exists(IMAGES_DIR . "icons/{$ext}.png")) {
                    $ext = 'Unknown';
                }
                $body .= "
            <tr>
                <td class='has-text-centered'>
                    <img src='{$imagesBaseUrl}icons/" . htmlsafechars($ext) . ".png' class='tooltipper icon' alt='" . htmlsafechars($ext) . " file' title='" . _fe('{0} file', format_comment($ext)) . "'>
                </td>
                <td>" . htmlsafechars($file['filename']) . "</td>
                <td class='has-text-right'>" . mksize((int) $file['size']) . '</td>
            </tr>';
            }

            if ($body === '') {
                $body = "
            <tr>
                <td colspan='3'>" . _('No files found') . '</td>
            </tr>';
            }

            $html .= main_table($body, $header);

            if ($count > $perpage) {
                $html .= $pager['pagerbottom'];
            }

            $title = _('Filelist');
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

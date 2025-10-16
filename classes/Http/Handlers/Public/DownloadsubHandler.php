<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16T03:48:50Z via handler-convert offset=150 size=5

namespace PU239\Http\Handlers\Public;

use Pu239\Database;
use RuntimeException;
use ZipArchive;

final class DownloadsubHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16T03:48:50Z via handler-convert offset=150 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var Database $db */
            $db = $container->get(Database::class);

            check_user_status();

            // TODO(2025): add CSRF verification
            $action = isset($_POST['action']) ? htmlsafechars($_POST['action']) : '';
            if ($action !== 'download') {
                stderr(_('Error'), _('You do not have the permission to do that.'));
            }

            $id = isset($_POST['sid']) ? (int) $_POST['sid'] : 0;
            if ($id === 0) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $subtitle = $db->row('SELECT id, name, filename FROM subtitles WHERE id = :id', ['id' => $id]);
            if ($subtitle === null) {
                stderr(_('Error'), _('Invalid subtitle requested.'));
            }

            $extension = pathinfo($subtitle['filename'], PATHINFO_EXTENSION);
            $sanitizedName = str_replace([' ', '.', '-'], '_', $subtitle['name']) . '.' . $extension;
            $content = file_get_contents(UPLOADSUB_DIR . $subtitle['filename']);
            if ($content === false) {
                stderr(_('Error'), _('Unable to read subtitle file.'));
            }

            if (file_put_contents(UPLOADSUB_DIR . $sanitizedName, $content) !== false) {
                $zipfile = UPLOADSUB_DIR . $sanitizedName . '.zip';
                /** @var ZipArchive $zip */
                $zip = $container->get(ZipArchive::class); // TODO(2025): legacy ZipArchive::class binding must support force_download
                $zip->open($zipfile, ZipArchive::CREATE);
                $zip->addFromString($zipfile, $content);
                $zip->close();
                if (method_exists($zip, 'force_download')) {
                    $zip->force_download($zipfile);
                } else {
                    // TODO(2025): legacy force_download() helper missing for ZipArchive binding
                }
                if (file_exists($zipfile)) {
                    unlink($zipfile);
                }
                if (file_exists($sanitizedName)) {
                    unlink($sanitizedName); // TODO(2025): confirm legacy unlink without UPLOADSUB_DIR prefix
                }
            }

            $db->run('UPDATE subtitles SET hits = hits + 1 WHERE id = :id', ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

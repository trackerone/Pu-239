<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19 via handler-convert offset=260 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Database;
use ZipArchive;

final class DownloadsubHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19 via handler-convert offset=260 batch=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            $container = $GLOBALS['container'] ?? null;
            if ($container === null) {
                throw new \RuntimeException('Global container not initialized');
            }

            /** @var Database $db */
            $db = $container->get(Database::class);

            check_user_status();

            $escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $action = isset($_POST['action']) ? htmlsafechars((string) $_POST['action']) : '';
            if ($action !== 'download') {
                stderr(_('Error'), _('You do not have the permission to do that.'));
            }

            $subtitleId = isset($_POST['sid']) ? (int) $_POST['sid'] : 0;
            if ($subtitleId <= 0) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $subtitle = $db->fetch(
                'SELECT id, name, filename FROM subtitles WHERE id = :id',
                [
                    ':id' => $subtitleId,
                ],
            );

            if ($subtitle === null) {
                stderr(_('Error'), _('Subtitle not found'));
            }

            $filename = (string) ($subtitle['filename'] ?? '');
            $name = (string) ($subtitle['name'] ?? '');

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $sanitizedName = str_replace([' ', '.', '-'], '_', $name) . ($extension !== '' ? '.' . $extension : '');

            $sourcePath = UPLOADSUB_DIR . $filename;
            if (!is_file($sourcePath)) {
                stderr(_('Error'), _('Subtitle file missing on disk.'));
            }

            $content = (string) file_get_contents($sourcePath);
            $targetPath = UPLOADSUB_DIR . $sanitizedName;
            if (file_put_contents($targetPath, $content) === false) {
                throw new \RuntimeException('Unable to persist normalized subtitle file.');
            }

            $zipPath = $targetPath . '.zip';
            $zipArchive = new ZipArchive();
            if ($zipArchive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create subtitle archive.');
            }

            $zipArchive->addFromString($sanitizedName, $content);
            $zipArchive->close();

            if (method_exists($zipArchive, 'force_download')) {
                $zipArchive->force_download($zipPath);
            } else {
                header('Content-Type: application/zip');
                header('Content-Length: ' . (string) filesize($zipPath));
                header('Content-Disposition: attachment; filename="' . $escape(basename($zipPath)) . '"');
                readfile($zipPath);
            }

            unlink($zipPath);
            unlink($targetPath);

            $db->run('UPDATE subtitles SET hits = hits + 1 WHERE id = :id', [':id' => $subtitleId]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

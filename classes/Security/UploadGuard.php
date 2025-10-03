<?php
declare(strict_types=1);

namespace PU239\Security;

final class UploadGuard
{
    public static function store(array $file, array $opts = []): array
    {
        if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
            throw new \RuntimeException('Invalid upload array.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload error code: ' . (int) $file['error']);
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Not an uploaded file.');
        }

        $max = (int) ($opts['max_bytes'] ?? (getenv('UPLOAD_MAX_BYTES') ?: 10 * 1024 * 1024));
        if ($file['size'] > $max) {
            http_response_code(413);
            throw new \RuntimeException('File too large.');
        }

        $origName = (string) $file['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION) ?: '');
        $allowExtCsv = (string) ($opts['allow_ext'] ?? (getenv('UPLOAD_ALLOW_EXT') ?: 'jpg,jpeg,png,webp,gif,pdf,txt,zip'));
        $allowExt = array_filter(array_map('trim', explode(',', $allowExtCsv)));
        if ($ext === '' || !in_array($ext, $allowExt, true)) {
            http_response_code(415);
            throw new \RuntimeException('Extension not allowed.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
        ];
        $allowSvg = filter_var(getenv('UPLOAD_ALLOW_SVG') ?: '0', FILTER_VALIDATE_BOOLEAN);
        if ($ext === 'svg' && !$allowSvg) {
            http_response_code(415);
            throw new \RuntimeException('SVG not allowed.');
        }
        if ($ext !== 'svg') {
            $expected = $map[$ext] ?? null;
            if ($expected !== null && stripos($mime, $expected) !== 0) {
                http_response_code(415);
                throw new \RuntimeException('MIME mismatch.');
            }
        }

        if (preg_match('/\.(ph(p[0-9]?|t|ar))$/i', $origName)) {
            http_response_code(415);
            throw new \RuntimeException('Executable content not allowed.');
        }

        $root = rtrim((string) ($opts['storage'] ?? (getenv('UPLOAD_STORAGE') ?: dirname(__DIR__, 2) . '/storage/uploads')), '/');
        $sub = date('Y/m');
        $dir = $root . '/' . $sub;
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create storage dir.');
        }

        $rand = bin2hex(random_bytes(16));
        $destRel = $sub . '/' . $rand . ($ext ? '.' . $ext : '');
        $destAbs = $root . '/' . $destRel;

        if (!@move_uploaded_file($file['tmp_name'], $destAbs)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }
        @chmod($destAbs, 0640);
        return [
            'path' => $destRel,
            'mime' => $mime,
            'size' => (int) $file['size'],
            'name' => $origName,
        ];
    }
}

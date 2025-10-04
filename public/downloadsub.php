<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Database;

if (!defined('PU239_ROUTED')) {
    require_once __DIR__ . '/index.php';

    return;
}

require_once __DIR__ . '/../include/bittorrent.php';
check_user_status();
global $container;
$db = $container->get(Database::class);
$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// TODO(2025): add CSRF verification
$action = isset($_POST['action']) ? htmlsafechars($_POST['action']) : '';
if ($action === 'download') {
    $id = isset($_POST['sid']) ? (int) $_POST['sid'] : 0;
    if ($id == 0) {
        stderr(_('Error'), _('Invalid ID'));
    } else {
        $fluent = $db; // alias
// $fluent removed — use $this->db (ExtendedPdo)
        $subtitle = $fluent->from('subtitles')
                           ->select(null)
                           ->select('id')
                           ->select('name')
                           ->select('filename')
                           ->where('id = ?', $id)
                           ->fetch();
        $ext = pathinfo($subtitle['filename'], PATHINFO_EXTENSION);
        $file_name = str_replace([
            ' ',
            '.',
            '-',
        ], '_', $subtitle['name']) . '.' . $ext;
        $content = file_get_contents(UPLOADSUB_DIR . $subtitle['filename']);
        if (file_put_contents(UPLOADSUB_DIR . $file_name, $content)) {
            $files = $file_name;
            $zipfile = UPLOADSUB_DIR . $file_name . '.zip';
            $zip = $container->get(ZipArchive::class);
            $zip->open($zipfile, ZipArchive::CREATE);
            $zip->addFromString($zipfile, $content);
            $zip->close();
            $zip->force_download($zipfile);
            unlink($zipfile);
            unlink($file_name);
        }
        $db->run('UPDATE subtitles SET hits = hits + 1 WHERE id = :id', [':id' => $id]);
    }
} else {
    stderr(_('Error'), _('You do not have the permission to do that.'));
}

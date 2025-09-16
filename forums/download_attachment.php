<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
use Pu239\Database;

$db = $container->get(Database::class);
global $container;

// $fluent removed — use $this->db (ExtendedPdo)
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
if (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID.'));
}
$what = $fluent->from('attachments')
               ->where('id = ?', $id)
               ->fetch();

$update = [
    'times_downloaded' => $what['times_downloaded'] + 1,
];
$sql = "UPDATE attachments SET /* columns */ WHERE id = :id";
$db->perform($sql, array_merge($update, ['id' => $id]));
$download_as = "{$what['file_name']}.{$what['extension']}";
$stored_file = ATTACHMENT_DIR . $what['file'];
header('Content-type: application/' . $what['extension']);
header('Content-Disposition: attachment; filename="' . $download_as . '"');
header('Content-length: ' . filesize($stored_file));
flush();
readfile("$stored_file");

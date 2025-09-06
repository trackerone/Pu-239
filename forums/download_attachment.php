<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

global $container;

// $fluent removed — use $this->db (ExtendedPdo)
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
if (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID.'));
}
$what = $row = $this->db->fetchRow("SELECT * FROM attachments WHERE id = :id", ["id" => (int) $id]); // TODO(batch41): replace with $this->db->fetchRow("SELECT ...", [...])

$update = [
    'times_downloaded' => $what['times_downloaded'] + 1,
];
$sql = "UPDATE attachments SET /* columns */ WHERE id = :id";
$this->db->perform($sql, array_merge($update, ['id' => $id]));
$download_as = "{$what['file_name']}.{$what['extension']}";
$stored_file = ATTACHMENT_DIR . $what['file'];
header('Content-type: application/' . $what['extension']);
header('Content-Disposition: attachment; filename="' . $download_as . '"');
header('Content-length: ' . filesize($stored_file));
flush();
readfile("$stored_file");

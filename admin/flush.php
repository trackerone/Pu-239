<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

if (strpos(__FILE__, '/admin/') !== false) {
    AuthZ::requireRole('admin');
} else {
    AuthZ::requireAnyRole(['staff', 'admin']);
}

global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/** @var Database $db */
$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID'));
}

if ((int) $CURUSER['class'] >= (int) UC_STAFF) {
    // Confirm the user exists and get username
    $row = $db->fetch('SELECT id, username FROM users WHERE id = :id', [':id' => $id]);
    if (!$row) {
        stderr(_('Error'), _('User not found'));
    }
    $username = htmlsafechars((string) $row['username']);

    // Count current peer rows (ghosts) for the user
    $countRow = $db->fetch('SELECT COUNT(*) AS c FROM peers WHERE userid = :id', [':id' => $id]);
    $effected = (int) ($countRow['c'] ?? 0);

    if ($effected > 0) {
        // Remove peer rows (tracker ghosts) for this user
        $db->run('DELETE FROM peers WHERE userid = :id', [':id' => $id]);
        audit_log(
            $CURUSER['id'] ?? null,
            'torrent.moderate',
            [
                'id' => null,
                'op' => 'ghost.flush',
                'target' => $id,
                'count' => $effected,
            ]
        );
    }

    stderr(
        _('Success'),
        _pfe(
            '{0} ghost torrent was successfully cleaned. You may now restart your torrents, the tracker has been updated.',
            '{0} ghost torrents were successfully cleaned. You may now restart your torrents, the tracker has been updated.',
            $effected
        )
    );
} else {
    stderr(_('Error'), _('You are not a member of the staff.'));
}

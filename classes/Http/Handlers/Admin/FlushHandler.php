<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-11 via handler-convert (batch=125-5)

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class FlushHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-11 via handler-convert (batch=125-5)
        try {
            global $container, $CURUSER;

            AuthZ::requireRole('admin');

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            if ((int) ($CURUSER['class'] ?? 0) >= (int) UC_STAFF) {
                $user = $db->fetch('SELECT id, username FROM users WHERE id = :id', [':id' => $id]);
                if ($user === false || $user === null) {
                    stderr(_('Error'), _('User not found'));
                }

                $countRow = $db->fetch('SELECT COUNT(*) AS c FROM peers WHERE userid = :id', [':id' => $id]);
                $effected = (int) ($countRow['c'] ?? 0);

                if ($effected > 0) {
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
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

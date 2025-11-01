<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;
use Psr\Container\ContainerInterface;

final class FlushController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23; tool=codex-admin-medium-require; rules=2025.10.23-admin-require
        try {
            global $container, $CURUSER;
            $container = $this->container;
            $config = $this->config;

            $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
            if (strpos($scriptPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $db = $container->get(Database::class);

            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            if ((int) $CURUSER['class'] >= (int) UC_STAFF) {
                $row = $db->fetch('SELECT id, username FROM users WHERE id = :id', [':id' => $id]);
                if (!$row) {
                    stderr(_('Error'), _('User not found'));
                }
                $username = htmlsafechars((string) $row['username']);

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
            error_log('Admin controller error (flush): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}

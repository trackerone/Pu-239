<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:11:32Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use PU239\Config\ConfigRepository;
use Pu239\Database;

final class ClassConfigHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:11:32Z via codex handler conversion
        try {
            global $container, $CURUSER;

            if (strpos(ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escaper = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escaper($_SERVER['PHP_SELF'] ?? '');
            $baseurl = $escaper((string) $config->get('paths.baseurl'));

            $stdhead = [];
            $stdfoot = [];
            $HTMLOUT = '';

            $action = $_GET['action'] ?? '';
            $id = (int) ($_GET['id'] ?? 0);

            switch ($action) {
                case 'add':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // TODO(2025): csrf
                        $name = trim($_POST['name'] ?? '');
                        $value = trim($_POST['value'] ?? '');
                        if ($name === '' || $value === '') {
                            stderr(_('Error'), _('Missing name or value'));
                        }
                        $db->perform('INSERT INTO config (name, value) VALUES (:name, :value)', [
                            'name' => $name,
                            'value' => $value,
                        ]);
                        audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => [$name]]);
                        header('Location: ?tool=class_config');
                        exit;
                    }
                    break;

                case 'edit':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // TODO(2025): csrf
                        $name = trim($_POST['name'] ?? '');
                        $value = trim($_POST['value'] ?? '');
                        if ($id === 0 || $name === '' || $value === '') {
                            stderr(_('Error'), _('Invalid request'));
                        }
                        $db->perform('UPDATE config SET name = :name, value = :value WHERE id = :id', [
                            'id' => $id,
                            'name' => $name,
                            'value' => $value,
                        ]);
                        audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => [$name]]);
                        header('Location: ?tool=class_config');
                        exit;
                    }
                    break;

                case 'delete':
                    if ($id > 0) {
                        $db->perform('DELETE FROM config WHERE id = :id', ['id' => $id]);
                        audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => [$id]]);
                        header('Location: ?tool=class_config');
                        exit;
                    }
                    break;

                default:
                    $rows = $db->fetchAll('SELECT id, name, value FROM config ORDER BY id');
                    $heading = '
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Value</th>
                <th>Actions</th>
            </tr>';
                    $body = '';
                    foreach ($rows as $r) {
                        $configId = $escaper((string) $r['id']);
                        $body .= "
                <tr>
                    <td>{$configId}</td>
                    <td>" . $escaper($r['name']) . "</td>
                    <td>" . $escaper($r['value']) . "</td>
                    <td>
                        <a href='?tool=class_config&amp;action=edit&amp;id={$configId}'>Edit</a> |
                        <a href='?tool=class_config&amp;action=delete&amp;id={$configId}'>Delete</a>
                    </td>
                </tr>";
                    }
                    $HTMLOUT .= main_table($body, $heading);
                    break;
            }

            $title = _('Configuration');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $escaper($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

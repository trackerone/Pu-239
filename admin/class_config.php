<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use Pu239\Database;

if (strpos(__FILE__, '/admin/') !== false) {
    AuthZ::requireRole('admin');
} else {
    AuthZ::requireAnyRole(['staff', 'admin']);
}

global $container, $CURUSER;
/** @var ContainerInterface $container */
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
// AUTO_ADMIN_MEDIUM: 2025-10-23; tool=codex-admin-medium-sweep; rules=2025.10.23-admin-medium

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$self = $s($_SERVER['PHP_SELF'] ?? '');
$baseurl = $s($config->get('paths.baseurl'));

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
            $configId = $s((string) $r['id']);
            $body .= "
                <tr>
                    <td>{$configId}</td>
                    <td>" . $s($r['name']) . "</td>
                    <td>" . $s($r['value']) . "</td>
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
    "<a href='{$self}'>" . $s($title) . '</a>',
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

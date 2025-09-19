<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Database;

global $container, $site_config, $CURUSER;

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$stdhead = [];
$stdfoot = [];
$HTMLOUT = '';

$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $value = trim($_POST['value'] ?? '');
            if ($name === '' || $value === '') {
                stderr(_('Error'), _('Missing name or value'));
            }
            $db->perform('INSERT INTO config (name, value) VALUES (:name, :value)', [
                'name' => $name,
                'value' => $value,
            ]);
            header('Location: ?tool=class_config');
            exit;
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            header('Location: ?tool=class_config');
            exit;
        }
        break;

    case 'delete':
        if ($id > 0) {
            $db->perform('DELETE FROM config WHERE id = :id', ['id' => $id]);
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
            $body .= "
                <tr>
                    <td>{$r['id']}</td>
                    <td>" . htmlsafechars($r['name']) . "</td>
                    <td>" . htmlsafechars($r['value']) . "</td>
                    <td>
                        <a href='?tool=class_config&amp;action=edit&amp;id={$r['id']}'>Edit</a> |
                        <a href='?tool=class_config&amp;action=delete&amp;id={$r['id']}'>Delete</a>
                    </td>
                </tr>";
        }
        $HTMLOUT .= main_table($body, $heading);
        break;
}

$title = _('Configuration');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

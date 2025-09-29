<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;


global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Cache $cache */
$cache = $container->get(Cache::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$HTMLOUT = '';

// Merge params
$edit_params = array_merge($_GET, $_POST);
$action = isset($edit_params['action']) ? (string) $edit_params['action'] : '';
$id     = isset($edit_params['id']) ? (int) $edit_params['id'] : 0;
$name   = isset($edit_params['name']) ? (string) $edit_params['name'] : '';
$image  = isset($edit_params['image']) ? (string) $edit_params['image'] : '';
$bonus  = isset($edit_params['bonus']) ? 1 : 0;

// Helpers
function mood_row(string $name, string $image, int $bonus): string
{
    global $config;
    return '<tr>'
        . "<td><img src=\"" . (string) $config->get('paths.images_baseurl') . "smilies/" . htmlsafechars($image) . "\" alt=\"\"></td>"
        . '<td>' . htmlsafechars($name) . '</td>'
        . '<td>' . htmlsafechars($image) . '</td>'
        . '<td>' . ($bonus !== 0 ? _('Yes') : _('No')) . '</td>';
}

// Actions
if ($action === 'added') {
    // prevent adding placeholders
    if ($name !== 'is example mood' && $image !== 'smiley1.gif') {
        $db->run(
            'INSERT INTO moods (name, image, bonus) VALUES (:name, :image, :bonus)',
            [':name' => $name, ':image' => $image, ':bonus' => (int) $bonus]
        );
        $cache->delete('topmoods');
        audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => [$name]]);
        if (function_exists('write_log')) {
            write_log('<b>' . _('Mood Added') . '</b> ' . htmlsafechars($CURUSER['username']) . ' - ' . htmlsafechars($name) . '<img src="' . (string) $config->get('paths.images_baseurl') . 'smilies/' . htmlsafechars($image) . '" alt="">');
        }
    }
} elseif ($action === 'edited') {
    if ($id > 0) {
        $db->run(
            'UPDATE moods SET name = :name, image = :image, bonus = :bonus WHERE id = :id',
            [':name' => $name, ':image' => $image, ':bonus' => (int) $bonus, ':id' => (int) $id]
        );
        $cache->delete('topmoods');
        audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => [$name]]);
        if (function_exists('write_log')) {
            write_log('<b>' . _('Mood Edited') . '</b> ' . htmlsafechars($CURUSER['username']) . ' - ' . htmlsafechars($name) . '<img src="' . (string) $config->get('paths.images_baseurl') . 'smilies/' . htmlsafechars($image) . '" alt="">');
        }
    }
}

// Forms
if ($action === 'edit' && $id > 0) {
    $row = $db->fetch('SELECT * FROM moods WHERE id = :id', [':id' => (int) $id]);
    if ($row) {
        $HTMLOUT .= "<h1 class='has-text-centered'>" . _('Edit Mood') . "</h1>
            <form method='post' action='staffpanel.php?tool=edit_moods&amp;action=edited' enctype='multipart/form-data' accept-charset='utf-8'>
            <table class='table table-bordered table-striped'>
                <tr>
                    <td class='colhead'>" . _('Name') . "</td>
                    <td><input type='text' name='name' size='40' value='" . htmlsafechars((string) $row['name']) . "'></td>
                </tr>
                <tr>
                    <td class='colhead'>" . _('Image') . "</td>
                    <td><input type='text' name='image' size='40' value='" . htmlsafechars((string) $row['image']) . "'></td>
                </tr>
                <tr>
                    <td class='colhead'>" . _('Bonus') . "</td>
                    <td><input type='checkbox' name='bonus' " . ((int) $row['bonus'] === 1 ? 'checked' : '') . "></td>
                </tr>
                <tr>
                    <td colspan='2' class='has-text-centered'>
                        <input type='hidden' name='id' value='" . (int) $id . "'>
                        <input type='submit' name='okay' value='" . _('Save') . "' class='button is-small'>
                    </td>
                </tr>
            </table>
            </form>";
    }
} else {
    // Add form
    $HTMLOUT .= "<h1 class='has-text-centered'>" . _('Add New Mood') . "</h1>
         <form method='post' action='staffpanel.php?tool=edit_moods&amp;action=added' enctype='multipart/form-data' accept-charset='utf-8'>
         <table class='table table-bordered table-striped'>
            <tr>
                <td class='colhead'>" . _('Name') . "</td>
                <td><input type='text' name='name' size='40' value='is example mood'></td>
            </tr>
            <tr>
                <td class='colhead'>" . _('Image') . "</td>
                <td><input type='text' name='image' size='40' value='smiley1.gif'></td>
            </tr>
            <tr>
                <td class='colhead'>" . _('Bonus') . "</td>
                <td><input type='checkbox' name='bonus'></td>
            </tr>
            <tr>
                <td colspan='2' class='has-text-centered'>
                    <input type='submit' name='okay' value='" . _('Add') . "' class='button is-small'>
                </td>
            </tr>
         </table>
         </form>";
}

// List moods
$HTMLOUT .= '<h1 class="has-text-centered">' . _('Current Moods') . '</h1>';
$HTMLOUT .= "<table class='table table-bordered table-striped'>
      <tr>
        <td class='colhead'>" . _('Added') . "</td>
        <td class='colhead'>" . _('Name') . "</td>
        <td class='colhead'>" . _('Image') . "</td>
        <td class='colhead'>" . _('Bonus') . "</td>
        <td class='colhead'>" . _('Edit') . "</td>
      </tr>";

$rows = $db->fetchAll('SELECT * FROM moods ORDER BY id');
if (!empty($rows)) {
    $color = true;
    foreach ($rows as $arr) {
        $HTMLOUT .= '<tr ' . (($color = !$color) ? ' style="background-color:#000000;"' : 'style="background-color:#0f0f0f;"') . '>
            <td><img src="' . (string) $config->get('paths.images_baseurl') . 'smilies/' . htmlsafechars((string) $arr['image']) . '" alt=""></td>
            <td>' . htmlsafechars((string) $arr['name']) . '</td>
            <td>' . htmlsafechars((string) $arr['image']) . '</td>
            <td>' . ((int) $arr['bonus'] !== 0 ? _('Yes') : _('No')) . '</td>
            <td><a style="color:#FF0000" href="' . (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=edit_moods&amp;id=' . (int) $arr['id'] . '&amp;action=edit">' . _('Edit') . '</a></td>
        </tr>';
    }
}
$HTMLOUT .= '</table>';

$title = _('Edit Moods');
$breadcrumbs = [
    "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];

echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

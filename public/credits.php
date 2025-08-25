<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_comments.php';
$user = check_user_status();
global $container;
$db = $container->get(Database::class);, $site_config;

$HTMLOUT = '';
$action = isset($_GET['action']) ? htmlsafechars($_GET['action']) : '';
$act_validation = [
    '',
    'add',
    'edit',
    'delete',
    'update',
];

$id = (isset($_GET['id']) ? (int) $_GET['id'] : '');

if (!in_array($action, $act_validation)) {
    stderr(_('Error'), 'Unknown action.');
}

if (isset($_POST['action']) === 'add' && has_access($user['class'], UC_SYSOP, 'coder')) {
    $name = ($_POST['name']);
    $description = ($_POST['description']);
    $category = ($_POST['category']);
    $link = ($_POST['link']);
    $status = ($_POST['status']);
    $credit = ($_POST['credit']);
    $db->run('INSERT INTO modscredits (name, description,  category,  pu239lnk,  status, credit) VALUES (' . sqlesc($name) . ', ' . sqlesc($description) . ', ' . sqlesc($category) . ', ' . sqlesc($link) . ', ' . sqlesc($status) . ', ' . sqlesc($credit) . ')') or sqlerr(__FILE__, __LINE__);
    header("Location: {$_SERVER['PHP_SELF']}");
    app_halt('Exit called');
}

if ($action === 'delete' && has_access($user['class'], UC_SYSOP, 'coder')) {
    if (!$id) {
        stderr(_('Error'), _('Fuck something went Pete Tong!'));
    }
    $db->run(");
    header("Location: {$_SERVER['PHP_SELF']}");
    app_halt('Exit called');
}

if ($action === 'edit' && has_access($user['class'], UC_SYSOP, 'coder')) {
    $id = (int) $_GET['id'];
    $rows = $db->fetchAll('SELECT name, description, category, pu239lnk, status, credit FROM modscredits WHERE id =' . $id . '');
    if (empty($rows)) {
        stderr(_('Error'), _('No credit mod found with that ID!'));
    }
    while ($mod = mysqli_fetch_assoc($res)) {
        $HTMLOUT .= "
        <form method='post' action='" . $_SERVER['PHP_SELF'] . '?action=update&amp;id=' . $id . "' enctype='multipart/form-data' accept-charset='utf-8'>
            <table>
                <tr>
                    <td class='rowhead'>" . _('Mod name') . "</td>
                    <td style='padding: 0'><input type='text' size='60' maxlength='120' name='name' " . "value='" . htmlsafechars($mod['name']) . "'></td>
                </tr>
                <tr>
                    <td class='rowhead'>" . _('Description') . "</td>
                    <td style='padding: 0'>
                        <input type='text' size='60' maxlength='120' name='description' value='" . htmlsafechars($mod['description']) . "'>
                    </td>
                </tr>
                <tr>
                    <td class='rowhead'>" . _('Category') . "</td>
                    <td style='padding: 0'>
                        <select name='category'>";
        $result = $db->run(');
}

$rows = $db->fetchAll('SELECT * FROM modscredits');
$fluent = $db; // alias
$fluent = $container->get(Database::class);
$credits = $fluent->from('modscredits')
                  ->orderBy('id')
                  ->fetchAll();
$heading = '
    <tr>
        <th>' . _('Name') . '</th>
        <th>' . _('Category') . '</th>
        <th>' . _('Status') . '</th>
        <th>' . _('Credits') . '</th>
    </tr>';

if (empty($credits)) {
    $body = "
    <tr>
        <td colspan='4' class='has-text-centered'>" . _('There are no credits so far!!') . '</td>
    </tr>';
} else {
    $body = '';
    foreach ($credits as $row) {
        $id = $row['id'];
        $name = $row['name'];
        $category = $row['category'];
        if ($row['status'] === 'In-Progress') {
            $status = '[b][color=#ff0000]' . $row['status'] . '[/color][/b]';
        } else {
            $status = '[b][color=#018316]' . $row['status'] . '[/color][/b]';
        }
        $link = $row['pu239lnk'];
        $credit = $row['credit'];
        $descr = $row['description'];

        $body .= "
    <tr>
        <td><a target='_blank' class='is-link' href='" . $link . "'>" . htmlsafechars(CutName($name, 60)) . '</a>';
        if (has_access($user['class'], UC_ADMINISTRATOR, 'coder')) {
            $body .= "&#160;<a class='is-link_blue' href='?action=edit&amp;id=" . $id . "'>[" . _('Edit') . "]</a>&#160;<a class='is-link_blue' href=\"javascript:confirm_delete(" . $id . ');">[' . _('Delete') . ']</a>';
        }

        $body .= "<br><span class='small'>" . htmlsafechars($descr) . '</span></td>
        <td><b>' . htmlsafechars($category) . '</b></td>
        <td><b>' . format_comment($status) . '</b></td>
        <td>' . htmlsafechars($credit) . '</td>
    </tr>';
    }
}
$HTMLOUT .= main_table($body, $heading);

if ($user['class'] >= UC_MAX) {
    $HTMLOUT .= "
    <form method='post' action='{$_SERVER['PHP_SELF']}' enctype='multipart/form-data' accept-charset='utf-8'>
    <h2 class='has-text-centered top20'>" . _('Add Mods & Credits') . "</h2>
        <input type='hidden' name='action' value='add'>";
    $body = '
    <tr>
        <td>' . _('Name:') . "</td>
        <td><input name='name' type='text' class='w-100' required></td>
    </tr>
    <tr>
        <td>" . _('Description:') . "</td>
        <td><input name='description' type='text' class='w-100' maxlength='120' required></td>
    </tr>
    <tr>
        <td>" . _('Category:') . "</td>
        <td>
            <select name='category' required>
                <option value=''>" . _('Select One') . "</option>
                <option value='Addon'>" . _('Addon') . "</option>
                <option value='Forum'>" . _('Forum') . "</option>
                <option value='Message/Email'>" . _('Message/E-mail') . "</option>
                <option value='Display/Style'>" . _('Display/Style') . "</option>
                <option value='Staff/Tools'>" . _('Staff Tools') . "</option>
                <option value='Browse/Torrent/Details'>" . _('Browse/Torrents/Details') . "</option>
                <option value='Misc'>" . _('Misc') . '</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>' . _('Link:') . "</td>
        <td><input name='link' type='text' class='w-100' required></td>
    </tr>
    <tr>
        <td>" . _('Status:') . "</td>
        <td>
            <select name='status' required>
                <option value=''>" . _('Select One') . "</option>
                <option value='In-Progress'>" . _('In-Progress') . "</option>
                <option value='Complete'>" . _('Complete') . '</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>' . _('Credits:') . "</td>
        <td><input name='credit' type='text' class='w-100' maxlength='120' required><br><span class='small'>" . _('Values separated by commas') . "</span></td>
    </tr>
    <tr>
        <td colspan='2' class='has-text-centered'>
            <input type='submit' value='" . _('Add Credits') . "' class='button is-small'>
        </td>
    </tr>";
    $HTMLOUT .= main_table($body) . '
    </form>';
}
$title = _('Mod Credits');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

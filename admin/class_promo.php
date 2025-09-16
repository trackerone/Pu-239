<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Database;
use Pu239\Session;


global $container, $site_config, $CURUSER;

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Session $session */
$session = $container->get(Session::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

// ---------------------------------------------------------------------
// Load existing promo rules
// ---------------------------------------------------------------------
$class_config = [];
$promos = $db->fetchAll('SELECT * FROM class_promo ORDER BY id');
foreach ($promos as $ac) {
    $class_config[$ac['name']] = [
        'id' => (int) $ac['id'],
        'name' => (string) $ac['name'],
        'min_ratio' => (float) $ac['min_ratio'],
        'uploaded' => (int) $ac['uploaded'],
        'time' => (int) $ac['time'],
        'low_ratio' => (float) $ac['low_ratio'],
    ];
}

$possible_modes = ['add', 'edit', 'remove', ''];
$mode = isset($_GET['mode']) ? (string) htmlsafechars($_GET['mode']) : '';
if (!in_array($mode, $possible_modes, true)) {
    $session->set('is-error', _('A ruffian that will swear, drink, dance, revel the night, rob, murder and commit the oldest of ins the newest kind of ways.'));
    $mode = '';
}

// ---------------------------------------------------------------------
// POST handling (add / edit / remove)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'edit') {
        $rows = [];
        foreach ($class_config as $cName => $current) {
            // POST structure per row: [ name, id, min_ratio, uploaded, time, low_ratio ]
            if (!isset($_POST[$cName]) || !is_array($_POST[$cName])) {
                continue;
            }
            $p = $_POST[$cName];
            $posted = [
                'name' => isset($p[0]) ? (string) $p[0] : $cName,
                'id' => isset($p[1]) ? (int) $p[1] : $current['id'],
                'min_ratio' => isset($p[2]) ? (float) $p[2] : $current['min_ratio'],
                'uploaded' => isset($p[3]) ? (int) $p[3] : $current['uploaded'],
                'time' => isset($p[4]) ? (int) $p[4] : $current['time'],
                'low_ratio' => isset($p[5]) ? (float) $p[5] : $current['low_ratio'],
            ];

            $changed = $posted['name'] !== $current['name']
                || $posted['min_ratio'] !== (float) $current['min_ratio']
                || $posted['uploaded'] !== (int) $current['uploaded']
                || $posted['time'] !== (int) $current['time']
                || $posted['low_ratio'] !== (float) $current['low_ratio'];

            if ($changed) {
                $rows[] = [
                    'name' => $posted['name'],
                    'min_ratio' => $posted['min_ratio'],
                    'uploaded' => $posted['uploaded'],
                    'time' => $posted['time'],
                    'low_ratio' => $posted['low_ratio'],
                ];
            }
        }

        if (!empty($rows)) {
            // Build a single UPSERT statement with bound params
            $valuesSql = [];
            $params = [];
            foreach ($rows as $i => $r) {
                $valuesSql[] = "(:name{$i}, :min_ratio{$i}, :uploaded{$i}, :time{$i}, :low_ratio{$i})";
                $params[":name{$i}"] = $r['name'];
                $params[":min_ratio{$i}"] = $r['min_ratio'];
                $params[":uploaded{$i}"] = $r['uploaded'];
                $params[":time{$i}"] = $r['time'];
                $params[":low_ratio{$i}"] = $r['low_ratio'];
            }
            $sql = 'INSERT INTO class_promo (name, min_ratio, uploaded, time, low_ratio) VALUES ' . implode(', ', $valuesSql) . ' '
                . 'ON DUPLICATE KEY UPDATE '
                . 'name = VALUES(name), '
                . 'min_ratio = VALUES(min_ratio), '
                . 'uploaded = VALUES(uploaded), '
                . 'time = VALUES(time), '
                . 'low_ratio = VALUES(low_ratio)';

            $ok = $db->run($sql, $params);
            if ($ok) {
                $session->set('is-success', _('User promotion configuration was saved!'));
            } else {
                $session->set('is-error', _('There was an error while executing the update query or nothing was updated.'));
            }
        } else {
            $session->set('is-warning', _('No changes detected.'));
        }
    } elseif ($mode === 'add') {
        // Resolve selected numeric class id -> class name string from class_config
        if (!isset($_POST['name']) || $_POST['name'] === '') {
            $session->set('is-error', _('We cannot have empty class name!'));
        } else {
            $class_id = (int) $_POST['name'];
            $name = $db->fetch('SELECT name FROM class_config WHERE value = :id AND name NOT IN (\'UC_STAFF\', \'UC_MIN\', \'UC_MAX\')', [':id' => $class_id]);
            $class_name = $name['name'] ?? '';
            if ($class_name === '') {
                $session->set('is-error', _('Invalid class selected.'));
            } else {
                $min_ratio = isset($_POST['min_ratio']) ? (float) $_POST['min_ratio'] : null;
                $uploaded = isset($_POST['uploaded']) ? (int) $_POST['uploaded'] : null;
                $time = isset($_POST['time']) ? (int) $_POST['time'] : null;
                $low_ratio = isset($_POST['low_ratio']) ? (float) $_POST['low_ratio'] : null;

                if ($min_ratio === null) {
                    $session->set('is-error', _('We cannot have empty min ratio!'));
                } elseif ($uploaded === null) {
                    $session->set('is-error', _('We cannot have empty uploaded!'));
                } elseif ($time === null) {
                    $session->set('is-error', _('We cannot have empty time!'));
                } elseif ($low_ratio === null) {
                    $session->set('is-error', _('We cannot have empty low ratio!'));
                } else {
                    $ok = $db->run(
                        'INSERT INTO class_promo (name, min_ratio, uploaded, time, low_ratio) VALUES (:name, :min_ratio, :uploaded, :time, :low_ratio) '
                        . 'ON DUPLICATE KEY UPDATE min_ratio = VALUES(min_ratio), uploaded = VALUES(uploaded), time = VALUES(time), low_ratio = VALUES(low_ratio)',
                        [
                            ':name' => $class_name,
                            ':min_ratio' => $min_ratio,
                            ':uploaded' => $uploaded,
                            ':time' => $time,
                            ':low_ratio' => $low_ratio,
                        ]
                    );
                    if ($ok) {
                        $session->set('is-success', _('Promotion rule added/updated.'));
                    } else {
                        $session->set('is-error', _('Insert failed.'));
                    }
                }
            }
        }
    } elseif ($mode === 'remove') {
        if (!isset($_POST['remove']) || $_POST['remove'] === '') {
            $session->set('is-error', _('Missing class name to remove.'));
        } else {
            $ok = $db->run('DELETE FROM class_promo WHERE name = :name', [':name' => (string) $_POST['remove']]);
            $session->set($ok ? 'is-success' : 'is-error', $ok ? _('Promotion rule removed.') : _('Nothing removed.'));
        }
    }

    // Reload latest config after writes
    $promos = $db->fetchAll('SELECT * FROM class_promo ORDER BY id');
}

// ---------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------
$HTMLOUT = '';

if (!empty($promos)) {
    $head_top = "
    <h3 class='has-text-centered top20'>" . _('User Promotion Settings') . "</h3>
    <form name='edit' action='{$site_config['paths']['baseurl']}/staffpanel.php?tool=class_promo&amp;mode=edit' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";

    $heading = "
        <tr>
            <th class='has-text-centered'>" . _('Class Name') . "</th>
            <th class='has-text-centered'>" . _('Min Ratio') . "</th>
            <th class='has-text-centered'>" . _('Min Uploaded (GB)') . "</th>
            <th class='has-text-centered'>" . _('Min Time On Site (Days)') . "</th>
            <th class='has-text-centered'>" . _('Low Ratio') . "</th>
            <th class='has-text-centered'>" . _('Remove') . '</th>
        </tr>';

    $body = '';
    foreach ($promos as $arr) {
        $body .= '
        <tr>
            <td>
                ' . get_user_class_name(constant($arr['name']), false) . "
                <input type='hidden' name='" . htmlsafechars($arr['name']) . "[]' value='" . htmlsafechars($arr['name']) . "'>
                <input type='hidden' name='" . htmlsafechars($arr['name']) . "[]' value='" . (int) $arr['id'] . "'>
            </td>
            <td class='has-text-centered'><input type='text' name='" . htmlsafechars($arr['name']) . "[]' value='" . format_comment((string) $arr['min_ratio']) . "' class='has-text-centered'></td>
            <td class='has-text-centered'><input type='text' name='" . htmlsafechars($arr['name']) . "[]' value='" . format_comment((string) $arr['uploaded']) . "' class='has-text-centered'></td>
            <td class='has-text-centered'><input type='text' name='" . htmlsafechars($arr['name']) . "[]' value='" . format_comment((string) $arr['time']) . "' class='has-text-centered'></td>
            <td class='has-text-centered'><input type='text' name='" . htmlsafechars($arr['name']) . "[]' value='" . format_comment((string) $arr['low_ratio']) . "' class='has-text-centered'></td>
            <td class='has-text-centered'>
                <form name='remove' action='staffpanel.php?tool=class_promo&amp;mode=remove' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
                    <input type='hidden' name='remove' value='" . htmlsafechars($arr['name']) . "'>
                    <input type='submit' value='" . _('Remove') . "' class='button is-small'>
                </form>
            </td>
        </tr>";
    }

    $body .= "
        <tr>
            <td colspan='7' class='has-text-centered'>
                <input type='submit' value='" . _('Apply changes') . "' class='button is-small'>
            </td>
        </tr>";

    $HTMLOUT .= $head_top . main_table($body, $heading) . "
    </form>
    <div class='margin20 has-text-centered'>
        " . _('Min ratio = The minimum ratio a user needs to achieve to reach this class.') . '<br>
        ' . _('Min Uploaded = The minimum uploaded amount a user needs to achieve to reach this class.') . '<br>
        ' . _('Min Time On Site = The minimum time a user needs to of been registered with the site to reach this class.') . '<br>
        ' . _('Low Ratio = If a user in this class falls below this ratio, they will be demoted back to the previous class.') . '<br>
    </div>';
}

$HTMLOUT .= "
    <h3 class='has-text-centered top20'>" . _('Add New Promotion Rule') . "</h3>
    <form name='add' action='staffpanel.php?tool=class_promo&amp;mode=add' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";
$heading = "
        <tr>
            <th class='w-15'>" . _('Class Name') . '</th>
            <th>' . _('Min Ratio') . '</th>
            <th>' . _('Min Uploaded (GB)') . '</th>
            <th>' . _('Min Time On Site (Days)') . '</th>
            <th>' . _('Low Ratio') . '</th>
        </tr>";

$body = "
        <tr>
            <td>
                <select name='name'>";
$maxclass = UC_STAFF;
for ($i = 1; $i < $maxclass; ++$i) {
    $body .= "
                    <option value='" . (int) $i . "'>" . get_user_class_name((int) $i) . '</option>';
}
$body .= "
                </select>
            </td>
            <td><input type='text' name='min_ratio' value='' class='w-100'></td>
            <td><input type='text' name='uploaded' value='' class='w-100'></td>
            <td><input type='text' name='time' value='' class='w-100'></td>
            <td><input type='text' name='low_ratio' value='' class='w-100'></td>
        </tr>
        <tr><td colspan='5' class='has-text-centered'>
                <input type='submit' value='" . _('Add new class') . "' class='button is-small'>
            </td>
        </tr>";
$HTMLOUT .= main_table($body, $heading) . '
    </form>';
$title = _('Promotion Settings');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

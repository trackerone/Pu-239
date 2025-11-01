<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use PU239\Security\AuthZ;
use Pu239\Database;
use Pu239\Session;

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

$extensionsList = $s(implode(', ', $config->get('coders.log_allowed_ext')));

$HTMLOUT = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): csrf
}

$file_data = ROOT_DIR . 'dir_list' . DIRECTORY_SEPARATOR . 'data_' . $CURUSER['username'] . '.txt';
if (file_exists($file_data)) {
    $data = json_decode(file_get_contents($file_data), true);
    $exist = true;
} else {
    $exist = false;
}
$fetch_set = [];
$i = 0;
$directories = [ROOT_DIR];
$included_extentions = $config->get('coders.log_allowed_ext');
foreach ($directories as $path) {
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $name => $object) {
        preg_match('/(\.idea|\.git|vendor|node_modules)/', $name, $match);
        if (empty($match)) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if (in_array($ext, $included_extentions)) {
                $fetch_set[$i]['modify'] = filemtime($name);
                $fetch_set[$i]['size'] = filesize($name);
                $fetch_set[$i]['hash'] = hash_file('sha256', $name);
                $fetch_set[$i]['name'] = $name;
                $fetch_set[$i]['key'] = $i;
                ++$i;
            }
        }
    }
}
if (!$exist || (isset($_POST['update']) && ($_POST['update'] === 'Update'))) {
    $data = json_encode($fetch_set);
    $session = $container->get(Session::class);
    if (file_put_contents($file_data, $data)) {
        $session->set('is-success', _fe("Coder's Log was updated for {0}", $CURUSER['username']));
        audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => ['coders.log']]);
    } else {
        $session->set('is-warning', _fe('Could not save data to: [p]{0}[/p]', $file_data));
    }
    $data = $fetch_set;
    unset($_POST);
}
reset($fetch_set);
reset($data);
$current = $fetch_set;
$last = $data;
foreach ($current as $x) {
    foreach ($last as $y) {
        if ($x['name'] == $y['name']) {
            if (($x['hash'] === $y['hash'])) {
                unset($current[$x['key']], $last[$y['key']]);
            } else {
                $current[$x['key']]['status'] = 'modified';
            }
        }
        if (isset($last[$y['key']])) {
            $last[$y['key']]['status'] = 'deleted';
        }
    }
    if (isset($current[$x['key']]['name']) && !isset($current[$x['key']]['status'])) {
        $current[$x['key']]['status'] = 'new';
    }
}
$current += $last;
unset($last, $data, $fetch_set);

$HTMLOUT .= "
        <h1 class='has-text-centered top20'>Coder's Log</h1>
        <div class='bordered bottom20'>
            <div class='alt_bordered bg-00 padding20'>
                <div class='has-text-centered'>Tracking {$extensionsList} files only!</div>
                <div class='has-text-centered'>" . number_format(count($current)) . ' files have been added, modifed or deleted since your last update of the ' . number_format($i) . " files being tracked.</div>
            </div>
        </div>
        <div class='table-wrapper'>
        <table class='table table-bordered table-striped'>
            <thead>
                <tr>
                    <th>" . _('New files added since last check.') . "</th>
                    <th class='w-15'>" . _('Added.') . '</th>
                </tr>
            </thead>';
reset($current);
$count = 0;
$current = array_msort($current, ['name' => SORT_ASC]);
foreach ($current as $x) {
    if ($x['status'] === 'new') {
        $HTMLOUT .= '
                <tr>
                    <td>' . $s(str_replace(ROOT_DIR, '', $x['name'])) . '
                    </td>
                    <td>' . get_date((int) $x['modify'], 'DATE', 0, 1) . '
                    </td>
                </tr>';
        ++$count;
    }
}
if (!$count) {
    $HTMLOUT .= "
                <tr>
                    <td colspan='2' class='has-text-primary'>" . _('No new files added since last check.') . '</td>
                </tr>';
}
$HTMLOUT .= "
        </table>
        </div>
        <div class='table-wrapper'>
        <table class='table table-bordered table-striped top20'>
            <thead>
                <tr>
                    <th>" . _('Modified files since last check.') . "</th>
                    <th class='w-15'>" . _('Modified.') . '</th>
                </tr>
            </thead>';
reset($current);
$count = 0;
foreach ($current as $x) {
    if ($x['status'] === 'modified') {
        $HTMLOUT .= '
                <tr>
                    <td>' . $s(str_replace(ROOT_DIR, '', $x['name'])) . '
                    </td>
                    <td>' . get_date((int) $x['modify'], 'DATE', 0, 1) . '
                    </td>
                </tr>';
        ++$count;
    }
}
if (!$count) {
    $HTMLOUT .= "
                <tr>
                    <td colspan='2' class='has-text-primary'>" . _('No files modified since last check.') . '</td>
                </tr>';
}
$HTMLOUT .= "
        </table>
        </div>
        <div class='table-wrapper'>
        <table class='table table-bordered table-striped top20'>
            <thead>
                <tr>
                    <th>" . _('Files deleted since last check.') . "</th>
                    <th class='w-15'>" . _('Deleted.') . '</th>
                </tr>
            </thead>';
reset($current);
$count = 0;
foreach ($current as $x) {
    if ($x['status'] === 'deleted') {
        $HTMLOUT .= '
                <tr>
                    <td>' . $s(str_replace(ROOT_DIR, '', $x['name'])) . '
                    </td>
                    <td>' . get_date((int) $x['modify'], 'DATE', 0, 1) . '
                    </td>
                </tr>';
        ++$count;
    }
}
if (!$count) {
    $HTMLOUT .= "
                <tr>
                    <td colspan='2' class='has-text-primary'>" . _('No files deleted since last check.') . '</td>
                </tr>';
}
$HTMLOUT .= "
        </table>
        </div>
        <form method='post' action='staffpanel.php?tool=editlog&amp;action=editlog' enctype='multipart/form-data' accept-charset='utf-8'>
            <div class='has-text-centered top20 bottom20'>
                <input name='update' type='submit' value='" . _('Update') . "' class='button is-small'>
            </div>
        </form>";
$title = _('File Edit Log');
$breadcrumbs = [
    "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$self}'>" . $s($title) . '</a>',
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

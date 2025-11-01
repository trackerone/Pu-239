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
// AUTO_ADMIN_MEDIUM: 2025-10-23

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$self = $s($_SERVER['PHP_SELF'] ?? '');
$baseurl = $s($config->get('paths.baseurl'));

$file = (string) $config->get('paths.flood_file');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): csrf
    $limits = isset($_POST['limit']) && is_array($_POST['limit']) ? $_POST['limit'] : [];
    foreach ($limits as $class => $limit) {
        if ((int) $limit === 0) {
            unset($limits[$class]);
        }
    }
    $session = $container->get(Session::class);
    if (file_put_contents($file, json_encode($limits))) {
        audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => array_keys($limits)]);
        $session->set('is-success', _('Flood Limits saved!'));
    } else {
        $session->set('is-error', _fe('Something went wrong make sure {0} exists and it is chmoded 0774', $file));
    }
}

if (!file_exists($file) || !is_array($limit = json_decode(file_get_contents($file)))) {
    $limit = [];
}
$out = "
        <form method='post' action='{$self}?tool=floodlimit&amp;action=floodlimit' enctype='multipart/form-data' accept-charset='utf-8'>";
$heading = '
        <tr>
            <th>' . _('User class') . '</th>
            <th>' . _('Limit') . '</th>
        </tr>';
$body = '';
for ($i = UC_MIN; $i <= UC_MAX; ++$i) {
    $limitValue = $s((string) ($limit[$i] ?? 0));
    $body .= "
        <tr>
            <td>" . get_user_class_name((int) $i) . "</td>
            <td><input name='limit[$i]' type='text' class='w-100' value='{$limitValue}'></td>
        </tr>";
}
$out .= main_table($body, $heading) . "
        <div class='has-text-centered'>
            <p class='padding10'>" . _('Note: if you want no limit for the user class set the limit to 0') . "</p>
            <input type='submit' value='" . _('Save') . "' class='button is-small margin20'>
        </div>
        </form>";
$title = _('Flood Limit');
$breadcrumbs = [
    "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$self}'>" . $s($title) . '</a>',
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($out) . stdfoot();

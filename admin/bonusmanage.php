<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$selfPath = $_SERVER['PHP_SELF'] ?? '';
$baseurlRaw = (string) $config->get('paths.baseurl');
$self = $s($selfPath);
$baseurl = $s($baseurlRaw);

$HTMLOUT = $count = '';
$rows = $db->fetchAll('SELECT * FROM bonus ORDER BY orderid, bonusname');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): csrf
    if (isset($_POST['id']) || isset($_POST['orderid']) || isset($_POST['points']) || isset($_POST['pointspool']) || isset($_POST['minpoints']) || isset($_POST['description']) || isset($_POST['enabled']) || isset($_POST['minclass'])) {
        $id = (int) $_POST['id'];
        $points = (int) $_POST['bonuspoints'];
        $pointspool = (int) $_POST['pointspool'];
        $minpoints = (int) $_POST['minpoints'];
        $minclass = (int) $_POST['minclass'];
        $descr = htmlsafechars($_POST['description']);
        $enabled = 'yes';
        if (empty($_POST['enabled'])) {
            $enabled = 'no';
        }
        $orderid = (int) $_POST['orderid'];
        $cache->delete('bonus_points_' . $id);
        $cache->delete('freeleech_alerts_');
        $cache->delete('doubleupload_alerts_');
        $cache->delete('halfdownload_alerts_');
        $sql = sql_query('UPDATE bonus SET orderid=' . sqlesc($orderid) . ', points = ' . sqlesc($points) . ', pointspool = ' . sqlesc($pointspool) . ', minpoints = ' . sqlesc($minpoints) . ', minclass = ' . sqlesc($minclass) . ', enabled = ' . sqlesc($enabled) . ', description = ' . sqlesc($descr) . ' WHERE id=' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
        sql_query("UPDATE bonus SET orderid=orderid + 1 WHERE orderid>= $orderid AND id != $id") or sqlerr(__FILE__, __LINE__);

        $ids = $db->fetchAll('SELECT id FROM bonus ORDER BY orderid, id');
        $iter = 0;
        foreach ($ids as $arr) {
            sql_query('UPDATE bonus SET orderid=' . ++$iter . ' WHERE id=' . $arr['id']) or sqlerr(__FILE__, __LINE__);
        }

        if ($sql) {
            header("Location: {$selfPath}?tool=bonusmanage");
            app_halt('Exit called');
        } else {
            stderr(_('Error'), _('Something went wrong with the sql query'));
        }
    }
}

$heading = '
        <tr>
            <th>' . _('Id') . '</th>
            <th>' . _('Order Id') . "</th>
            <th class='tooltipper' title='" . _('Enabled') . "'>E</th>
            <th>" . _('Bonus') . '</th>
            <th>' . _('Points') . '</th>
            <th>' . _('Points Pool') . '</th>
            <th>' . _('Min Points') . '</th>
            <th>' . _('Min Class') . "</th>
            <th class='w-20'>" . _('Description') . '</th>
            <th>' . _('Type') . '</th>
            <th>' . _('Quantity') . '</th>
            <th>' . _('Action') . '</th>
        </tr>';

$HTMLOUT = "
    <h1 class='has-text-centered'>" . _('Bonus Management') . '</h1>';

$body = '';
$submitLabel = $s(_('Submit'));
foreach ($rows as $arr) {
    $quantityDisplay = $s((string) $arr['menge']);
    if (in_array($arr['art'], ['traffic', 'traffic2', 'gift_1', 'gift_2'], true)) {
        $quantityValue = (float) $arr['menge'] / 1024 / 1024 / 1024;
        $quantityDisplay = $s((string) $quantityValue) . ' GB';
    }
    $id = (int) $arr['id'];
    $orderId = (int) $arr['orderid'];
    $bonusPoints = (int) $arr['points'];
    $pointsPool = (int) $arr['pointspool'];
    $minPoints = (int) $arr['minpoints'];
    $minClass = (int) $arr['minclass'];
    $idAttr = $s((string) $id);
    $orderIdAttr = $s((string) $orderId);
    $bonusPointsAttr = $s((string) $bonusPoints);
    $pointsPoolAttr = $s((string) $pointsPool);
    $minPointsAttr = $s((string) $minPoints);
    $minClassAttr = $s((string) $minClass);
    $enabledAttribute = $arr['enabled'] === 'yes' ? "checked='checked'" : '';
    $bonusName = format_comment($arr['bonusname']);
    $description = format_comment($arr['description']);
    $art = format_comment($arr['art']);
    $body .= <<<HTML
        <tr>
            <form name='bonusmanage' method='post' action='{$self}?tool=bonusmanage&amp;action=bonusmanage' enctype='multipart/form-data' accept-charset='utf-8'>
                <td><input name='id' type='hidden' value='{$idAttr}'>{$idAttr}</td>
                <td><input type='number' name='orderid' value='{$orderIdAttr}' class='w-100'></td>
                <td><input name='enabled' type='checkbox' {$enabledAttribute}></td>
                <td>{$bonusName}</td>
                <td><input type='number' name='bonuspoints' value='{$bonusPointsAttr}' class='w-100'></td>
                <td><input type='number' name='pointspool' value='{$pointsPoolAttr}' class='w-100'></td>
                <td><input type='number' name='minpoints' value='{$minPointsAttr}' class='w-100'></td>
                <td><input type='number' name='minclass' value='{$minClassAttr}' class='w-100'></td>
                <td><textarea name='description' rows='4' class='w-100'>{$description}</textarea></td>
                <td>{$art}</td>
                <td>{$quantityDisplay}</td>
                <td><input class='button is-small' type='submit' value='{$submitLabel}'></td>
            </form>
        </tr>
    HTML;
}

$HTMLOUT .= main_table($body, $heading);
$title = _('Bonus Manager');
$breadcrumbs = [
    "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$self}'>" . $s($title) . '</a>',
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

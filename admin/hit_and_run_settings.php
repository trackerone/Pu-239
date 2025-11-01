<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use PU239\Security\AuthZ;
use Pu239\Cache;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): csrf
    $session = $container->get(Session::class);
    // $fluent removed — use $this->db (ExtendedPdo)
    $updated = false;
    $changedKeys = [];
    foreach ($config->get('hnr_config') as $c_name => $c_value) {
        if (isset($_POST[$c_name]) && $_POST[$c_name] != $c_value) {
            $fluent->update('hit_and_run_settings')
                   ->set(['value' => $_POST[$c_name]])
                   ->where('name = ?', $c_name)
                   ->execute();

            $updated = true;
            $changedKeys[] = $c_name;
        }
    }
    if (!$updated) {
        $session->set('is-warning', _('There was an error while executing the update query or nothing was updated.'));
    } else {
        $cache = $container->get(Cache::class);
        $cache->delete('hnr_settings_');
        audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => $changedKeys]);
        $session->set('is-success', 'Update Successful');
    }
}

$HTMLOUT .= "
<h1 class='has-text-centered'>" . _('Hit And Run Settings') . "</h1>
<form action='{$self}?tool=hit_and_run_settings' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";

$HTMLOUT .= main_table("
    <tr><td class='w-50'>" . _('Hit And Run Online:') . '</td><td>' . _('Yes') . "<input type='radio' name='hnr_online' value='1' " . ($config->get('hnr_config.hnr_online') ? 'checked' : '') . '> ' . _('No') . "<input type='radio' name='hnr_online' value='0' " . (!$config->get('hnr_config.hnr_online') ? 'checked' : '') . "></td></tr>
    <tr><td class='w-50'>" . _('First Class (Under and Equal)') . "</td><td><input type='text' name='firstclass' size='20' value='" . htmlsafechars($config->get('hnr_config.firstclass')) . "'></td></tr>
    <tr><td class='w-50'>" . _('Second Class (Under)') . "</td><td><input type='text' name='secondclass' size='20' value='" . htmlsafechars($config->get('hnr_config.secondclass')) . "'></td></tr>
    <tr><td class='w-50'>" . _('Third Class (Above and Equal)') . "</td><td><input type='text' name='thirdclass' size='20' value='" . htmlsafechars($config->get('hnr_config.thirdclass')) . "'></td></tr>

    <tr><td class='w-50'>" . _('Torrent Age Group 1 Under') . "</td><td><input type='number' name='torrentage1' min='0' max='31' step='1' value='" . $config->get('hnr_config.torrentage1') . "'> " . _('Days') . "</td></tr>
    <tr><td class='w-50'>" . _('Torrent Age Group 2 Under') . "</td><td><input type='number' name='torrentage2' min='0' max='31' step='1' value='" . $config->get('hnr_config.torrentage2') . "'> " . _('Days') . "</td></tr>
    <tr><td class='w-50'>" . _('Torrent Age Group 3 Over') . "</td><td><input type='number' name='torrentage3' min='0' max='31' step='1' value='" . $config->get('hnr_config.torrentage3') . "'> " . _('Days') . "</td></tr>
    <tr><td colspan='2'><div class='has-text-centered size_6'>" . _('Group 1') . "</div></td></tr>

    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 1 First Class') . "</td><td><input type='number' name='_3day_first' min='0' max='4320' step='1' value='" . $config->get('hnr_config._3day_first') . "'>" . _(' Hours') . "</td></tr>
    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 1 Second Class') . "</td><td><input type='number' name='_3day_second' min='0' max='4320' step='1' value='" . $config->get('hnr_config._3day_second') . "'>" . _(' Hours') . "</td></tr>
    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 1 Third Class') . "</td><td><input type='number' name='_3day_third' min='0' max='4320' step='1' value='" . $config->get('hnr_config._3day_third') . "'>" . _(' Hours') . "</td></tr>
    <tr><td colspan='2'><div class='has-text-centered size_6'>" . _('Group 3') . "</div></td></tr>

    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 2 First Class') . "</td><td><input type='number' name='_14day_first' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_first') . "'>" . _(' Hours') . "</td></tr>
    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 2 Second Class') . "</td><td><input type='number' name='_14day_second' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_second') . "'>" . _(' Hours') . "</td></tr>
    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 2 Third Class') . "</td><td><input type='number' name='_14day_third' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_third') . "'>" . _(' Hours') . "</td></tr>
    <tr><td colspan='2'><div class='has-text-centered size_6'>" . _('Group 2') . "</div></td></tr>

    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 3 First Class') . "</td><td><input type='number' name='_14day_over_first' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_over_first') . "'>Hours</td></tr>
    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 3 Second Class') . "</td><td><input type='number' name='_14day_over_second' min='0' max='4320' step='1'  value='" . $config->get('hnr_config._14day_over_second') . "'>" . _(' Hours') . "</td></tr>
    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 3 Third Class') . "</td><td><input type='number' name='_14day_over_third' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_over_third') . "'>" . _(' Hours') . "</td></tr>
    <tr><td colspan='2'></td></tr>

    <tr><td class='w-50'>" . _('Time Allowed Before Mark Of Cain') . "</td><td><input type='number' name='caindays' min='0' max='31' step='0.1' value='" . $config->get('hnr_config.caindays') . "'>" . _(' Days') . "</td></tr>
    <tr><td class='w-50'>" . _('Allowed Mark Of Cains') . "</td><td><input type='number' name='cainallowed' min='0' max='500' step='1' value='" . $config->get('hnr_config.cainallowed') . "'></td></tr>
    
    <tr><td colspan='2'></td></tr>
    <tr>
        <td class='w-50'>" . _('Are all downloads subject to HnR, including incomplete downloads?') . "</td>
        <td>
            <input type='radio' name='all_torrents' value='1' " . ($config->get('hnr_config.all_torrents') ? 'checked' : '') . '>' . _('Yes') . "
            <input type='radio' name='all_torrents' value='0' " . (!$config->get('hnr_config.all_torrents') ? 'checked' : '') . '>' . _(' No') . "
        </td>
    </tr>

    <tr><td colspan='2' class='has-text-centered'><input type='submit' value='" . _('Apply changes') . "' class='button is-small'></td></tr>") . '</form>';

$title = _('HnR Settings');
$breadcrumbs = [
    "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$self}'>" . $s($title) . '</a>',
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

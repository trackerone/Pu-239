<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;

global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);
$cache = $container->get(Cache::class);

if ($user['paranoia'] < 1 || $CURUSER['id'] == $id || $CURUSER['class'] >= UC_STAFF) {
    $What_Cache = 'port_data_';
    $Ident_Client = '';
    $port_data = $cache->get($What_Cache . $id);
    if ($port_data === false || is_null($port_data)) {
        $port_data = $db->fetch('SELECT connectable, port, agent FROM peers WHERE userid = :id LIMIT 1', [
            ':id' => $id,
        ]);
        $cache->set('port_data_' . $id, $port_data, $config->get('expires.port_data'));
    }
    if (!empty($port_data) && isset($port_data['agent'])) {
        $connect = $port_data['connectable'];
        $port = (int) $port_data['port'];
        $Ident_Client = $port_data['agent'];
        if ($connect === 'yes') {
            $connectable = "
    <div class='has-text-success tooltipper' title='" . _('Sorted Yer connectable') . "'>
        <i class='icon-thumbs-up icon' aria-hidden='true'></i><b>" . _('Yes') . '</b>
    </div>';
        } else {
            $connectable = "
    <div class='has-text-danger tooltipper' title='" . _('Contact Site Staff') . "'>
        <i class='icon-thumbs-down icon' aria-hidden='true'></i><b>" . _('No') . '</b>
    </div>';
        }
    } else {
        $connectable = "<span style='color: orange;'><b>" . _('Unknown') . '</b></span>';
    }
    $table_data .= "
        <tr>
            <td class='rowhead'>" . _('Connectable') . '</td>
            <td>' . $connectable . '</td>
        </tr>';
    if (!empty($port)) {
        $table_data .= "
        <tr>
            <td class='rowhead'>" . _('Port') . "</td>
            <td class='tablea'>$port</td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Client') . "</td>
            <td class='tablea'>" . htmlsafechars($Ident_Client) . '</td>
        </tr>';
    }
}

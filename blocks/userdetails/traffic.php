<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use PU239\Config\ConfigRepository;
use Pu239\Database;

global $container, $CURUSER, $user;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

if ($user['paranoia'] < 2 || $CURUSER['id'] == $id || $CURUSER['class'] >= UC_STAFF) {
    $days = round((TIME_NOW - $user['registered']) / 86400);
    if ($config->get('site.ratio_free')) {
        $table_data .= "
        <tr>
            <td class='rowhead'>" . _('Happy days') . '</td>
            <td>' . _('Ratio free tracker in effect') . "</td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Uploaded') . '</td>
            <td>' . mksize($user['uploaded']) . ' - ' . _('Daily: ') . ($days > 1 ? mksize((int) floor($user['uploaded'] / $days)) : mksize($user['uploaded'])) . '</td>
        </tr>';
    } else {
        $table_data .= "
        <tr>
            <td class='rowhead'>" . _('Downloaded') . '</td>
            <td>' . mksize($user['downloaded']) . ' - ' . _('Daily: ') . ($days > 1 ? mksize((int) floor($user['downloaded'] / $days)) : mksize($user['downloaded'])) . "</td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Uploaded') . '</td>
            <td>' . mksize($user['uploaded']) . ' - ' . _('Daily: ') . ($days > 1 ? mksize((int) floor($user['uploaded'] / $days)) : mksize($user['uploaded'])) . '</td>
        </tr>';
    }
}

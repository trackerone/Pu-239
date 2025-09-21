<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;

global $container, $user;

/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$imagesBaseurl = (string) $config->get('paths.images_baseurl');

$db = $container->get(Database::class);

$HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Gender') . "</td>
        <td>
            <img src='{$imagesBaseurl}" . htmlsafechars($user['gender']) . ".gif' alt='" . htmlsafechars($user['gender']) . "' title='" . htmlsafechars($user['gender']) . "'>
        </td>
    </tr>";

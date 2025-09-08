<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $site_config;

$db = $container->get(Database::class);

$HTMLOUT .= tr(_('Report User'), "
    <form method='post' action='{$site_config['paths']['baseurl']}/report.php?type=User&amp;id={$id}' enctype='multipart/form-data' accept-charset='utf-8'>
        <input type='submit' value='" . _('Report User') . "' class='button is-small'>" . _(' Click to Report this user for Breaking the rules.') . '
    </form>', 1);

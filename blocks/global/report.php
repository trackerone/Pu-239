<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Cache;
use Pu239\Database;

$user = check_user_status();
global $container, $site_config;

$db = $container->get(Database::class);
$cache = $container->get(Cache::class);
if ($site_config['alerts']['report'] && has_access($user['class'], UC_STAFF, 'coder')) {
    $delt_with = $cache->get('new_report_');
    if ($delt_with === false || is_null($delt_with)) {
        $delt_with = $db->fetchValue('SELECT COUNT(id) FROM reports WHERE delt_with = 0');
        $cache->set('new_report_', $delt_with, $site_config['expires']['alerts']);
    }
    if ($delt_with > 0) {
        $htmlout .= "
    <li>
        <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=reports&amp;action=reports'>
            <span class='button tag is-danger dt-tooltipper-small' data-tooltip-content='#reportmessage_tooltip'>
                " . _pfe('{0} New Report', '{0} New Reports', $delt_with) . "
            </span>
            <div class='tooltip_templates'>
                <div id='reportmessage_tooltip' class='margin20'>
                    <div class='size_6 has-text-centered has-text-danger has-text-weight-bold bottom10'>
                        " . _pfe('{0} New Report', '{0} New Reports', $delt_with) . "
                    </div>
                    <div class='has-text-centered'>
                        " . _pfe('Hey {1}!<br>{0} new report to be dealt with.', 'Hey {1}!<br>{0} new reports to be dealt with.', $delt_with, $user['username']) . '
                    </div>
                </div>
            </div>
        </a>
    </li>';
    }
}

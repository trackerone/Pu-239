<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$user = check_user_status();

$db = $container->get(Database::class);
$cache = $container->get(Cache::class);
$alertsEnabled = (bool) $config->get('alerts.staffmsg');
$alertsTtl = (int) $config->get('expires.alerts');
$baseurl = (string) $config->get('paths.baseurl');

if ($alertsEnabled && has_access($user['class'], UC_STAFF, 'coder')) {
    $answeredby = $cache->get('staff_mess_');
    if ($answeredby === false || is_null($answeredby)) {
        $answeredby = $db->fetchValue('SELECT COUNT(*) AS count FROM staffmessages WHERE answeredby = 0');
        $cache->set('staff_mess_', $answeredby, $alertsTtl);
    }
    if ($answeredby > 0) {
        $htmlout .= "
        <li>
            <a href='{$baseurl}/staffbox.php'>
                <span class='button tag is-warning dt-tooltipper-small' data-tooltip-content='#staffmessage_tooltip'>
                    " . _p('New Staff Message', 'New Staff Messages', $answeredby) . "
                </span>
                <div class='tooltip_templates'>
                    <div id='staffmessage_tooltip' class='margin20'>
                        <div class='size_6 has-text-centered has-text-warning has-text-weight-bold bottom10'>
                            " . _p('New Staff Message', 'New Staff Messages', $answeredby) . "
                        </div>
                        <div class='has-text-centered'>
                            " . _pfe('Hey {1}!<br>There is {0} new message for the staff.', 'Hey {1}!<br>There are {0} new messages for the staff.', $answeredby, $user['username']) . '
                        </div>
                    </div>
                </div>
            </a>
        </li>';
    }
}

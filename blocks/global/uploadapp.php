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
$alertsEnabled = (bool) $config->get('alerts.uploadapp');
$alertsTtl = (int) $config->get('expires.alerts');
$baseurl = (string) $config->get('paths.baseurl');

if ($alertsEnabled && has_access($user['class'], UC_STAFF, 'coder')) {
    $cache = $container->get(Cache::class);
    $newapp = $cache->get('new_uploadapp_');
    if ($newapp === false || is_null($newapp)) {
        $newapp = $db->fetchValue('SELECT COUNT(*) AS count FROM uploadapp WHERE status = ?', ['pending']);
        $cache->set('new_uploadapp_', $newapp, $alertsTtl);
    }
    if ($newapp > 0) {
        $htmlout .= "
    <li>
        <a href='{$baseurl}/staffpanel.php?tool=uploadapps&amp;action=app'>
            <span class='button tag is-info dt-tooltipper-small has-text-black' data-tooltip-content='#uploadapp_tooltip'>
                " . _p('New Uploader App', 'New Uploader Apps', $newapp) . "
            </span>
            <div class='tooltip_templates'>
                <div id='uploadapp_tooltip' class='margin20'>
                    <div class='size_6 has-text-centered has-text-danger has-text-weight-bold bottom10'>
                        " . _fe('Hey {0}!', $user['username']) . "
                    </div>
                    <div class='has-text-centered'>
                        " . _pfe('{0} uploader application to be dealt with.', '{0} uploader applications to be dealt with.', $newapp) . '
                    </div>
                </div>
            </div>
        </a>
    </li>';
    }
}

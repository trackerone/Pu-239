<?php
require_once __DIR__ . '/../../include/runtime_safe.php';

require_once __DIR__ . '/../../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;

$user = check_user_status();
global $container, $site_config;

if ($user) {
    $cache = $container->get(Cache::class);
    $lottery_info = $cache->get('lottery_info_');
    if ($lottery_info === false || is_null($lottery_info)) {
        $fluent = $container->get(Database::class);
        $lottery_info = $fluent->from('lottery_config')
                               ->fetchPairs('name', 'value');

        $cache->set('lottery_info_', $lottery_info, 86400);
    }

    if ($lottery_info['enable']) {
        $htmlout .= "
    <li>
        <a href='{$site_config['paths']['baseurl']}/lottery.php'>
            <span class='button tag is-success dt-tooltipper-large' data-tooltip-content='#lottery_tooltip'>
                " . _('Lottery in Progress') . "
            </span>
            <div class='tooltip_templates'>
                <div id='lottery_tooltip' class='margin20'>
                    <div>
                        <div class='size_6 has-text-centered has-text-success has-text-weight-bold bottom10'>
                            " . _('Lottery Info') . "
                        </div>
                        <div class='level-wide is-marginless'>
                            <div>" . _('Started at') . ': </div><div>' . get_date((int) $lottery_info['start_date'], 'LONG') . "</div>
                        </div>
                        <div class='level-wide is-marginless'>
                            <div class='right20'>" . _('Ends at') . ": </div><div class='left20'>" . get_date((int) $lottery_info['end_date'], 'LONG') . "</div>
                        </div>
                        <div class='level-wide is-marginless'>
                            <div>" . _('Remaining') . ': </div><div>' . mkprettytime($lottery_info['end_date'] - TIME_NOW) . '</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </li>';
    }
}

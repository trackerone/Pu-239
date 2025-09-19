<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Database;
use Psr\SimpleCache\CacheInterface as Cache;

global $container, $site_config;

$user = check_user_status();
if (empty($user)) {
    return '';
}

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Cache $cache */
$cache = $container->get(Cache::class);

$lottery = $cache->get('lottery_info_');
if ($lottery === null) {
    $rows = $db->fetchAll('SELECT name, value FROM lottery_config');
    $lottery = array_column($rows, 'value', 'name');
    $cache->set('lottery_info_', $lottery, 86400);
}

if (empty($lottery['enable'])) {
    return '';
}

$esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$baseurl = $esc($site_config['paths']['baseurl'] ?? '');
$start = isset($lottery['start_date']) ? (int) $lottery['start_date'] : 0;
$end = isset($lottery['end_date']) ? (int) $lottery['end_date'] : 0;
$remaining = $end > TIME_NOW ? mkprettytime($end - TIME_NOW) : _('Ended');

ob_start();
?>
<li>
    <a href="<?= $baseurl ?>/lottery.php">
        <span class="button tag is-success dt-tooltipper-large" data-tooltip-content="#lottery_tooltip">
            <?= $esc(_('Lottery in Progress')) ?>
        </span>
        <div class="tooltip_templates">
            <div id="lottery_tooltip" class="margin20">
                <div>
                    <div class="size_6 has-text-centered has-text-success has-text-weight-bold bottom10">
                        <?= $esc(_('Lottery Info')) ?>
                    </div>
                    <div class="level-wide is-marginless">
                        <div><?= $esc(_('Started at')) ?>: </div>
                        <div><?= $esc(get_date($start, 'LONG')) ?></div>
                    </div>
                    <div class="level-wide is-marginless">
                        <div class="right20"><?= $esc(_('Ends at')) ?>: </div>
                        <div class="left20"><?= $esc(get_date($end, 'LONG')) ?></div>
                    </div>
                    <div class="level-wide is-marginless">
                        <div><?= $esc(_('Remaining')) ?>: </div>
                        <div><?= $esc($remaining) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </a>
</li>
<?php

return (string) ob_get_clean();

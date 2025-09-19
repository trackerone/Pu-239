<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Database;
use Psr\SimpleCache\CacheInterface as Cache;

global $container, $site_config;

$user = check_user_status();
if (empty($user) || !$site_config['alerts']['bug'] || !has_access($user['class'], UC_STAFF, 'coder')) {
    return '';
}

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Cache $cache */
$cache = $container->get(Cache::class);

$cacheKey = 'bug_mess_';
$bugCount = $cache->get($cacheKey);
if ($bugCount === null) {
    $bugCount = (int) ($db->fetchValue(
        'SELECT COUNT(*) AS count FROM bugs WHERE status = :status',
        ['status' => 'na'],
    ) ?? 0);
    $cache->set($cacheKey, $bugCount, (int) ($site_config['expires']['alerts'] ?? 300));
}

if ($bugCount < 1) {
    return '';
}

$s = $s ?? static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$baseurl = $s($site_config['paths']['baseurl'] ?? '');
$username = $s($user['username'] ?? '');
$bugMessage = $s(_pfe('There is {0} new bug!', 'There are {0} new bugs!', $bugCount));

ob_start();
?>
<li>
    <a href="<?= $baseurl ?>/bugs.php?action=bugs">
        <span class="button tag is-warning dt-tooltipper-small" data-tooltip-content="#bugmessage_tooltip">
            <?= $s(_('Bug Alert Message')) ?>
        </span>
        <div class="tooltip_templates">
            <div id="bugmessage_tooltip" class="margin20">
                <div class="size_6 has-text-centered has-text-danger has-text-weight-bold bottom10">
                    <?= $s(_('New Bug Message')) ?>
                </div>
                <div class="has-text-centered">
                    <?= $s(_('New Bug Message')) ?> <?= $username ?>!<br> <?= $bugMessage ?>
                </div>
            </div>
        </div>
    </a>
</li>
<?php

return (string) ob_get_clean();

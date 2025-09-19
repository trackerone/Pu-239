<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Database;
use Psr\SimpleCache\CacheInterface as Cache;

global $container;

$user = check_user_status();
if (empty($user)) {
    return '';
}

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Cache $cache */
$cache = $container->get(Cache::class);

$event = $cache->get('site_events_');
if ($event === null) {
    $event = $db->fetch(
        'SELECT modifier, expires, setby, title FROM events WHERE expires > :now ORDER BY id DESC LIMIT 1',
        ['now' => TIME_NOW],
    );

    if (empty($event)) {
        $event = [
            'modifier' => 0,
            'expires' => 0,
            'setby' => 0,
            'title' => '',
        ];
    }

    $ttl = max(0, (int) ($event['expires'] ?? 0) - TIME_NOW);
    $cache->set('site_events_', $event, $ttl);
}

$modifier = (int) ($event['modifier'] ?? 0);
if ($modifier === 0 || (int) ($event['expires'] ?? 0) <= TIME_NOW) {
    return '';
}

$mode = match ($modifier) {
    1 => _('All Torrents Free'),
    2 => _('All Double Upload'),
    3 => _('All Torrents Free and Double Upload'),
    4 => _('All Torrents Silver'),
    default => '',
};

$esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$tooltipId = 'free_tooltip_' . $modifier;
$title = $esc((string) ($event['title'] ?? ''));
$setBy = format_username((int) ($event['setby'] ?? 0));
$expiry = (int) ($event['expires'] ?? 0);
$remaining = $expiry > 0 ? $esc(mkprettytime($expiry - TIME_NOW)) : '';
$until = $expiry > 0 ? $esc(get_date($expiry, 'DATE')) : '';
$setByLine = _fe('{0} set by {1}<br>', $title, $setBy);
$untilLine = ($expiry !== 1 && $expiry > 0) ? _fe('Until {0} ({1} to go).', $until, $remaining) : '';

ob_start();
?>
<li>
    <a href="#">
        <span class="button tag is-success dt-tooltipper-small" data-tooltip-content="#<?= $esc($tooltipId) ?>">
            <?= $esc(_('FreeLeech ON')) ?>
        </span>
        <div class="tooltip_templates">
            <div id="<?= $esc($tooltipId) ?>" class="margin20">
                <div class="size_6 has-text-centered has-text-info has-text-weight-bold bottom10">
                    <?= $esc($mode) ?>
                </div>
                <div class="has-text-centered">
                    <?= $setByLine ?>
                    <?php if ($untilLine !== '') { ?>
                        <?= $untilLine ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </a>
</li>
<?php

return (string) ob_get_clean();

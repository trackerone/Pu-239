<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Psr\SimpleCache\CacheInterface as Cache;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$getActiveEvent = static function (Database $db, Cache $cache): array {
    $event = $cache->get('site_events_details_');
    if ($event === null) {
        $event = $db->fetch(
            'SELECT modifier, begin, expires FROM events WHERE expires > :now ORDER BY id DESC LIMIT 1',
            ['now' => TIME_NOW],
        );

        if (empty($event)) {
            $event = [
                'modifier' => 0,
                'begin' => 0,
                'expires' => 0,
            ];
        }

        $ttl = max(0, (int) ($event['expires'] ?? 0) - TIME_NOW);
        $cache->set('site_events_details_', $event, $ttl);
    }

    return [
        'modifier' => (int) ($event['modifier'] ?? 0),
        'begin' => (int) ($event['begin'] ?? 0),
        'expires' => (int) ($event['expires'] ?? 0),
    ];
};

$getBonusProgress = static function (Cache $cache, Database $db, string $cacheKey, int $bonusId): array {
    $progress = $cache->get($cacheKey);
    if ($progress === null) {
        $progress = $db->fetch(
            'SELECT pointspool / points * 100 AS percent, enabled FROM bonus WHERE id = :id',
            ['id' => $bonusId],
        );

        if (empty($progress)) {
            $progress = [
                'percent' => 0.0,
                'enabled' => 'no',
            ];
        }

        $cache->set($cacheKey, $progress, 300);
    }

    return [
        'percent' => (float) ($progress['percent'] ?? 0),
        'enabled' => (string) ($progress['enabled'] ?? 'no'),
    ];
};

$getPercentClass = static function (float $percent): string {
    return match (true) {
        $percent >= 90 => 'is-success',
        $percent >= 80 => 'is-lightgreen',
        $percent >= 70 => 'is-jade',
        $percent >= 50 => 'is-turquoise',
        $percent >= 40 => 'has-text-lghtblue',
        $percent >= 30 => 'is-gold',
        $percent >= 20 => 'has-text-oragne',
        default => 'has-text-danger',
    };
};

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Cache $cache */
$cache = $container->get(Cache::class);

$event = $getActiveEvent($db, $cache);
$modifier = $event['modifier'];

$freeleechEnabled = $modifier === 1 || $modifier === 3;
$doubleUploadEnabled = $modifier === 2 || $modifier === 3;
$halfDownloadEnabled = $modifier === 4;

$freeleechWindow = $freeleechEnabled ? [$event['begin'], $event['expires']] : [0, 0];
$doubleUploadWindow = $doubleUploadEnabled ? [$event['begin'], $event['expires']] : [0, 0];
$halfDownloadWindow = $halfDownloadEnabled ? [$event['begin'], $event['expires']] : [0, 0];

$freeleech = $getBonusProgress($cache, $db, 'freeleech_alerts_', 11);
$doubleUpload = $getBonusProgress($cache, $db, 'doubleupload_alerts_', 12);
$halfDownload = $getBonusProgress($cache, $db, 'halfdownload_alerts_', 13);

if (
    $freeleech['enabled'] !== 'yes'
    && $doubleUpload['enabled'] !== 'yes'
    && $halfDownload['enabled'] !== 'yes'
) {
    return '';
}

$esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$baseurl = $esc((string) $config->get('paths.baseurl'));

$percentFl = number_format($freeleech['percent'], 2);
$percentDu = number_format($doubleUpload['percent'], 2);
$percentHd = number_format($halfDownload['percent'], 2);

$freeleechClass = $getPercentClass((float) $percentFl);
$doubleUploadClass = $getPercentClass((float) $percentDu);
$halfDownloadClass = $getPercentClass((float) $percentHd);

ob_start();
?>
<li>
    <a href="<?= $baseurl ?>/mybonus.php">
        <span class="button tag is-success dt-tooltipper-large" data-tooltip-content="#karma_tooltip">
            <?= $esc(_("Karma Contribution's")) ?>
        </span>
        <div class="tooltip_templates">
            <div id="karma_tooltip" class="margin20">
                <div class="size_6 has-text-centered has-text-success has-text-weight-bold bottom10">
                    <?= $esc(_("Karma Contribution's")) ?>
                </div>
                <?php if ($freeleech['enabled'] === 'yes') { ?>
                    <div class="level is-marginless">
                        <span><?= $esc(_('Freeleech')) ?></span>
                        <span class="left10">[
                            <?php if ($freeleechEnabled) { ?>
                                <span class="has-text-success"> <?= $esc(_('ON')) ?> </span>
                                <?= $esc(get_date($freeleechWindow[0], 'DATE')) ?> - <?= $esc(get_date($freeleechWindow[1], 'DATE')) ?>
                            <?php } else { ?>
                                <span class="<?= $esc($freeleechClass) ?>"> <?= $esc($percentFl) ?>%</span>
                            <?php } ?>
                        ]</span>
                    </div>
                <?php } ?>
                <?php if ($doubleUpload['enabled'] === 'yes') { ?>
                    <div class="level is-marginless">
                        <span><?= $esc(_('Doubleupload')) ?></span>
                        <span class="left10">[
                            <?php if ($doubleUploadEnabled) { ?>
                                <span class="has-text-success"> <?= $esc(_('ON')) ?> </span>
                                <?= $esc(get_date($doubleUploadWindow[0], 'DATE')) ?> - <?= $esc(get_date($doubleUploadWindow[1], 'DATE')) ?>
                            <?php } else { ?>
                                <span class="<?= $esc($doubleUploadClass) ?>"> <?= $esc($percentDu) ?>%</span>
                            <?php } ?>
                        ]</span>
                    </div>
                <?php } ?>
                <?php if ($halfDownload['enabled'] === 'yes') { ?>
                    <div class="level is-marginless">
                        <span><?= $esc(_('Half Download')) ?></span>
                        <span class="left10">[
                            <?php if ($halfDownloadEnabled) { ?>
                                <span class="has-text-success"> <?= $esc(_('ON')) ?> </span>
                                <?= $esc(get_date($halfDownloadWindow[0], 'DATE')) ?> - <?= $esc(get_date($halfDownloadWindow[1], 'DATE')) ?>
                            <?php } else { ?>
                                <span class="<?= $esc($halfDownloadClass) ?>"> <?= $esc($percentHd) ?>%</span>
                            <?php } ?>
                        ]</span>
                    </div>
                <?php } ?>
            </div>
        </div>
    </a>
</li>
<?php

return (string) ob_get_clean();

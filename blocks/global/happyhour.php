<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Config\ConfigRepository;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$user = check_user_status();
if (empty($user) || !(bool) $config->get('bonus.happy_hour')) {
    return '';
}

$happyFile = (string) $config->get('paths.happyhour');
if (!is_string($happyFile) || $happyFile === '' || !is_file($happyFile)) {
    return '';
}

try {
    $data = json_decode((string) file_get_contents($happyFile), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    return '';
}
$start = isset($data['time']) ? (int) strtotime((string) $data['time']) : 0;
$categoryId = (int) ($data['catid'] ?? 0);
$now = TIME_NOW;

if ($start <= 0 || $start >= $now || ($start + 3600) < $now) {
    return '';
}

$timeLeft = mkprettytime(($start + 3600) - $now);
[$minutes, $seconds] = array_pad(explode(':', $timeLeft), 2, '00');
$formattedTime = $minutes . ' min : ' . $seconds . ' sec';

$esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$baseurl = $esc((string) $config->get('paths.baseurl'));
$linkCategory = $categoryId === 255 ? '255' : (string) $categoryId;
$categoryUrl = $esc($linkCategory);
$categoryMessage = $categoryId === 255
    ? _('Every torrent downloaded in the happy hour is free')
    : _('Only in the selected Category, click on HappyHour above here to go to it');

ob_start();
?>
<li>
    <a href="<?= $baseurl ?>/browse.php?cat=<?= $categoryUrl ?>">
        <span class="button tag is-success dt-tooltipper-small" data-tooltip-content="#happyhour_tooltip">
            <?= $esc(_('HappyHour')) ?>
        </span>
        <div class="tooltip_templates">
            <div id="happyhour_tooltip" class="margin20">
                <div class="size_6 has-text-centered has-text-success has-text-weight-bold bottom10">
                    <?= $esc(_('HappyHour')) ?>
                </div>
                <div class="has-text-centered is-primary">
                    <?= $esc(_('Hey its now happy hour!')) ?><br>
                    <?= $esc($categoryMessage) ?><br>
                    <span class="has-text-danger"><b><?= $esc($formattedTime) ?></b></span>
                </div>
            </div>
        </div>
    </a>
</li>
<?php

return (string) ob_get_clean();

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Config\ConfigRepository;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$user = check_user_status();
if (empty($user) || (int) ($user['override_class'] ?? 255) === 255) {
    return '';
}

$esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$baseurl = $esc((string) $config->get('paths.baseurl'));

ob_start();
?>
<li>
    <a href="<?= $baseurl ?>/restoreclass.php">
        <span class="button tag is-warning dt-tooltipper-small" data-tooltip-content="#demotion_tooltip">
            <?= $esc(_('Temp. Demotion')) ?>
        </span>
        <div class="tooltip_templates">
            <div id="demotion_tooltip" class="margin20">
                <div class="size_6 has-text-centered has-text-warning has-text-weight-bold bottom10">
                    <?= $esc(_('Temporary Demotion')) ?>
                </div>
                <div class="has-text-centered">
                    <?= $esc(_('To reset your class, simply click here.')) ?>
                </div>
            </div>
        </div>
    </a>
</li>
<?php

return (string) ob_get_clean();

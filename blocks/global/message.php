<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Config\ConfigRepository;
use Pu239\Message;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$user = check_user_status();
if (empty($user) || !(bool) $config->get('alerts.message')) {
    return '';
}

/** @var Message $messages */
$messages = $container->get(Message::class);
$unread = (int) $messages->get_count($user['id'], (int) $config->get('pm.inbox'), true);

if ($unread < 1) {
    return '';
}

$esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$baseurl = $esc((string) $config->get('paths.baseurl'));
$unreadLabel = _pfe('{0} Unread PM', "{0} Unread PM's", $unread);
$unreadDetail = _pfe('You have {0} new message', 'You have {0} new messages', $unread);

ob_start();
?>
<li>
    <a href="<?= $baseurl ?>/messages.php">
        <span class="button tag is-info has-text-black dt-tooltipper-small" data-tooltip-content="#message_tooltip">
            <?= $esc($unreadLabel) ?>
        </span>
        <div class="tooltip_templates">
            <div id="message_tooltip" class="margin20">
                <div class="size_6 has-text-centered has-text-success has-text-weight-bold bottom10">
                    <?= $esc($unreadLabel) ?>
                </div>
                <div class="has-text-centered">
                    <?= $esc($unreadDetail) ?>
                </div>
            </div>
        </div>
    </a>
</li>
<?php

return (string) ob_get_clean();

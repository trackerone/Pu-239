<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Cache;
use Pu239\Message;

global $container, $site_config, $CURUSER;

// TODO(2025): csrf
$new_location = isset($_POST['boxx']) ? (int) $_POST['boxx'] : 0;
$set = [
    'location' => $new_location,
];
$messages_class = $container->get(Message::class);
$result = $messages_class->update($set, $pm_id);
if (!$result) {
    stderr(_('Error'), _('Message could not be moved!') . '<br><a class="is-link" href="' . $site_config['paths']['baseurl'] . '/messages.php?action=view_message&id=' . $pm_id . '>' . _('BACK') . '</a>');
}
$cache = $container->get(Cache::class);
$cache->delete('inbox_' . $CURUSER['id']);
header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&singlemove=1&box=' . $mailbox);
app_halt('Exit called');

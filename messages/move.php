<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Message;

global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

// TODO(2025): csrf
$new_location = isset($_POST['boxx']) ? (int) $_POST['boxx'] : 0;
$set = [
    'location' => $new_location,
];
$messages_class = $container->get(Message::class);
$result = $messages_class->update($set, $pm_id);
if (!$result) {
    stderr(_('Error'), _('Message could not be moved!') . '<br><a class="is-link" href="' . (string) $config->get('paths.baseurl') . '/messages.php?action=view_message&id=' . $pm_id . '>' . _('BACK') . '</a>');
}
$cache = $container->get(Cache::class);
$cache->delete('inbox_' . $CURUSER['id']);
header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&singlemove=1&box=' . $mailbox);
app_halt('Exit called');

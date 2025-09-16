<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Cache;
use Pu239\Message;

global $container, $site_config, $CURUSER;

// TODO(2025): csrf
$posted_pm = $_POST['pm'] ?? [];
if (empty($posted_pm)) {
    $referer = $_SERVER['HTTP_REFERER'] ?? $_SERVER['PHP_SELF'];
    header('Location: ' . $referer);
    app_halt('Exit called');
}
$pm_messages = is_array($posted_pm) ? $posted_pm : [$posted_pm];
$pm_messages = array_map(static fn ($value) => (int) $value, $pm_messages);
$messages_class = $container->get(Message::class);
$cache = $container->get(Cache::class);
if (isset($_POST['move'])) {
    $set = [
        'location' => isset($_POST['boxx']) ? (int) $_POST['boxx'] : 0,
    ];
    foreach ($pm_messages as $pm_message) {
        $messages_class->update($set, $pm_message);
    }
    $cache->delete('inbox_' . $CURUSER['id']);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&multi_move=1&box=' . $mailbox);
    app_halt('Exit called');
}
if (isset($_POST['delete'])) {
    foreach ($pm_messages as $id) {
        $id = (int) $id;
        $message = $messages_class->get_by_id($id);
        if ($message['receiver'] == $CURUSER['id'] && $message['urgent'] === 'yes' && $message['unread'] === 'yes') {
            stderr(_('Error'), _('You MUST read this message before you delete it!!!') . ' <a class="is-link" href="' . $site_config['paths']['baseurl'] . '/messages.php?action=view_message&id=' . $pm_id . '">' . _('BACK') . '</a>' . _(' to message.') . '');
        }
        if (($message['receiver'] == $CURUSER['id'] || $message['sender'] == $CURUSER['id']) && $message['location'] == $site_config['pm']['deleted']) {
            $result = $messages_class->delete($id, $CURUSER['id']);
        } elseif ($message['receiver'] == $CURUSER['id']) {
            $set = [
                'location' => 0,
                'unread' => 'no',
            ];
            $result = $messages_class->update($set, $id);
            $cache->decrement('inbox_' . $CURUSER['id']);
        } elseif ($message['sender'] == $CURUSER['id'] && $message['location'] != $site_config['pm']['deleted']) {
            $set = [
                'saved' => 'no',
            ];
            $result = $messages_class->update($set, $id);
        }
    }

    if (!$result) {
        stderr(_('Error'), _("Messages couldn't be deleted!"));
    }
    $return_action = isset($_POST['returnto']) ? preg_replace('/[^a-z_]/i', '', (string) $_POST['returnto']) : '';
    if (isset($_POST['returnto'])) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=' . $return_action . '&multi_delete=1');
    } elseif (isset($_POST['draft_section'])) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=viewdrafts&multi_delete=1');
    } else {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&multi_delete=1&box=' . $mailbox);
    }
    app_halt('Exit called');
}

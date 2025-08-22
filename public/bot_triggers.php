<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

use Pu239\BotReplies;
use Pu239\BotTriggers;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;
use Rakit\Validation\Validator;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_bitbucket.php';
$user = check_user_status();

global $container, $site_config;

$cache = $container->get(Cache::class);
$fluent = $container->get(Database::class);
$trigger_class = $container->get(BotTriggers::class);
$replies_class = $container->get(BotReplies::class);
$editable = false;
if (has_access($user['class'], UC_ADMINISTRATOR, 'coder')) {
    $stdfoot['js'][] = 'editable';
    $editable = true;
}

$unapproved = $approved = false;
$colspan = 4;
if (has_access($user['class'], UC_ADMINISTRATOR, 'coder')) {
    $colspan = 5;
}
if (has_access($user['class'], UC_ADMINISTRATOR, 'coder') && isset($_GET['action']) && $_GET['action'] === 'unapproved') {
    $unapproved = true;
}
if (has_access($user['class'], UC_ADMINISTRATOR, 'coder') && isset($_GET['action']) && $_GET['action'] === 'approved') {
    $approved = true;
}
$data = array_merge($_POST, $_GET);
if (has_access($user['class'], UC_ADMINISTRATOR, 'coder') && !empty($data)) {
    $valid_actions = [
        'view_replies',
        'edit_trigger',
        'update_trigger',
        'edit_reply',
        'update_reply',
        'add_trigger',
        'add_reply',
        'delete_trigger',
        'delete_reply',
        'approved',
        'unapproved',
    ];
    if (!in_array($data['action'], $valid_actions)) {
        unset($data, $_POST, $_GET);
    }
    $validator = $container->get(Validator::class);
    $session = $container->get(Session::class);
    $data['phraseid'] = isset($data['id']) ? decrypt($data['id'], $site_config['salt']['one']) : '';
    $validation = $validator->validate($data, [
        'add_trigger' => 'regex:/^[\p{L}\p{M}\p{N}\p{Z}\p{P}]+$/u',
        'update_trigger' => 'regex:/^[\p{L}\p{M}\p{N}\p{Z}\p{P}]+$/u',
        'add_reply' => 'regex:/^[\p{L}\p{M}\p{N}\p{Z}\p{P}]+$/u',
        'update_reply' => 'regex:/^[\p{L}\p{M}\p{N}\p{Z}\p{P}]+$/u',
        'phraseid' => 'integer',
        'reply_id' => 'integer',
        'action' => 'required|alpha_dash',
    ]);
    if (!$validation->fails()) {
        $add_trigger = isset($data['add_trigger']) ? htmlsafechars($data['add_trigger']) : '';
        $update_trigger = isset($data['update_trigger']) ? htmlsafechars($data['update_trigger']) : '';
        $add_reply = isset($data['add_reply']) ? htmlsafechars($data['add_reply']) : '';
        $update_reply = isset($data['update_reply']) ? htmlsafechars($data['update_reply']) : '';
        $phraseid = isset($data['phraseid']) ? (int) $data['phraseid'] : 0;
        $reply_id = isset($data['reply_id']) ? (int) $data['reply_id'] : 0;
        $action = isset($data['action']) ? htmlsafechars($data['action']) : '';
    } else {
        $errors = $validation->errors()
                             ->firstOfAll();
        foreach ($errors as $key => $value) {
            stderr(_('Error'), "$key : $value");
        }
    }
    if (!empty($add_trigger)) {
        $values = [
            'phrase' => $add_trigger,
            'userid' => $user['id'],
        ];
        if ($trigger_class->insert($values)) {
            $session->set('is-success', _fe('Trigger: {0} added successfully.', $add_trigger));
        } else {
            $session->set('is-warning', _fe('Trigger: {0} failed to be added.', $add_trigger));
        }
    } elseif (!empty($update_trigger)) {
        $values = [
            'phrase' => $update_trigger,
            'userid' => $user['id'],
        ];
        if ($trigger_class->update($values, $phraseid)) {
            $session->set('is-success', _fe('Trigger: {0} updated successfully.', $add_trigger));
        } else {
            $session->set('is-warning', _fe('Trigger: {0} failed to be updated.', $add_trigger));
        }
    } elseif (!empty($add_reply)) {
        $values = [
            'phraseid' => $phraseid,
            'reply' => $add_reply,
            'userid' => $user['id'],
        ];
        if ($replies_class->insert($values)) {
            $session->set('is-success', _fe('Reply: {0} added successfully.', $add_reply));
        } else {
            $session->set('is-warning', _fe('Reply: {0} failed to be added.', $add_reply));
        }
    } elseif (!empty($update_reply)) {
        $values = [
            'reply' => $update_reply,
            'userid' => $user['id'],
        ];
        if ($replies_class->update($values, $reply_id)) {
            $session->set('is-success', _fe('Reply: {0} updated successfully.', $add_reply));
        } else {
            $session->set('is-warning', _fe('Reply: {0} failed to be updated.', $add_reply));
        }
    } elseif (!empty($approve_trigger)) {
        $update = [
            'approved_by' => $user['id'],
        ];
        if ($trigger_class->update($update, $approve_trigger)) {
            $session->set('is-success', _('Trigger Approved.'));
        } else {
            $session->set('is-warning', _('Trigger Approval Failed.'));
        }
    } elseif (!empty($approve_reply)) {
        $update = [
            'approved_by' => $user['id'],
        ];
        if ($replies_class->update($update, $approve_reply)) {
            $session->set('is-success', _('Reply Approved.'));
        } else {
            $session->set('is-warning', _('Reply Approval Failed.'));
        }
    } elseif ($action === 'delete_trigger' && !empty($phraseid)) {
        if ($trigger_class->delete($phraseid)) {
            $session->set('is-success', _fe('Trigger: #{0} was deleted.', $phraseid));
        } else {
            $session->set('is-success', _fe('Trigger: #{0} was [i]NOT[/] deleted.', $phraseid));
        }
    } elseif ($action === 'delete_reply' && !empty($reply_id)) {
        if ($replies_class->delete($reply_id)) {
            $session->set('is-success', _fe('Reply: #{0} was deleted.', $reply_id));
        } else {
            $session->set('is-success', _fe('Reply: #{0} was [i]NOT[/] deleted.', $phraseid));
            $session->set('is-warning', _fe('Reply #{0} was [i]NOT[/i] deleted.', $reply_id));
        }
    } elseif ($action === 'view_replies') {
        $replies = $replies_class->get_replies();
    } elseif ($action === 'add_reply') {
        $trigger = $trigger_class->get_by_id($phraseid);
        $form = main_div("
			<form method='post' action='{$_SERVER['PHP_SELF']}?action=add_reply' enctype='multipart/form-data' accept-charset='utf-8'>
				<div class='has-text-centered padding20'>" . _('BBCode and emoticons are allowed.') . "</div>
				<div class='has-text-centered padding20 w-75'>
				    <div class='has-text-left'><span class='padding20'>" . _('Add response for') . ":</span> <blockquote class='padding20 bg-00 round10'>$trigger</blockquote></div>
				</div>
				<div class='padding20 level-center-center'>
				    <input type='text' name='add_reply' class='w-50 right5'>
				    <input type='hidden' name='id' value='{$data['id']}'>
				    <input type='submit' value='" . _('Add New Reply') . "' class='button is-small left5'>
				</div>
			</form>", 'has-text-centered');
    } elseif ($action === 'edit_trigger') {
        $trigger = $trigger_class->get_by_id($phraseid);
        $form = main_div("
			<form method='post' action='{$_SERVER['PHP_SELF']}?action=edit_trigger' enctype='multipart/form-data' accept-charset='utf-8'>
				<div class='has-text-centered padding20'>" . _('BBCode and emoticons are allowed.') . "</div>
				<div class='has-text-centered padding20 w-75'>" . _('Edit Trigger') . ":</div>
				<div class='padding20 level-center-center'>
				    <input type='text' name='update_trigger' class='w-50 right5' value='{$trigger}'>
				    <input type='hidden' name='id' value='{$data['id']}'>
				    <input type='submit' value='" . _('Edit Trigger') . "' class='button is-small left5'>
				</div>
			</form>", 'has-text-centered');
    } elseif ($action === 'edit_reply') {
        $reply = $replies_class->get_by_id($reply_id);
        $form = main_div("
			<form method='post' action='{$_SERVER['PHP_SELF']}?action=edit_reply' enctype='multipart/form-data' accept-charset='utf-8'>
				<div class='has-text-centered padding20'>" . _('BBCode and emoticons are allowed.') . "</div>
				<div class='has-text-centered padding20 w-75'>" . _('Edit Reply') . ":</div>
				<div class='padding20 level-center-center'>
				    <input type='text' name='update_reply' class='w-50 right5' value='{$reply}'>
				    <input type='hidden' name='reply_id' value='{$data['reply_id']}'>
				    <input type='hidden' name='id' value='{$data['id']}'>
				    <input type='submit' value='" . _('Edit Reply') . "' class='button is-small left5'>
				</div>
			</form>", 'has-text-centered');
    }
}
$HTMLOUT = "
        <h1 class='has-text-centered'>" . _('Bot Triggers') . '</h1>';
if (has_access($user['class'], UC_ADMINISTRATOR, 'coder')) {
    if ($approved) {
        $links = [
            "<a class='is-link tooltipper' title='" . _('Show All Triggers') . "' href='{$_SERVER['PHP_SELF']}'>" . _('Show All') . '</a>',
            "<a class='is-link tooltipper' title='" . _('Show Unapproved Triggers') . "' href='{$_SERVER['PHP_SELF']}?action=unapproved'>" . _('Show Unapproved') . '</a>',
        ];
    } elseif ($unapproved) {
        $links = [
            "<a class='is-link tooltipper' title='" . _('Show All Triggers') . "' href='{$_SERVER['PHP_SELF']}'>" . _('Show All') . '</a>',
            "<a class='is-link tooltipper' title='" . _('Show Unapproved Triggers') . "' href='{$_SERVER['PHP_SELF']}?action=approved'>" . _('Show Approved') . '</a>',
        ];
    } else {
        $links = [
            "<a class='is-link tooltipper' title='" . _('Show Approved Triggers') . "' href='{$_SERVER['PHP_SELF']}?action=approved'>" . _('Show Approved') . '</a>',
            "<a class='is-link tooltipper' title='" . _('Show Unapproved Triggers') . "' href='{$_SERVER['PHP_SELF']}?action=unapproved'>" . _('Show Unapproved') . '</a>',
        ];
    }

    $HTMLOUT .= "
            <div class='bottom20'>
                <ul class='level-center bg-06'>";
    foreach ($links as $link) {
        $HTMLOUT .= "
			        <li class='margin10'>
			            $link
			        </li>";
    }
    $HTMLOUT .= '
			    </ul>
			</div>';
}
if (!isset($form)) {
    $HTMLOUT .= main_div("
			<form method='post' action='{$_SERVER['PHP_SELF']}?action=add_trigger' enctype='multipart/form-data' accept-charset='utf-8'>
				<div class='has-text-centered padding20'>" . _('BBCode and emoticons are allowed.') . "</div>
				<div class='padding20 level-center-center'>
				    <input type='text' name='add_trigger' class='w-50 right5'>
				    <input type='submit' value='" . _('Add New Trigger') . "' class='button is-small left5'>
				</div>
			</form>", 'has-text-centered');
} else {
    $HTMLOUT .= $form;
}

if ($unapproved) {
    $triggers = $trigger_class->get_unapproved();
} else {
    $triggers = $trigger_class->getall();
}

$heading = '
					<tr>
						<th>' . _('Trigger Phrase') . '<br><small>' . _('For the bot to respond, the users text must include each word from one of the phases below.<br>Click the trigger to view or add responses.') . "</small></th>
						<th class='has-text-centered'>" . _('Added By') . "</th>
						<th class='has-text-centered'>" . _('Approved By') . '</th>' . (has_access($user['class'], UC_ADMINISTRATOR, 'coder') ? "
						<th class='has-text-centered'>" . _('Tools') . '</th>' : '') . "
						<th class='has-text-centered'>" . _('Add Reply') . '</th>
					</tr>';

$body = '';
if (empty($triggers)) {
    $HTMLOUT .= stdmsg(_('Error'), _('No bot triggers'), 'top20');
} else {
    foreach ($triggers as $trigger) {
        $approved = '';
        if ($trigger['approved_by'] > 0) {
            $approved = format_username($trigger['approved_by']);
        } elseif (has_access($user['class'], UC_ADMINISTRATOR, 'coder') && $user['id'] != $trigger['userid']) {
            $approved = "
				<form method='post' action='{$_SERVER['PHP_SELF']}' enctype='multipart/form-data' accept-charset='utf-8'>
					<label for='approve_trigger'>" . _('Approve') . "</label>
					<input name='approve_trigger' id='approve_trigger' type='checkbox' value='{$trigger['id']}' onChange='this.form.submit()'>
				</form>";
        } else {
            $approved = _("You can't approve your trigger.");
        }
        $inner_heading = '
                    <tr>
                        <th>' . _('Reply Phrase') . '<br><small>' . _('The bot will respond to the trigger phrase by selecting one of these responses, at random.') . "</small></th>
                        <th class='has-text-centered'>" . _('Added By') . "</th>
                        <th class='has-text-centered'>" . _('Approved By') . '</th>' . (has_access($user['class'], UC_ADMINISTRATOR, 'coder') ? "
						<th class='has-text-centered'>" . _('Tools') . '</th>' : '') . '
                    </tr>';
        $inner_body = $each = '';
        if (!empty($replies)) {
            foreach ($replies as $eaches) {
                if ($eaches['phraseid'] != $trigger['id']) {
                    continue;
                }
                $each_checkbox = "
                    <form method='post' action='{$_SERVER['PHP_SELF']}' enctype='multipart/form-data' accept-charset='utf-8'>
                        <label for='approve_reply'>" . _('Approve') . "</label>
                        <input name='approve_reply' id='approve_reply' type='checkbox' value='{$eaches['id']}' onChange='this.form.submit()'>
                    </form>";

                $each_approved = '';
                if ($eaches['approved_by'] > 0) {
                    $each_approved = format_username($eaches['approved_by']);
                } elseif (has_access($user['class'], UC_ADMINISTRATOR, 'coder') && $user['id'] != $eaches['userid']) {
                    $each_approved = $each_checkbox;
                } else {
                    $each_approved = _("You can't approve your reply.");
                }
                $post_id = encrypt($eaches['id'], $site_config['salt']['one']);
                $current_text = trim($eaches['reply']);
                $inner_body .= "
                    <tr id='reply_{$eaches['id']}'>
                        <td>
                            <span id='{$post_id}'>$current_text</span>
                        </td>
                        <td class='has-text-centered'>" . format_username($eaches['userid']) . "</td>
                        <td class='has-text-centered'>$each_approved</td>" . (has_access($user['class'], UC_ADMINISTRATOR, 'coder') ? "
                        <td class='has-text-centered'>
                            <i class='icon-trash-empty icon has-text-danger'></i>
                        </td>" : '') . '
                    </tr>';
            }
            $each = "
							<span class='panel spoiler' style='display:none; clear: both; width: 100%; margin-top: 20px;'>
                                <span>" . main_table($inner_body, $inner_heading) . '</span>
                            </span>';
        }
        $post_id = encrypt($trigger['id'], $site_config['salt']['one']);
        $body .= "
					<tr>
						<td>					
						    <a href='{$_SERVER['PHP_SELF']}?action=view_replies&amp;id={$post_id}' class='tooltipper' title='" . _('View Replies') . "s'>{$trigger['phrase']}</a>							
						</td>
						<td class='has-text-centered'>" . format_username($trigger['userid']) . "</td>
						<td class='has-text-centered'>$approved</td>" . (has_access($user['class'], UC_ADMINISTRATOR, 'coder') ? "
						<td class='has-text-centered'>
						    <a href='{$_SERVER['PHP_SELF']}?action=edit_trigger&amp;id={$post_id}'>
                                <i class='icon-edit icon has-text-info tooltipper' aria-hidden='true' title='" . _('Edit Bot Trigger') . "'></i>
                            </a>
                            <a href='{$_SERVER['PHP_SELF']}?action=delete_trigger&amp;id={$post_id}'>
                                <i class='icon-trash-empty icon has-text-danger tooltipper' aria-hidden='true' title='" . _('Delete Bot Trigger and all Replies') . "'></i>
                            </a>
                        </td>" : '') . "
						<td class='has-text-centered'>
						    <a href='{$_SERVER['PHP_SELF']}?action=add_reply&amp;id={$post_id}' class='button is-small'>" . _('Add Reply') . '</a>
                        </td>
					</tr>';
        if (!empty($replies)) {
            $inner_heading = '
                        <tr>
                            <th>' . _('Reply Phrase') . '<br><small>' . _('The bot will respond to the trigger phrase by selecting one of these responses, at random.') . '</small></th>
                            <th>' . _('Added By') . '</th>
                            <th>' . _('Approved By') . '</th>
                            <th>' . _('Tools') . '</th>
                        </tr>';
            $inner_body = '';
            foreach ($replies as $reply) {
                if ($reply['phraseid'] != $trigger['id'] || $reply['phraseid'] != $phraseid) {
                    continue;
                }
                $approve = $reply['userid'] != $user['id'] ? "
                                <form method='post' action='{$_SERVER['PHP_SELF']}'>
                                    <input type='hidden' name='id' value='{$post_id}'>
                                    <input type='submit' name='approve_reply' value='" . _('Approve') . "'>
                                </form>" : _("You can't approve your replies!.");
                $inner_body .= "
                        <tr>
                            <td>{$reply['reply']}</td>
                            <td class='has-text-centered'>" . format_username($reply['userid']) . "</td>
                            <td class='has-text-centered'>" . (!empty($reply['approved_by']) ? format_username($reply['approved_by']) : $approve) . '</td>' . (has_access($user['class'], UC_ADMINISTRATOR, 'coder') ? "
						    <td class='has-text-centered'>
						        <a href='{$_SERVER['PHP_SELF']}?action=edit_reply&amp;reply_id={$reply['id']}&amp;id={$post_id}'>
                                    <i class='icon-edit icon has-text-info tooltipper' aria-hidden='true' title='" . _('Edit Reply') . "'></i>
                                </a>
                                <a href='{$_SERVER['PHP_SELF']}?action=delete_reply&amp;reply_id={$reply['id']}&amp;id={$post_id}'>
                                    <i class='icon-trash-empty icon has-text-danger tooltipper' aria-hidden='true' title='" . _('Delete Reply') . "'></i>
                                </a>
                            </td>" : '') . '
                        </tr>';
            }
            if (!empty($inner_body)) {
                $body .= "<tr><td colspan='$colspan'>" . main_table($inner_body, $inner_heading, null, null, 'table-striped', null, false) . '</td></tr>';
            }
        }
    }
    $HTMLOUT .= main_table($body, $heading, '', 'top20');
}

$title = _('Bot Triggers');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

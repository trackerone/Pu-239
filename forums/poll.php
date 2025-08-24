<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
if (!is_valid_id($topic_id)) {
    stderr(_('Error'), _('Invalid ID.'));
}

/**
 * @param $vote
 *
 * @return bool
 */
function is_valid_poll_vote($vote)
{
    return is_numeric($vote) && ($vote >= 0) && (floor($vote) == $vote);
}

$success = 0; //=== used for errors
//=== lets do that action 2 thing \\o\o/o//
$posted_action = strip_tags((isset($_GET['action_2']) ? $_GET['action_2'] : (isset($_POST['action_2']) ? $_POST['action_2'] : '')));
//=== add all possible actions here and check them to be sure they are ok
$valid_actions = [
    'poll_vote',
    'poll_add',
    'poll_delete',
    'poll_reset',
    'poll_close',
    'poll_open',
    'poll_edit',
    'reset_vote',
];
//=== check posted action, and if no match, kill it
$action = in_array($posted_action, $valid_actions) ? $posted_action : 1;
if ($action == 1) {
    stderr(_('Error'), _('Invalid action'));
}
//=== casting a vote(s) ===========================================================================================//
global $CURUSER, $site_config;

switch ($action) {
    case 'poll_vote':
        //=== Get poll info
        $res_poll = $db->run(');
            //=== all went well, send them back!
            header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
            app_halt('Exit called');
        } else {
            //=== if single vote (not array)
            if (is_valid_poll_vote($post_vote)) {
                $db->run('INSERT INTO forum_poll_votes (`poll_id`, `user_id`, `options`, `added`) VALUES (' . sqlesc($arr_poll['poll_id']) . ', ' . sqlesc($CURUSER['id']) . ', ' . sqlesc($post_vote) . ', ' . $added . ')') or sqlerr(__FILE__, __LINE__);
                $success = 1;
            } else {
                foreach ($post_vote as $votes) {
                    $vote = 0 + $votes;
                    if (is_valid_poll_vote($vote)) {
                        $db->run('INSERT INTO forum_poll_votes (`poll_id`, `user_id`, `options`, `added`) VALUES (' . sqlesc($arr_poll['poll_id']) . ', ' . sqlesc($CURUSER['id']) . ', ' . sqlesc($vote) . ', ' . $added . ')') or sqlerr(__FILE__, __LINE__);
                        $success = 1;
                    }
                }
            }
            //=== did it work?
            if ($success != 1) {
                stderr(_('Error'), _('Something went wrong, the poll was not %s!', 'counted') . '<a href="forums.php?action=view_topic&amp;topic_id=' . $topic_id . '" class="is-link">' . _('Back To Topic') . '</a>.');
            }
            //=== all went well, send them back!
            header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
            app_halt('Exit called');
        } //=== end of else
        break; //=== end casting a vote(s)
    //=== resetting vote ============================================================================================//

    case 'reset_vote':
        //=== Get poll info
        $res_poll = $db->run(');
        break;
    //=== adding a poll ============================================================================================//

    case 'poll_add':
        //=== be sure there is no poll yet :P
        $res_poll = $db->run(');
            $poll_id = ((is_null($___mysqli_res = mysqli_insert_id($mysqli))) ? false : $___mysqli_res);
            if (is_valid_id((int) $poll_id)) {
                $db->run(');
        } //=== end of posting poll to DB
        //=== ok looks like they can be here
        //=== options for amount of options lol
        for ($i = 2; $i < 21; ++$i) {
            $options .= '<option class="body" value="' . $i . '">' . $i . ' options</option>';
        }
        $HTMLOUT .= '<table class="main">
	<tr>
		<td class="embedded">
		<h1>' . _('Add poll in') . ' "<a class="is-link" href="forums.php?action=view_topic&amp;topic_id=' . $topic_id . '">' . htmlsafechars($arr_poll['topic_name']) . '</a>"</h1>
	<form action="forums.php?action=poll" method="post" name="poll" accept-charset="utf-8">
		<input type="hidden" name="topic_id" value="' . $topic_id . '">
		<input type="hidden" name="action_2" value="poll_add">
		<input type="hidden" name="add_the_poll" value="1">
	<table>
	<tr>
		<td colspan="3"><span style="color: white; font-weight: bold;"><img src="' . $site_config['paths']['images_baseurl'] . 'forums/poll.gif" alt="' . _('Poll') . '" title="' . _('Poll') . '" style="vertical-align: middle;"> ' . _('Add poll to topic') . '!</span></td>
	</tr>
	<tr>
		<td><img src="' . $site_config['paths']['images_baseurl'] . 'forums/question.png" alt="' . _('Question') . '" title="' . _('Question') . '" width="24" style="vertical-align: middle;"></td>
		<td><span style="white - space:nowrap;font-weight: bold;">' . _('Poll question') . ':</span></td>
		<td><input type="text" name="poll_question" class="w-100" value=""></td>
	</tr>
	<tr>
		<td><img src="' . $site_config['paths']['images_baseurl'] . 'forums/options.gif" alt="' . _('Options') . '" title="' . _('Options') . '" width="24" style="vertical-align: middle;"></td>
		<td><span style="white - space:nowrap;font-weight: bold;">' . _('Poll answers') . ':</span></td>
		<td><textarea cols="30" rows="4" name="poll_answers" class="text_area_small"></textarea>
		<br> ' . _('One option per line. There is a minimum of 2 options, and a maximun of 20 options. BBcode is enabled.') . '</td>
	</tr>
	<tr>
		<td><img src="' . $site_config['paths']['images_baseurl'] . 'forums/clock.png" alt="' . _('Clock') . '" title="' . _('Clock') . '" width="30" style="vertical-align: middle;"></td>
		<td><span style="white - space:nowrap;font-weight: bold;">' . _('Poll starts') . ':</span></td>
		<td><select name="poll_starts">
											<option class="body" value="0">' . _('Start Now') . '!</option>
											<option class="body" value="1">' . _pfe('in {0} day', 'in {0} days', 1) . '</option>
											<option class="body" value="2">' . _pfe('in {0} day', 'in {0} days', 2) . '</option>
											<option class="body" value="3">' . _pfe('in {0} day', 'in {0} days', 3) . '</option>
											<option class="body" value="4">' . _pfe('in {0} day', 'in {0} days', 4) . '</option>
											<option class="body" value="5">' . _pfe('in {0} day', 'in {0} days', 5) . '</option>
											<option class="body" value="6">' . _pfe('in {0} day', 'in {0} days', 6) . '</option>
											<option class="body" value="7">' . _pfe('in {0} week', 'in {0} weeks', 1) . '</option>
											</select> ' . _("When to start the poll. Default is 'Start Now'") . '!" </td>
	</tr>
	<tr>
		<td><img src="' . $site_config['paths']['images_baseurl'] . 'forums/stop.png" alt = "' . _('Stop') . '" title="' . _('Stop') . '" width="20" style="vertical-align: middle;"></td>
		<td><span style="white-space:nowrap;font-weight: bold;">' . _('Poll ends') . ':</span></td>
		<td><select name = "poll_ends">
											<option class="body" value = "1356048000">' . _('Run Forever') . '</option>
											<option class="body" value = "1">' . _pfe('in {0} day', 'in {0} days', 1) . '</option>
											<option class="body" value = "2">' . _pfe('in {0} day', 'in {0} days', 2) . '</option>
											<option class="body" value = "3">' . _pfe('in {0} day', 'in {0} days', 3) . '</option>
											<option class="body" value = "4">' . _pfe('in {0} day', 'in {0} days', 4) . '</option>
											<option class="body" value = "5">' . _pfe('in {0} day', 'in {0} days', 5) . '</option>
											<option class="body" value = "6">' . _pfe('in {0} day', 'in {0} days', 6) . '</option>
											<option class="body" value = "7">' . _pfe('in {0} week', 'in {0} weeks', 1) . '</option>
											<option class="body" value = "14">' . _pfe('in {0} week', 'in {0} weeks', 2) . '</option>
											<option class="body" value = "21">' . _pfe('in {0} week', 'in {0} weeks', 3) . '</option>
											<option class="body" value = "28">' . _pfe('in {0} month', 'in {0} months', 1) . '</option>
											<option class="body" value = "56">' . _pfe('in {0} month', 'in {0} months', 2) . '</option>
											<option class="body" value = "84">' . _pfe('in {0} month', 'in {0} months', 3) . '</option>
											</select>' . _("How long this poll should run? Default is to 'Run Forever'") . '"</td>
	</tr>
	<tr>
		<td><img src="' . $site_config['paths']['images_baseurl'] . 'forums/multi.gif" alt = "' . _('Multi') . '" title="' . _('Multi') . '" width="20" style="vertical-align: middle;"></td>
		<td><span style="white-space:nowrap;font-weight: bold;">' . _('Multi options') . ':</span></td>
		<td><select name = "multi_options">
											<option class="body" value = "1">' . _('Single option') . '!</option>' . $options . '
											</select>' . _('Allow members to have more then one selection') . ' ? ' . _('Default is') . ' "' . _('Single option') . '!" </td>
	</tr>
	<tr>
		<td></td>
		<td><span style="white-space:nowrap;font-weight: bold;">' . _('Change vote') . ':</span></td>
		<td><input name = "change_vote" value = "yes" type = "radio"' . ($change_vote === 'yes' ? ' checked = "checked"' : '') . '>Yes
													<input name = "change_vote" value = "no" type = "radio"' . ($change_vote === 'no' ? ' checked = "checked"' : '') . '>No   <br>' . _('Allow members to change their vote') . ' ? ' . _('Default is') . ' "no" </td>
	</tr>
	<tr>
		<td colspan="3">
		<input type = "submit" name = "button" class="button" value = "' . _('Add Poll') . '!"></td>
	</tr>
	</table></form><br></td>
	</tr>
	</table>';
        $HTMLOUT .= $the_bottom_of_the_page;
        break; //=== end add poll
    //=== deleting a poll ============================================================================================//

    case 'poll_delete':
        if ($CURUSER['class'] < UC_STAFF) {
            stderr(_('Error'), _('Wherein [art thou] good, but to taste sack and drink it? Wherein neat and cleanly, but to carve a capon and eat it? Wherein cunning, but in craft? Wherein crafty but in villainy? Wherein villainous, but in all things? Wherein worthy but in nothing?'));
        }
        //=== be sure there is a poll to delete :P
        $res_poll = $db->run(');
        }
        //=== all went well, send them back!
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic & topic_id=' . $topic_id);
        app_halt('Exit called');
        break; //=== end delete poll
    //=== reseting a poll ============================================================================================//

    case 'poll_reset':
        if ($CURUSER['class'] < UC_STAFF) {
            stderr(_('Error'), _('Thou hath more hair than wit, and more faults than hairs, and more wealth than faults.'));
        }
        //=== be sure there is a poll to reset :P
        $res_poll = $db->run(');
        }
        //=== all went well, send them back!
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic & topic_id=' . $topic_id);
        app_halt('Exit called');
        break; //=== end reset poll
    //=== closing a poll ============================================================================================//

    case 'poll_close':
        if ($CURUSER['class'] < UC_STAFF) {
            stderr(_('Error'), _("A weasel hath not such a deal of spleen as you are toss'd with."));
        }
        //=== be sure there is a poll to close :P
        $res_poll = $db->run(');
        }
        //=== all went well, send them back!
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
        app_halt('Exit called');
        break; //=== end of poll close
    //=== opening a poll  (either after it was closed, or timed out) ===============================================================================//

    case
    'poll_open':
        if ($CURUSER['class'] < UC_STAFF) {
            stderr(_('Error'), _('Thou bootless toad-spotted ratsbane!'));
        }
        //=== be sure there is a poll to open :P
        $res_poll = $db->run(');
        }
        //=== all went well, send them back!
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
        app_halt('Exit called');
        break; //=== end of open poll
    //=== edit a poll ============================================================================================//

    case 'poll_edit':
        if ($CURUSER['class'] < UC_STAFF) {
            stderr(_('Error'), _('Confusion now hath made his masterpiece!'));
        }
        //=== be sure there is a poll to edit :P
        $res_poll = $db->run(');
        } //=== end of posting poll to DB
        //=== get poll stuff to edit
        $res_edit = $db->run(');
} //=== end switch all actions

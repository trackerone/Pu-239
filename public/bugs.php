<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Delight\Auth\AuthError;
use Delight\Auth\NotLoggedInException;
use DI\DependencyException;
use DI\NotFoundException;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;
use Pu239\User;
use Spatie\Image\Exceptions\InvalidManipulation;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_bbcode.php';
$curuser = check_user_status();
$HTMLOUT = '';
global $container, $site_config;

$possible_actions = [
    'viewbug',
    'bugs',
    'add',
];
$action = isset($_GET['action']) ? htmlsafechars($_GET['action']) : (isset($_POST['action']) ? htmlsafechars($_POST['action']) : 'bugs');
if (!in_array($action, $possible_actions)) {
    stderr(_('Error'), _('Invalid action.'));
}
$dt = TIME_NOW;
$fluent = $container->get(Database::class);
$messages_class = $container->get(Message::class);
$user_class = $container->get(User::class);
$cache = $container->get(Cache::class);
$session = $container->get(Session::class);
if ($action === 'viewbug') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!has_access($curuser['class'], UC_MAX, 'coder')) {
            stderr(_('Error'), _('Only site-coders can do this!'));
        }
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $status = isset($_POST['status']) ? htmlsafechars($_POST['status']) : '';
        $comment = !empty($_POST['comment']) ? htmlsafechars($_POST['comment']) : '';
        if (!$id || !is_valid_id($id)) {
            stderr(_('Error'), _('Invalid ID'));
        }
        $bug = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

    $na_count = $fluent->from('bugs')
                       ->select(null)
                       ->select('COUNT(id) AS count')
                       ->where('status = "na"')
                       ->fetch('count');

    if ($count > 0) {
        $HTMLOUT .= $count > $perpage ? $pager['pagertop'] : '';
        $HTMLOUT .= "
        <h1 class='has-text-centered'>" . _pfe('There is {0} new bug. Please check it.', 'There is {0} new bugs. Please check them.', $na_count) . "</h1>
        <div class='has-text-centered size_3'>" . _('All solved bugs will be deleted after 30 days (from added date).') . '</div>';
        $heading = '        
    <tr>
        <th>' . _('Title') . '</th>
        <th>' . _('Added / By') . '</th>
        <th>' . _('Priority') . '</th>
        <th>' . _('Status') . '</th>
        <th>' . _('Coder') . '</th>
        <th>' . _('Staff Comment') . '</th>
    </tr>';
        $body = '';
        foreach ($bugs as $bug) {
            switch ($bug['priority']) {
                case 'low':
                    $priority = "<span class='has-text-green'>" . _('Low') . '</span>';
                    break;

                case 'high':
                    $priority = "<span class='has-text-danger'>" . _('High') . '</span>';
                    break;

                case 'veryhigh':
                    $priority = "<span class='has-text-danger'><b><u>" . _('Very High') . '</u></b></span>';
                    break;
            }
            switch ($bug['status']) {
                case 'fixed':
                    $status = "<span class='has-text-green'><b>" . _('Fixed') . '</b></span>';
                    break;

                case 'ignored':
                    $status = "<span class='has-text-orange'><b>" . _('Ignored') . '</b></span>';
                    break;

                default:
                    $status = "<span class='has-text-gold'><b>N/A</b></span>";
                    break;
            }
            $body .= "
    <tr>
        <td class='w-25 min-150'><a href='?action=viewbug&amp;id=" . $bug['id'] . "'>" . format_comment($bug['title']) . '</a></td>
        <td>' . get_date($bug['added'], 'TINY') . '<br>' . format_username($bug['sender']) . "</td>
        <td>{$priority}</td>
        <td>{$status}</td>
        <td>" . ($bug['status'] != 'na' ? format_username($bug['staff']) : '---') . "</td>
        <td class='w-25 min-350'>" . (!empty($bug['comment']) ? format_comment($bug['comment']) : '---') . '</td>
    </tr>';
        }
        $HTMLOUT .= main_table($body, $heading);
        $HTMLOUT .= $count > $perpage ? $pager['pagerbottom'] : '';
    } else {
        $session->set('is-warning', _('There are no reported bugs :).'));
        header('Location: ' . $site_config['paths']['baseurl']);
        app_halt('Exit called');
    }
} elseif ($action === 'add') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = htmlsafechars($_POST['title']);
        $priority = htmlsafechars($_POST['priority']);
        $problem = htmlsafechars($_POST['problem']);
        if (empty($title) || empty($priority) || empty($problem)) {
            stderr(_('Error'), _('You missing something?<br>Please try again.'));
        }
        if (strlen($problem) < 20) {
            stderr(_('Error'), _("We can't use a problem text there is less then 20 chars."));
        }
        if (strlen($title) < 5) {
            stderr(_('Error'), _("We can't use a title there is less then 5 chars."));
        }
        $values = [
            'title' => $title,
            'priority' => $priority,
            'problem' => $problem,
            'sender' => $curuser['id'],
            'added' => $dt,
        ];
        $result = // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
        $cache->delete('bug_mess_');

        if ($result) {
            send_staff_message($values, (int) $result);
            stderr(_('Success'), _fe('Your bug has been sent to our coders.<br>You have choosen priority: {0}', $priority));
        } else {
            stderr(_('Error'), _('Please try again.'));
        }
    }
    $HTMLOUT .= "
    <form method='post' action='{$_SERVER['PHP_SELF']}?action=add' enctype='multipart/form-data' accept-charset='utf-8'>";
    $body = "
        <tr>
            <td class='rowhead'>" . _('Title') . ":</td>
            <td><input type='text' name='title' class='w-100'><br>" . _('Please choose a proper title.') . "</td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Problem (Bug)') . ":</td>
            <td><textarea class='w-100' rows='10' name='problem'></textarea><br>" . _('Describe the problem as completely as possible. Please include the entire error stack, not just part of it.') . "</td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Priority') . ":</td>
            <td>
                <select name='priority' required>
                    <option value='0'>" . _('Select one') . "</option>
                    <option value='low'>" . _('Low') . "</option>
                    <option value='high'>" . _('High') . "</option>
                    <option value='veryhigh'>" . _('Very High') . '</option>
                </select>
                <br>' . _('Choose only very high if the bug really is a problem for using the site.') . "
            </td>
        </tr>
        <tr>
            <td colspan='2' class='has-text-centered'><input type='submit' value='" . _('Send this bug!') . "' class='button is-small'></td>
        </tr>";
    $HTMLOUT .= main_table($body) . '
    </form>';
}

/**
 * @param array $values
 * @param int   $bug_id
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws AuthError
 * @throws NotLoggedInException
 * @throws \Envms\FluentPDO\Exception
 * @throws UnbegunTransaction
 * @throws \PHPMailer\PHPMailer\Exception
 * @throws InvalidManipulation
 */
function send_staff_message(array $values, int $bug_id)
{
    global $container, $site_config;

    $messages_class = $container->get(Message::class);
    $user_class = $container->get(User::class);
    $user = $user_class->getUserFromId($values['sender']);
    $link = _('Posted By') . ": [url={$site_config['paths']['baseurl']}/userdetails.php?id={$values['sender']}]{$user['username']}[/url]";
    $subject = _('New Bug Report');
    $msg = "[url={$site_config['paths']['baseurl']}/bugs.php?action=viewbug&id={$bug_id}][h1]{$values['title']}[/h1][/url][code]{$values['problem']}[/code]\n{$link}";
    foreach ($site_config['is_staff'] as $key => $userid) {
        $msgs_buffer[] = [
            'receiver' => $userid,
            'added' => TIME_NOW,
            'msg' => $msg,
            'subject' => $subject,
        ];
    }
    if (!empty($msgs_buffer)) {
        $messages_class->insert($msgs_buffer);
    }
}

$title = _('Bugs');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

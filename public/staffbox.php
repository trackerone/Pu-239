<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_html.php';
$user = check_user_status();
global $container;
$db = $container->get(Database::class);, $site_config;

$dt = TIME_NOW;
$session = $container->get(Session::class);
if (!has_access($user['class'], UC_STAFF, 'coder')) {
    $session->set('is-danger', _("You can't use this!"));
    header('Location: ' . $site_config['paths']['baseurl']);
    app_halt('Exit called');
}
$valid_do = [
    'view',
    'delete',
    'setanswered',
    'restart',
    '',
];
$do = isset($_GET['do']) && in_array($_GET['do'], $valid_do) ? $_GET['do'] : (isset($_POST['do']) && in_array($_POST['do'], $valid_do) ? $_POST['do'] : '');
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) && is_array($_POST['id']) ? array_map('intval', $_POST['id']) : 0);
$message = isset($_POST['message']) && !empty($_POST['message']) ? htmlsafechars($_POST['message']) : '';
$subject = isset($_POST['subject']) && !empty($_POST['subject']) ? htmlsafechars($_POST['subject']) : '';
$reply = isset($_POST['reply']) && $_POST['reply'] == 1 ? true : false;
$HTMLOUT = '';
$cache = $container->get(Cache::class);
switch ($do) {
    case 'delete':
        if ($id > 0) {
            if ($db->run(');
                header('Refresh: 2; url=' . $_SERVER['PHP_SELF']);
                $session->set('is-success', _('The messege(s) you selected were deleted!'));
                header("Location: {$_SERVER['PHP_SELF']}");
                app_halt('Exit called');
            } else {
                $session->set('is-warning', _('There was an error with the query please contact the staff!'));
                header("Location: {$_SERVER['PHP_SELF']}");
                app_halt('Exit called');
            }
        } else {
            $session->set('is-warning', _('Something was wrong, I have no idea what!'));
            header("Location: {$_SERVER['PHP_SELF']}");
            app_halt('Exit called');
        }
        break;

    case 'setanswered':
        if ($id > 0) {
            if ($reply && empty($message)) {
                $session->set('is-warning', _("You didn't write any message for the user!"));
                header("Location: {$_SERVER['PHP_SELF']}");
                app_halt('Exit called');
            }
            $q1 = $db->run(');
                $session->set('is-success', _('The messege(s) you selected were set as answered!'));
                header("Location: {$_SERVER['PHP_SELF']}");
                app_halt('Exit called');
            } else {
                $session->set('is-warning', _('There was an error with the query please contact the staff!'));
                header("Location: {$_SERVER['PHP_SELF']}");
                app_halt('Exit called');
            }
        } else {
            $session->set('is-warning', _('Something was wrong, I have no idea what!'));
            header("Location: {$_SERVER['PHP_SELF']}");
            app_halt('Exit called');
        }
        break;

    case 'view':
        if ($id > 0) {
            $q2 = $db->run(');
                $breadcrumbs = [
                    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                ];
                echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
            } else {
                $session->set('is-warning', _('There is message with this id'));
                header("Location: {$_SERVER['PHP_SELF']}");
                app_halt('Exit called');
            }
        } else {
            $session->set('is-warning', _('Something was wrong, I have no idea what!'));
            header("Location: {$_SERVER['PHP_SELF']}");
            app_halt('Exit called');
        }
        break;

    case 'restart':
        if ($id > 0) {
            if ($db->run(");
                app_halt('Exit called');
            } else {
                $session->set('is-warning', _('There was an error with the query please contact the staff!'));
                header("Location: {$_SERVER['PHP_SELF']}");
                app_halt('Exit called');
            }
        } else {
            $session->set('is-warning', _('Something was wrong, I have no idea what!'));
            header("Location: {$_SERVER['PHP_SELF']}");
            app_halt('Exit called');
        }
        break;

    default:
        $fluent = $db; // alias
$fluent = $container->get(Database::class);
        $count_msgs = $fluent->from('staffmessages')
                             ->select(null)
                             ->select('COUNT(id) AS count')
                             ->fetch('count');

        $perpage = 15;
        $pager = pager($perpage, $count_msgs, 'staffbox.php?');
        if (!$count_msgs) {
            $session->set('is-warning', _('There are no messages for the staff'));
            header('Location: ' . $site_config['paths']['baseurl']);
            app_halt('Exit called');
        } else {
            $HTMLOUT .= "
                    <h1 class='has-text-centered'>" . _('Staff Box - messages sent by users') . "</h1>
                    <form method='post' name='staffbox' action='{$_SERVER['PHP_SELF']}' enctype='multipart/form-data' accept-charset='utf-8'>";
            $HTMLOUT .= $count_msgs > $perpage ? $pager['pagertop'] : '';
            $head = '
                        <tr>
                            <th>' . _('Subject') . '</th>
                            <th>' . _('Sender') . '</th>
                            <th>' . _('Added') . '</th>
                            <th>' . _('Answered') . "</th>
                            <th><input type='checkbox' id='checkThemAll'></th>
                        </tr>";
            $r = $db->run(');
        $breadcrumbs = [
            "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
        ];
        echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
}

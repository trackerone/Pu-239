<?php
declare(strict_types=1);

use Pu239\Database;
use Pu239\Message;
use Pu239\Session;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once CLASS_DIR . 'class_check.php';

global $container, $site_config, $CURUSER;

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Session $session */
$session = $container->get(Session::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$stdhead = [
    'css' => [get_file_name('sceditor_css')],
];
$stdfoot = [
    'js' => [get_file_name('sceditor_js')],
];

$HTMLOUT = '';
$errors = [];
$last_user_class = UC_STAFF - 1; // highest non-staff class
$dt = TIME_NOW;
$sent2classes = [];

/**
 * Collect class names we send to (for info/logging if needed)
 */
function classes2name(int $min, int $max): void
{
    global $sent2classes;
    for ($i = $min; $i <= $max; ++$i) {
        $sent2classes[] = get_user_class_name((int) $i);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groups  = isset($_POST['groups']) && is_array($_POST['groups']) ? $_POST['groups'] : [];
    $subject = isset($_POST['subject']) ? trim((string) $_POST['subject']) : '';
    // Get raw body (allow BBCode), but normalize &amp; -> & as original code intended
    $msg_raw = isset($_POST['body']) ? (string) $_POST['body'] : '';
    $msg = str_replace('&amp', '&', $msg_raw);

    $sender = (isset($_POST['system']) && $_POST['system'] === 'yes') ? 2 : (int) $CURUSER['id'];

    if ($subject === '') {
        $errors[] = _("Your message doesn't have a subject");
    }
    if ($msg === '') {
        $errors[] = _('There is not any text in your message!');
    }
    if (empty($groups)) {
        $errors[] = _('You have to select a group to send your message');
    }

    if (empty($errors)) {
        $where = [];
        $params = [];
        $recipient_ids = [];

        foreach ($groups as $g) {
            if (is_string($g)) {
                switch ($g) {
                    case 'all_staff':
                        $where[] = '(u.class BETWEEN :staff_min AND :staff_max)';
                        $params[':staff_min'] = (int) UC_STAFF;
                        $params[':staff_max'] = (int) UC_MAX;
                        classes2name(UC_STAFF, UC_MAX);
                        break;

                    case 'all_users':
                        $where[] = '(u.class BETWEEN :user_min AND :user_max)';
                        $params[':user_min'] = (int) UC_MIN;
                        $params[':user_max'] = (int) UC_MAX;
                        classes2name(UC_MIN, UC_MAX);
                        break;

                    case 'fls':
                        $where[] = "(u.support = 'yes')";
                        $sent2classes[] = _('First line support');
                        break;

                    case 'donor':
                        $where[] = "(u.donor = 'yes')";
                        $sent2classes[] = _('Donors');
                        break;

                    case 'all_friends':
                        // Fetch confirmed friends of current user
                        $friendRows = $db->fetchAll(
                            "SELECT f.friendid AS id
                               FROM friends AS f
                              WHERE f.userid = :uid AND f.confirmed = 'yes'",
                            [':uid' => (int) $CURUSER['id']]
                        );
                        foreach ($friendRows as $fr) {
                            $recipient_ids[] = (int) $fr['id'];
                        }
                        break;
                }
            }

            // Numeric class selected
            if (is_numeric($g)) {
                $key = ':c' . $g;
                $where[] = "(u.class = $key)";
                $params[$key] = (int) $g;
                $sent2classes[] = get_user_class_name((int) $g);
            }
        }

        // Build recipients from WHERE
        if (!empty($where)) {
            $ids = $db->fetchAll('SELECT u.id FROM users AS u WHERE ' . implode(' OR ', $where), $params);
            foreach ($ids as $r) {
                $recipient_ids[] = (int) $r['id'];
            }
        }

        // Unique, remove sender if included
        $recipient_ids = array_values(array_unique(array_diff($recipient_ids, [$sender])));

        if (empty($recipient_ids)) {
            $errors[] = _('There are not any users in the groups you selected!');
        } else {
            /** @var Message $messages_class */
            $messages_class = $container->get(Message::class);

            $buffer = [];
            foreach ($recipient_ids as $rid) {
                $buffer[] = [
                    'receiver' => (int) $rid,
                    'added'    => $dt,
                    'msg'      => $msg,
                    'subject'  => $subject,
                ];
            }

            // Batch-insert messages
            if (!empty($buffer)) {
                $messages_class->insert($buffer);
            }

            $session->set('is-success', _fe('Message sent to {0} recipient(s)!', count($recipient_ids)));
        }
    }
}

// Group definitions for dropdown
$groups = [];
$groups['staff'] = [
    'opname'   => _('Site Staff'),
    'minclass' => UC_MIN,
    'ops'      => [],
];
for ($i = UC_STAFF; $i <= UC_MAX; ++$i) {
    $groups['staff']['ops'][$i] = get_user_class_name((int) $i);
}
$groups['staff']['ops']['fls'] = _('First line support');
$groups['staff']['ops']['all_staff'] = _('All staff');

$groups['members'] = [
    'opname'   => _('Members Groups'),
    'minclass' => UC_STAFF,
    'ops'      => [],
];
for ($i = UC_MIN; $i <= $last_user_class; ++$i) {
    $groups['members']['ops'][$i] = get_user_class_name((int) $i);
}
$groups['members']['ops']['donor'] = _('Donors');
$groups['members']['ops']['all_users'] = _('All users');

$groups['friends'] = [
    'opname'   => _('Related to you'),
    'minclass' => UC_MIN,
    'ops'      => ['all_friends' => _('Your friends')],
];

/**
 * Build groups <select multiple>
 */
function dropdown(): string
{
    global $CURUSER, $groups;

    $r = '<select multiple="multiple" name="groups[]" size="16">';
    foreach ($groups as $group) {
        if ($group['minclass'] >= $CURUSER['class']) {
            continue;
        }
        $r .= '<optgroup label="' . $group['opname'] . '">';
        foreach ($group['ops'] as $k => $v) {
            $r .= '<option value="' . (string) $k . '">' . (string) $v . '</option>';
        }
        $r .= '</optgroup>';
    }
    $r .= '</select>';
    return $r;
}

// Flash errors (or success)
if (!empty($errors)) {
    foreach ($errors as $error) {
        $session->set('is-warning', $error);
    }
}

// Render form
$HTMLOUT .= "
    <h1 class='has-text-centered'>" . _('Group message') . "</h1>
    <form action='staffpanel.php?tool=grouppm&amp;action=grouppm' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
      <table class='table table-bordered table-striped'>
        <tr>
          <td colspan='2'>" . _('Subject') . "
            <input type='text' name='subject' class='w-100'></td>
        </tr>
        <tr>
          <td>" . _('Body') . "</td>
          <td>" . _('Groups') . "</td>
        </tr>
        <tr>
          <td class='is-paddingless'>" . BBcode() . "</td>
          <td>" . dropdown() . "</td>
        </tr>
      </table>
        <div class='has-text-centered margin20'>
            <label for='sys'>" . _('Send as System') . "</label>
            <input id='sys' type='checkbox' name='system' value='yes' class=''>
            <input type='submit' value='" . _('Send!') . "' class='button is-small left20'>
        </div>
    </form>";

$title = _('Group PM');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];

echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);

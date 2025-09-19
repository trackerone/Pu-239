<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Database;
use Pu239\Message;
use Pu239\User;


global $container, $site_config, $CURUSER;

/** @var Database $db */
$db = $container->get(Database::class);
/** @var User $users_class */
$users_class = $container->get(User::class);
/** @var Message $messages_class */
$messages_class = $container->get(Message::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$stdhead = [
    'css' => [
        get_file_name('sceditor_css'),
    ],
];

$stdfoot = [
    'js' => [
        get_file_name('mass_bonus_js'),
        get_file_name('sceditor_js'),
    ],
];

$dt = TIME_NOW;
$h1_thingie = $HTMLOUT = '';
$good_stuff = [
    'upload_credit',
    'karma',
    'freeslots',
    'invite',
    'pm',
];

// ------ helpers ------

/**
 * Fetch recipient user IDs by selected classes (enabled users only).
 *
 * @param array<int> $class_ids
 * @return int[]
 */
function get_recipient_ids(Database $db, array $class_ids): array
{
    if (empty($class_ids)) {
        return [];
    }
    $class_ids = array_values(array_unique(array_map('intval', $class_ids)));
    $ph = [];
    $params = [];
    foreach ($class_ids as $i => $c) {
        $k = ":c$i";
        $ph[] = $k;
        $params[$k] = $c;
    }
    $rows = $db->fetchAll(
        'SELECT id FROM users WHERE status = 0 AND class IN (' . implode(',', $ph) . ')',
        $params
    );
    return array_map(static fn($r) => (int) $r['id'], $rows);
}

/**
 * Send a system PM to a list of user IDs.
 *
 * @param int[] $user_ids
 */
function mass_pm(Message $messages_class, array $user_ids, string $subject, string $body, int $added): void
{
    if (empty($user_ids)) {
        return;
    }
    $batch = [];
    foreach ($user_ids as $uid) {
        $batch[] = [
            'receiver' => (int) $uid,
            'added'    => $added,
            'msg'      => $body,
            'subject'  => $subject,
        ];
    }
    if (!empty($batch)) {
        $messages_class->insert($batch);
    }
}

// ------ controller ------

$action = !empty($_POST['bonus_options_1']) && in_array($_POST['bonus_options_1'], $good_stuff, true)
    ? (string) $_POST['bonus_options_1']
    : '';

$class_ids = !empty($_POST['free_for_classes']) && is_array($_POST['free_for_classes'])
    ? array_map('intval', $_POST['free_for_classes'])
    : [];

if (empty($class_ids)) {
    $action = '';
    $_POST = [];
}

switch ($action) {
    case 'upload_credit': {
        $GB = isset($_POST['GB']) ? (int) $_POST['GB'] : 0; // value in bytes from <select>
        if ($GB < 1073741824 || $GB > 53687091200) { // 1 GB .. 50 GB (bytes)
            stderr(_('Error'), _('You forgot to select an amount!'));
        }

        $ids = get_recipient_ids($db, $class_ids);
        if (empty($ids)) {
            stderr(_('Info'), _('No enabled users matched the selected classes.'));
        }

        // Add to users.uploaded
        $db->run(
            'UPDATE users SET uploaded = uploaded + :bytes WHERE status = 0 AND class IN (' . implode(',', array_fill(0, count($class_ids), '?')) . ')',
            array_merge([':bytes' => $GB], $class_ids)
        );

        // Notify
        $gb_amount = $GB / 1073741824;
        $subject = _('Upload Credit Awarded');
        $body = _fe('You have been awarded {0} GB upload credit by staff.', (int) $gb_amount);
        mass_pm($messages_class, $ids, $subject, $body, $dt);

        header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=mass_bonus_for_members&action=mass_bonus_for_members&GB=1');
        app_halt('Exit called');
        break;
    }

    case 'karma': {
        $karma_points = isset($_POST['karma']) ? (int) $_POST['karma'] : 0;
        if ($karma_points < 100 || $karma_points > 5000) {
            stderr(_('Error'), _('You forgot to select an amount!'));
        }

        $ids = get_recipient_ids($db, $class_ids);
        if (empty($ids)) {
            stderr(_('Info'), _('No enabled users matched the selected classes.'));
        }

        // NOTE: adjust column name if different in your schema (e.g., "seedbonus" or "karma")
        $db->run(
            'UPDATE users SET seedbonus = seedbonus + :pts WHERE status = 0 AND class IN (' . implode(',', array_fill(0, count($class_ids), '?')) . ')',
            array_merge([':pts' => $karma_points], $class_ids)
        );

        $subject = _('Karma Points Awarded');
        $body = _fe('You have been awarded {0} Karma Bonus Points by staff.', $karma_points);
        mass_pm($messages_class, $ids, $subject, $body, $dt);

        header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=mass_bonus_for_members&action=mass_bonus_for_members&karma=1');
        app_halt('Exit called');
        break;
    }

    case 'freeslots': {
        $freeslots = isset($_POST['freeslots']) ? (int) $_POST['freeslots'] : 0;
        if ($freeslots < 1 || $freeslots > 50) {
            stderr(_('Error'), _('You forgot to select an amount!'));
        }

        $ids = get_recipient_ids($db, $class_ids);
        if (empty($ids)) {
            stderr(_('Info'), _('No enabled users matched the selected classes.'));
        }

        $db->run(
            'UPDATE users SET freeslots = freeslots + :num WHERE status = 0 AND class IN (' . implode(',', array_fill(0, count($class_ids), '?')) . ')',
            array_merge([':num' => $freeslots], $class_ids)
        );

        $subject = _('Freeleech Slot(s) Awarded');
        $body = _fe('You have been awarded {0} freeleech slot(s) by staff.', $freeslots);
        mass_pm($messages_class, $ids, $subject, $body, $dt);

        header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=mass_bonus_for_members&action=mass_bonus_for_members&freeslots=1');
        app_halt('Exit called');
        break;
    }

    case 'invite': {
        $invites = isset($_POST['invites']) ? (int) $_POST['invites'] : 0;
        if ($invites < 1 || $invites > 50) {
            stderr(_('Error'), _('You forgot to select an amount!'));
        }

        $ids = get_recipient_ids($db, $class_ids);
        if (empty($ids)) {
            stderr(_('Info'), _('No enabled users matched the selected classes.'));
        }

        $db->run(
            'UPDATE users SET invites = invites + :num WHERE status = 0 AND class IN (' . implode(',', array_fill(0, count($class_ids), '?')) . ')',
            array_merge([':num' => $invites], $class_ids)
        );

        $subject = _('Invite(s) Awarded');
        $body = _fe('You have been awarded {0} invite(s) by staff.', $invites);
        mass_pm($messages_class, $ids, $subject, $body, $dt);

        header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=mass_bonus_for_members&action=mass_bonus_for_members&invites=1');
        app_halt('Exit called');
        break;
    }

    case 'pm': {
        $subject = isset($_POST['subject']) ? trim((string) $_POST['subject']) : '';
        $body = isset($_POST['body']) ? (string) $_POST['body'] : '';
        if ($subject === '') {
            stderr(_('Error'), _('No subject text... Please enter something to send!'));
        }
        if ($body === '') {
            stderr(_('Error'), _('No body text... Please enter something to send!'));
        }

        $ids = get_recipient_ids($db, $class_ids);
        if (empty($ids)) {
            stderr(_('Info'), _('No enabled users matched the selected classes.'));
        }

        mass_pm($messages_class, $ids, $subject, $body, $dt);

        header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=mass_bonus_for_members&action=mass_bonus_for_members&pm=1');
        app_halt('Exit called');
        break;
    }
}

// ---------------- UI ----------------

$all_classes_check_boxes = '
    <div class="level-center">';
for ($i = UC_MIN; $i <= UC_MAX; ++$i) {
    $all_classes_check_boxes .= '
        <div>
            <input type="checkbox" name="free_for_classes[]" value="' . $i . '" checked>
            <span style="font-weight: bold;color: #' . get_user_class_color($i) . ';">' . get_user_class_name($i) . '</span>
        </div>';
}
$all_classes_check_boxes .= '
    </div>';

$bonus_GB = '<select name="GB">
        <option class="head" value="">' . _('Add Upload Credit') . '</option>
        <option class="body" value="1073741824">' . _fe('{0} GB', 1) . '</option>
        <option class="body" value="2147483648">' . _fe('{0} GB', 2) . '</option>
        <option class="body" value="3221225472">' . _fe('{0} GB', 3) . '</option>
        <option class="body" value="4294967296">' . _fe('{0} GB', 4) . '</option>
        <option class="body" value="5368709120">' . _fe('{0} GB', 5) . '</option>
        <option class="body" value="6442450944">' . _fe('{0} GB', 6) . '</option>
        <option class="body" value="7516192768">' . _fe('{0} GB', 7) . '</option>
        <option class="body" value="8589934592">' . _fe('{0} GB', 8) . '</option>
        <option class="body" value="9663676416">' . _fe('{0} GB', 9) . '</option>
        <option class="body" value="10737418240">' . _fe('{0} GB', 10) . '</option>
        <option class="body" value="16106127360">' . _fe('{0} GB', 15) . '</option>
        <option class="body" value="21474836480">' . _fe('{0} GB', 20) . '</option>
        <option class="body" value="26843545600">' . _fe('{0} GB', 25) . '</option>
        <option class="body" value="32212254720">' . _fe('{0} GB', 30) . '</option>
        <option class="body" value="53687091200">' . _fe('{0} GB', 50) . '</option>
        </select>' . _('select amount of bonus GB to add to members upload credit.') . ' ';

$karma_drop_down = '
        <select name="karma">
        <option class="head" value="">' . _('Add Karma Bonus Points') . '</option>';
$i = 100;
while ($i <= 5000) {
    $karma_drop_down .= '<option class="body" value="' . $i . '">' . $i . ' ' . _('Karma Points') . '</option>';
    $i = ($i < 1000 ? $i + 100 : $i + 500);
}
$karma_drop_down .= '</select> ' . _('select amount of Karma Bonus Points to add.') . ' ';

$free_leech_slot_drop_down = '
        <select name="freeslots">
        <option class="head" value="">' . _('Add freeslots') . '</option>';
$i = 1;
while ($i <= 50) {
    $free_leech_slot_drop_down .= '<option class="body" value="' . $i . '">' . $i . ' ' . _('freeslot') . ($i !== 1 ? 's' : '') . '</option>';
    $i = ($i < 10 ? $i + 1 : $i + 5);
}
$free_leech_slot_drop_down .= '</select>' . _('select amount of freeslots to add.') . ' ';

$invites_drop_down = '
        <select name="invites">
        <option class="head" value="">' . _('Add Invites') . '</option>';
$i = 1;
while ($i <= 50) {
    $invites_drop_down .= '<option class="body" value="' . $i . '">' . $i . ' ' . _('Invite') . ($i !== 1 ? 's' : '') . '</option>';
    $i = ($i < 10 ? $i + 1 : $i + 5);
}
$invites_drop_down .= '</select>' . _('select amount of invites to add.') . '';

$subject = isset($_POST['subject']) ? htmlsafechars((string) $_POST['subject']) : _('Mass PM');
$body = isset($_POST['body']) ? htmlsafechars((string) $_POST['body']) : _('Your text here');
$pm_drop_down = '
                <table class="w-100">
                    <tr>
                        <td colspan="2">' . _('Send message') . '</td>
                    </tr>
                    <tr>
                        <td><span class="has-text-weight-bold">' . _('Subject:') . '</span></td>
                        <td>
                            <input type="hidden" name="pm" value="pm">
                            <input name="subject" type="text" class="w-100" value="' . $subject . '">
                        </td>
                    </tr>
                    <tr>
                        <td><span class="has-text-weight-bold">' . _('Body:') . '</span></td>
                        <td class="is-paddingless">' . BBcode($body, '', 300) . '</td>
                    </tr>
                </table>';

$drop_down = '
        <select name="bonus_options_1" id="bonus_options_1">
        <option value="">' . _('Select Bonus Type') . '</option>
        <option value="upload_credit">' . _('Upload Credit') . '</option>
        <option value="karma">' . _('Karma Points') . '</option>
        <option value="freeslots">' . _('Free Leech Slots') . '</option>
        <option value="invite">' . _('Invites') . '</option>
        <option value="pm">' . _('PM') . '</option>
        <option value="">' . _('Reset bonus type') . '</option>
        </select>';

// Success headers
$h1_thingie .= (isset($_GET['GB']) && (int) $_GET['GB'] === 1) ? '<h2>' . _('Bonus GB added to selected member classes') . '</h2>' : '';
$h1_thingie .= (isset($_GET['karma']) && (int) $_GET['karma'] === 1) ? '<h2>' . _('Bonus Karma added to selected member classes') . '</h2>' : '';
$h1_thingie .= (isset($_GET['freeslots']) && (int) $_GET['freeslots'] === 1) ? '<h2>' . _('Bonus Free Leech Slots added to selected member classes') . '</h2>' : '';
$h1_thingie .= (isset($_GET['invites']) && (int) $_GET['invites'] === 1) ? '<h2>' . _('Bonus invites added to selected member classes') . '</h2>' : '';
$h1_thingie .= (isset($_GET['pm']) && (int) $_GET['pm'] === 1) ? '<h2>' . _('Mass pm sent to selected member classes') . '</h2>' : '';

$HTMLOUT .= '<h1 class="has-text-centered">' . $site_config['site']['name'] . ' ' . _('Mass Bonus') . '</h1>' . $h1_thingie;
$HTMLOUT .= '
    <form name="inputform" method="post" action="' . $_SERVER['PHP_SELF'] . '?tool=mass_bonus_for_members&amp;action=mass_bonus_for_members" enctype="multipart/form-data" accept-charset="utf-8">';

$body_html = '
        <tr>
            <td class="colhead" colspan="2">' . _('Mass bonus for all or selected members:') . '</td>
        </tr>
        <tr>
            <td><span class="has-text-weight-bold">' . _('Apply bonus to:') . '</span></td>
            <td>
                <div>' . $all_classes_check_boxes . '</div>
            </td>
        </tr>
        <tr>
            <td class="w-25"><span class="has-text-weight-bold">' . _('Bonus Type:') . '</span></td>
            <td>' . $drop_down . '
                <div id="div_upload_credit" class="select_me"><br>' . $bonus_GB . '</div>
                <div id="div_karma" class="select_me"><br>' . $karma_drop_down . '</div>
                <div id="div_freeslots" class="select_me"><br>' . $free_leech_slot_drop_down . '</div>
                <div id="div_invite" class="select_me"><br>' . $invites_drop_down . '</div>
                <div id="div_pm" class="select_me"><br>' . $pm_drop_down . '</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="has-text-centered margin20">' . _("*** Please note, PM's are automatically sent to all users awarded by the script.") . '</div>
                <div class="has-text-centered margin20">
                    <input type="submit" class="button is-small" value="' . _('Do it') . '">
                </div>
            </td>
        </tr>';

$HTMLOUT .= main_table($body_html) . '
    </form>';

$title = _('Bonus Manager');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);

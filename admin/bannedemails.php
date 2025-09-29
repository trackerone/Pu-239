<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;


global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$db = $container->get(Database::class);
$fluent = $db;
$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$self = $s($_SERVER['PHP_SELF'] ?? '');
$baseurl = $s($config->get('paths.baseurl'));

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$HTMLOUT = '';
$remove = isset($_GET['remove']) ? (int) $_GET['remove'] : 0;
if (is_valid_id($remove)) {
    $db->run('DELETE FROM bannedemails WHERE id = :id', [':id' => $remove]);
    write_log(_fe('Email ban {0} was removed by {1}', $remove, $CURUSER['username']));
    audit_log($CURUSER['id'] ?? null, 'user.unban', ['target' => $remove, 'type' => 'email']);
    // >>>>>> PU239:audit-hook-6
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): csrf
    $email = htmlsafechars($_POST['email']);
    $comment = htmlsafechars($_POST['comment']);
    if (!$email || !$comment) {
        stderr(_('Error'), _('Missing Form Data.'));
    }
    $db->run(
        'INSERT INTO bannedemails (added, addedby, comment, email) VALUES (:added, :addedby, :comment, :email)',
        [
            ':added' => TIME_NOW,
            ':addedby' => $CURUSER['id'],
            ':comment' => $comment,
            ':email' => $email,
        ],
    );
    audit_log($CURUSER['id'] ?? null, 'user.ban', ['target' => $email, 'type' => 'email']);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=bannedemails');
    app_halt('Exit called');
}
$HTMLOUT .= "
    <h1 class='has-text-centered'>" . _('Add Ban') . "</h1>
    <form method='post' action='staffpanel.php?tool=bannedemails' enctype='multipart/form-data' accept-charset='utf-8'>";
$body = "
        <tr>
            <td class='rowhead'>" . _('Email') . "</td>
            <td><input type='text' name='email' size='40'></td></tr>
            <tr><td class='rowhead has-text-left'>" . _('Comment') . "</td>
            <td><input type='text' name='comment' size='40'></td>
        </tr>
        <tr>
            <td colspan='2'>" . _('Use *@email.com as wildcard for domain.') . "</td>
        </tr>
        <tr>
            <td colspan='2' class='has-text-centered'>
                <input type='submit' value='" . _('Ok') . "' class='button is-small'>
            </td>
        </tr>";
$HTMLOUT .= main_table($body) . '
    </form>';
$count1 = $fluent->from('bannedemails')
                 ->select(null)
                 ->select('COUNT(id) AS count')
                 ->fetch('count');
$perpage = 15;
$pager = pager($perpage, $count1, 'staffpanel.php?tool=bannedemails&amp;');
$rows = $db->fetchAll(
    'SELECT b.id, b.added, b.addedby, b.comment, b.email, u.username FROM bannedemails AS b LEFT JOIN users AS u ON b.addedby = u.id ORDER BY added DESC ' . $pager['limit']
);
$HTMLOUT .= "<h1 class='has-text-centered'>" . _('Current Banned Emails') . '</h1>';
if ($count1 > $perpage) {
    $HTMLOUT .= $pager['pagertop'];
}
if (empty($rows)) {
    $HTMLOUT .= stdmsg('Sorry', '<p><b>' . _('Nothing Found!') . '</b></p>');
} else {
    $heading = '
        <tr>
            <th>' . _('Added') . '</th>
            <th>' . _('Email') . '</th>
            <th>' . _('By') . '</th>
            <th>' . _('Comment') . '</th>
            <th>' . _('Remove?') . '</th>
        </tr>';
    $body = '';
    foreach ($rows as $arr) {
        $addedOn = $s(get_date((int) $arr['added'], ''));
        $email = $s($arr['email']);
        $comment = $s($arr['comment']);
        $id = $s((string) $arr['id']);
        $body .= "
        <tr>
            <td>{$addedOn}</td>
            <td>{$email}</td>
            <td>" . format_username((int) $arr['addedby']) . "</td>
            <td>{$comment}</td>
            <td><a href='staffpanel.php?tool=bannedemails&amp;remove={$id}'>" . _('Remove it') . "</a></td>
        </tr>";
    }
    $HTMLOUT .= main_table($body, $heading);
}
if ($count1 > $perpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}
$title = _('Banned Emails');
$breadcrumbs = [
    "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$self}'>" . $s($title) . '</a>',
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\User;


global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

stderr(_('Error'), 'This page is not in use atm');

$HTMLOUT = '';
$id = (int) $_GET['id'];
if (!is_valid_id($id) || $CURUSER['id'] != $id && $CURUSER['class'] < UC_STAFF) {
    $id = $CURUSER['id'];
}
$count = (int) $db->fetch(
    'SELECT COUNT(id) AS count FROM userhits WHERE hitid = :id',
    [':id' => $id],
)['count'];
$perpage = 15;
$pager = pager($perpage, $count, "staffpanel.php?tool=user_hits&amp;id=$id&amp;");
if (!$count) {
    stderr(_('No views'), _('This user has had no profile views yet.'));
}
$users_class = $container->get(User::class);
$user = $db->fetch('SELECT username FROM users WHERE id = :id', [':id' => $id]);
$HTMLOUT .= '<h1>' . _('Profile views of ') . '' . format_username((int) $id) . '</h1>
<h2>' . _('In total ') . '' . htmlsafechars($count) . '' . _(' views') . '</h2>';
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagertop'];
}
$HTMLOUT .= "
<table>
<tr>
<td class='colhead'>" . _('Nr.') . "</td>
<td class='colhead'>" . _('Username') . "</td>
<td class='colhead'>" . _('Viewed at') . "</td>
</tr>\n";
$rows = $db->fetchAll(
    'SELECT uh.*, username, users.id AS uid FROM userhits AS uh LEFT JOIN users ON uh.userid = users.id WHERE hitid = :id ORDER BY uh.id DESC ' . $pager['limit'],
    [':id' => $id],
);
foreach ($rows as $arr) {
    $HTMLOUT .= '
<tr><td>' . number_format($arr['number']) . '</td>
<td>' . format_username((int) $arr['uid']) . '</td>
<td>' . get_date((int) $arr['added'], 'DATE', 0, 1) . "</td>
</tr>\n";
}
$HTMLOUT .= '</table>';
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}
$title = _('Profile Views');
$breadcrumbs = [
    "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

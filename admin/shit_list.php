<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

use Pu239\Cache;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $site_config, $CURUSER;

$HTMLOUT = $message = $title = '';
//=== check if action2 is sent (either $_POST or $_GET) if so make sure it's what you want it to be
$action2 = isset($_POST['action2']) ? htmlsafechars($_POST['action2']) : (isset($_GET['action2']) ? htmlsafechars($_GET['action2']) : '');
$good_stuff = [
    'new',
    'add',
    'delete',
];
$action2 = (($action2 && in_array($action2, $good_stuff, true)) ? $action2 : '');
//=== action2 switch... do what must be done!
$cache = $container->get(Cache::class);
switch ($action2) {
    //=== action2: new

    case 'new':
        $shit_list_id = isset($_GET['shit_list_id']) ? (int) $_GET['shit_list_id'] : 0;
        $return_to = str_replace('&amp;', '&', htmlsafechars($_GET['return_to']));
        $cache->delete('shit_list_' . $CURUSER['id']);
        if ($shit_list_id == $CURUSER['id']) {
            stderr(_('Error'), _("Can't add yourself"));
        }
        if (!is_valid_id($shit_list_id)) {
            stderr(_('Error'), _('Invalid ID'));
        }
        $res_name = $db->run(');
        $cache->delete('shit_list_' . $shit_list_id);
        $message = '<h1>' . _('Success! Member added to your personal shitlist!') . '</h1><a class="is-link" href="' . $return_to . '"><span class="button is-small" style="padding:1px;">' . _('go back to where you were?') . '</span></a>';
        break;
    //=== action2: delete

    case 'delete':
        $shit_list_id = isset($_GET['shit_list_id']) ? (int) $_GET['shit_list_id'] : 0;
        $sure = isset($_GET['sure']) ? (int) $_GET['sure'] : 0;
        if (!is_valid_id($shit_list_id)) {
            stderr(_('Error'), _('Invalid ID'));
        }
        $res_name = $db->run(');
//=== default page
$HTMLOUT .= $message . '
   <legend>' . _fe('Shit List for {0}', format_comment($CURUSER['username'])) . '</legend>
   <table class="table table-bordered">
   <tr>
     <td class="colhead" colspan="4">
     <img src="' . $site_config['paths']['images_baseurl'] . 'smilies/shit.gif" alt=" * ">' . _('shittiest at the top ') . '<img src="' . $site_config['paths']['images_baseurl'] . 'smilies/shit.gif" alt=" * "></td>
   </tr>';
$i = 1;
if (empty($rows)) {
    $HTMLOUT .= '
   <tr>
      <td colspan="4">
      <img src="' . $site_config['paths']['images_baseurl'] . 'smilies/shit.gif" alt=" * ">' . _('Your shit list is empty. ') . '<img src="' . $site_config['paths']['images_baseurl'] . 'smilies/shit.gif" alt="*"></td>
   </tr>';
} else {
    while ($shit_list = mysqli_fetch_array($res)) {
        $shit = '';
        for ($poop = 1; $poop <= $shit_list['shittyness']; ++$poop) {
            $shit .= ' <img src="' . $site_config['paths']['images_baseurl'] . 'smilies/shit.gif" title="' . _fe('{0} out of 10 on the shittyness scale', $shit_list['shittyness']) . '" alt=" * ">';
        }
        $HTMLOUT .= (($i % 2 == 1) ? '<tr>' : '') . '
      <td class="has-text-centered w-15 mw-150 ' . (($i % 2 == 0) ? 'one' : 'two') . '">' . get_avatar($shit_list) . '<br>

      ' . format_username((int) $shit_list['id']) . '<br>

      <b> [ ' . get_user_class_name((int) $shit_list['class']) . ' ]</b><br>

      <a class="is-link" href="' . $site_config['paths']['baseurl'] . '/staffpanel.php?tool=shit_list&amp;action=shit_list&amp;action2=delete&amp;shit_list_id=' . (int) $shit_list['suspect_id'] . '" title="' . _('remove this toad from your shit list') . '"><span class="button is-small" style="padding:1px;"><img style="vertical-align:middle;" src="' . $site_config['paths']['images_baseurl'] . 'polls/p_delete.gif" alt="Delete">' . _('Remove') . '</span></a>
      <a class="is-link" href="messages.php?action=send_message&amp;receiver=' . (int) $shit_list['suspect_id'] . '" title="' . _('send a PM to this evil toad') . '"><span class="button is-small" style="padding:1px;"><img style="vertical-align:middle;" src="' . $site_config['paths']['images_baseurl'] . 'message.gif" alt="Message">' . _('Send PM') . '</span></a></td>
      <td class="' . (($i % 2 == 0) ? 'one' : 'two') . '">' . $shit . '
      <b>' . _('joined: ') . '</b> ' . get_date((int) $shit_list['added'], '') . '
      [ ' . get_date((int) $shit_list['added'], '', 0, 1) . ' ]
      <b>' . _('added to shit list: ') . '</b> ' . get_date((int) $shit_list['shit_list_added'], '') . '
      [ ' . get_date((int) $shit_list['shit_list_added'], '', 0, 1) . ' ]
      <b>last seen:</b> ' . get_date((int) $shit_list['last_access'], '') . ' 
      [ ' . get_date((int) $shit_list['last_access'], '', 0, 1) . ' ]<hr>
      ' . format_comment($shit_list['text']) . '</td>' . (($i % 2 == 0) ? '</tr><tr><td class="colhead" colspan="4"></td></tr>' : '');
        ++$i;
    }
}
$HTMLOUT .= (($i % 2 == 0) ? '<td class="one" colspan="2"></td></tr>' : '');
$HTMLOUT .= '</table><p><span class="button is-small" style="padding:3px;"><img style="vertical-align:middle;" src="' . $site_config['paths']['images_baseurl'] . 'btn_search.gif" alt="Search"><a class="is-link" href="' . $site_config['paths']['baseurl'] . '/users.php">' . _('Find Member / Browse Member List') . '</span></a></p>';
$title = _('Shitlist');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

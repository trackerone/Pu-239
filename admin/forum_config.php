<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;

require_once INCL_DIR . 'function_html.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $container, $site_config;

$HTMLOUT = $time_drop_down = $accepted_file_extension = $accepted_file_types = $member_class_drop_down = '';
$settings_saved = false;
$config_id = 1;
$db = $container->get(Database::class);
$fluent = $db;
$cache = $container->get(Cache::class);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_it'])) {
    $update = [
        'delete_for_real' => isset($_POST['delete_for_real']) ? (int) $_POST['delete_for_real'] : 0,
        'min_delete_view_class' => isset($_POST['min_delete_view_class']) && valid_class((int) $_POST['min_delete_view_class']) ? (int) $_POST['min_delete_view_class'] : 0,
        'readpost_expiry' => isset($_POST['readpost_expiry']) ? (int) $_POST['readpost_expiry'] : 0,
        'min_upload_class' => isset($_POST['min_upload_class']) && valid_class((int) $_POST['min_upload_class']) ? (int) $_POST['min_upload_class'] : 0,
        'accepted_file_extension' => isset($_POST['accepted_file_extension']) ? preg_replace('/\s+/', '|', trim($_POST['accepted_file_extension'])) : '',
        'accepted_file_types' => isset($_POST['accepted_file_types']) ? preg_replace('/\s+/', '|', trim($_POST['accepted_file_types'])) : '',
        'max_file_size' => isset($_POST['max_file_size']) ? (int) $_POST['max_file_size'] : 0,
    ];
    $fluent->update('forum_config')
           ->set($update)
           ->where('id = ?', $config_id)
           ->execute();
    $cache->delete('forum_config_');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=forum_config');
    app_halt('Exit called');
}
$main_links = "
            <div class='bottom20'>
                <ul class='level-center bg-06'>
                    <li class='is-link margin10'>
                        <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=over_forums&amp;action=over_forums'>" . _('Over Forums') . "</a>
                    </li>
                    <li class='is-link margin10'>
                        <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=forum_manage&amp;action=forum_manage'>" . _('Forum Manager') . "</a>
                    </li>
                </ul>
            </div>
            <h1 class='has-text-centered'>" . _('Config Forums') . '</h1>';

$rows = $db->fetchAll('SELECT delete_for_real, min_delete_view_class, readpost_expiry, min_upload_class, accepted_file_extension,
                                accepted_file_types, max_file_size FROM forum_config WHERE id = ' . sqlesc($config_id));
$arr = mysqli_fetch_array($res);
$weeks = 1;
for ($i = 7; $i <= 365; $i = $i + 7) {
    $time_drop_down .= '<option class="body" value="' . $i . '" ' . ($arr['readpost_expiry'] == $i ? 'selected' : '') . '>' . $weeks . ' ' . _('week') . plural($weeks) . '</option>';
    $weeks = $weeks + 1;
}
$accepted_file_extension = (!empty($arr['accepted_file_extension'])) ? str_replace('|', ' ', $arr['accepted_file_extension']) : [];
$accepted_file_types = (!empty($arr['accepted_file_types'])) ? str_replace('|', ' ', $arr['accepted_file_types']) : [];
$HTMLOUT .= $main_links . '<form method="post" action="staffpanel.php?tool=forum_config" accept-charset="utf-8">
            <input type="hidden" name="do_it" value="1">
        <table class="table table-bordered table-striped">
        <tr>
            <td><span class="has-text-weight-bold">' . _('Delete posts/topics:') . '</span></td>
            <td>
            <input type="radio" name="delete_for_real" value="1" ' . ($arr['delete_for_real'] == 1 ? 'checked' : '') . '>' . _('Yes') . '
            <input type="radio" name="delete_for_real" value="0" ' . ($arr['delete_for_real'] == 0 ? 'checked' : '') . '>' . _('No') . '<br>
            ' . _('Setting this to No will give the option for selected class and above to see deleted posts and threads and decide if they should be deleted.') . '</td>
        </tr>
        <tr>
            <td><span class="has-text-weight-bold">' . _('The minimum grade for deletion') . '</span></td>
            <td>
            <select name="min_delete_view_class"> ' . member_class_drop_down($arr['min_delete_view_class']) . '</select><br>
            ' . _('Set this to the lowest member class you wish to be able to view deleted posts and threads.') . '<br>' . _('[Implicit - Admin]') . '</td>
        </tr>
        <tr>
            <td><span class="has-text-weight-bold">' . _('Read post expiration') . ':</span></td>
            <td>
            <select name="readpost_expiry"> ' . $time_drop_down . '</select><br>
            ' . _('All postings older than that will be set as read.') . '</td>
        </tr>
        <tr>
            <td><span class="has-text-weight-bold">' . _('Minimum class load') . ':</span></td>
            <td>
            <select name="min_upload_class"> ' . member_class_drop_down($arr['min_upload_class']) . '</select><br>
            ' . _('Set this to the lowest member class you wish to give the right to add attachments to a post.') . '</td>
        </tr>
          <tr>
            <td><span class="has-text-weight-bold">' . _('Files acceptable extensions') . ':</span>  </td>
            <td>
            <input name="accepted_file_extension" type="text" class="w-100" maxlength="80" value="' . htmlsafechars((string) $accepted_file_extension) . '"><br>
            ' . _('Defaults are: zip and rar. Add more at your own risk! Each entry must be separated by a single space.') . '</td>
         </tr>
          <tr>
            <td><span class="has-text-weight-bold">' . _('File types supported') . ':</span>  </td>
            <td>
            <input name="accepted_file_types" type="text" class="w-100" value="' . htmlsafechars((string) $accepted_file_types) . '"><br>
            ' . _("Must match the above accepted file ext's. Add more at your own risk! Each entry must be separated by a single space") . '</td>
         </tr>
          <tr>
            <td><span class="has-text-weight-bold">' . _('Maximum File Size') . ':</span>  </td>
            <td>
            <input name="max_file_size" type="number" class="w-100" value="' . (int) $arr['max_file_size'] . '"><br>
            ' . _('The default setting-2 MBs, is currently set to: ') . mksize($arr['max_file_size']) . '.</td>
         </tr>
        <tr>
            <td colspan="2" class="has-text-centered">
            <input type="submit" name="button" class="button is-small margin20" value="' . _('Save the settings') . '"></td>
        </tr>
        </table></form>';
/**
 * @param $member_class
 *
 * @return string
 */
function member_class_drop_down($member_class)
{
    $member_class_drop_down = '';
    for ($i = 0; $i <= UC_MAX; ++$i) {
        $member_class_drop_down .= '<option class="body" value="' . $i . '" ' . ($member_class == $i ? 'selected' : '') . '>' . get_user_class_name((int) $i) . '</option>';
    }

    return $member_class_drop_down;
}
$title = _('Config Forums');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

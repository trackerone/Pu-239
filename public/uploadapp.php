<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Delight\Auth\Auth;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Pu239\Roles;
use Rakit\Validation\Validator;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
$user = check_user_status();
global $container, $site_config;

$HTMLOUT = '';

$fluent = $container->get(Database::class);
$cache = $container->get(Cache::class);
$messages_class = $container->get(Message::class);
$auth = $container->get(Auth::class);
if ($auth->hasRole(Roles::UPLOADER)) {
    stderr(_('Access Denied'), _('It appears you are already part of our uploading team.'));
}
function check_status(Database $fluent, int $userid)
{
    $applicant = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        foreach ($subres as $arr) {
            $msgs_buffer[] = [
                'receiver' => $arr['id'],
                'added' => $dt,
                'msg' => $msg,
                'subject' => $subject,
            ];
        }
        if (!empty($msgs_buffer)) {
            $messages_class->insert($msgs_buffer);
        }
        stderr(_('Application sent'), _('Your application has successfully been sent to the staff.'));
    }
}

$ratio = member_ratio($user['uploaded'], $user['downloaded']);
$connect = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchOne($sql, [/* params */]);;
if (!empty($connect)) {
    $Conn_Y = 'yes';
    if ($connect == $Conn_Y) {
        $connectable = 'Yes';
    } else {
        $connectable = 'No';
    }
} else {
    $connectable = 'Pending';
}

$HTMLOUT .= '
        <h1 class="has-text-centered">' . _('Uploader application') . "</h1>
        <form action='./uploadapp.php' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
            <div class='table-wrapper'>
                <table class='table table-bordered table-striped'>
                    <tr>
                        <td class='rowhead'>" . _('My username is') . "</td>
                        <td>
                            <input name='userid' type='hidden' value='" . (int) $user['id'] . "'>
                            {$user['username']}
                         </td>
                    </tr>
                    <tr>
                        <td class='rowhead'>" . _('I joined') . '</td>
                        <td>' . get_date((int) $user['registered'], '', 0, 1) . "</td>
                    </tr>
                    <tr>
                        <td class='rowhead'>" . _('I have a positive ratio') . '</td>
                        <td>' . ($ratio >= 1 ? 'No' : 'Yes') . "</td>
                    </tr>
                    <tr>
                        <td class='rowhead'>
                            " . _('I am connectable') . "
                        </td>
                        <td>
                            <input name='connectable' type='hidden' value='$connectable'>$connectable
                        </td>
                    </tr>
                    <tr>
                        <td class='rowhead'>
                            " . _('My upload speed is') . "
                        </td>
                        <td>
                            <input type='text' name='speed' class='w-100' maxlength='20'>
                        </td>
                    </tr>
                    <tr>
                        <td class='rowhead'>
                            " . _('What I have to offer') . "
                        </td>
                        <td>
                            <textarea class='w-100' name='offer' rows='2'></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class='rowhead'>
                            " . _('Why I should be promoted') . "
                        </td>
                        <td>
                            <textarea class='w-100' name='reason' rows='2'></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class='rowhead'>
                            " . _('I am an uploader at other sites') . "
                        </td>
                        <td>
                            <div class='level-left'>
                                <input type='radio' name='sites' value='yes' class='right5'>" . _('Yes') . "
                                <input name='sites' type='radio' value='no' class='left20 right5' checked>" . _('No') . "
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class='rowhead'>
                            " . _('Those sites are') . "</td>
                        <td>
                            <input type='text' class='w-100' name='sitenames' maxlength='150'>
                        </td>
                    </tr>
                    <tr>
                        <td class='rowhead'>
                            " . _('I have scene access') . "
                        </td>
                        <td>
                            <div class='level-left'>
                                <input type='radio' name='scene' value='yes' class='right5'>" . _('Yes') . "
                                <input name='scene' type='radio' value='no' class='left20 right5' checked>" . _('No') . "
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            <div class='level-left top5'>
                                <span class='right10'>
                                    " . _('I know how to create, upload and seed torrents') . "
                                </span>
                                <input type='radio' name='creating' value='yes' class='right5'>" . _('Yes') . "
                                <input type='radio' name='creating' value='no' class='left20 right5' checked>" . _('No') . "
                            </div>
                            <div class='level-left top5 bottom5'>
                                <span class='right10'>
                                    " . _('I understand that I have to keep seeding my torrents until there are at least two other seeders') . "
                                </span>
                                <input type='radio' name='seeding' value='yes' class='right5'>" . _('Yes') . "
                                <input name='seeding' type='radio' value='no' class='left20 right5' checked>" . _('No') . "
                            </div>
                            <input name='form' type='hidden' value='1'>
                        </td>
                    </tr>
                </table>
            </div>
            <div class='has-text-centered margin20'>
                <input type='submit' name='Submit' value='" . _('Send') . "' class='button is-small'>
            </div>
        </form>";
$title = _('Uploader Application');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();

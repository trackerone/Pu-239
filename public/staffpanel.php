<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;
use Pu239\Radiance;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_staff.php';
require_once BIN_DIR . 'uglify.php';
require_once BIN_DIR . 'functions.php';
require_once CLASS_DIR . 'class_check.php';
$user = check_user_status();
global $container, $site_config;

if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] === 'reset=1') {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=op&reset=1');
    app_halt('Exit called');
} elseif (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/staffpanel.php?') {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=op');
    app_halt('Exit called');
}
$session = $container->get(Session::class);
$radiance = $container->get(Radiance::class);
class_check(UC_STAFF);
if (!$site_config['site']['staffpanel_online']) {
    stderr(_('Information'), _('The staffpanel is currently offline for maintenance work'));
}
if ($site_config['tracker']['radiance'] && !file_exists($site_config['tracker']['config_path'])) {
    $session->set('is-danger', "{$site_config['tracker']['config_path']} does not exist. Please set the path correctly in the site settings -> tracker.");
}
$stdhead = [
    'css' => [
        get_file_name('sceditor_css'),
    ],
];
$stdfoot = [
    'js' => [
        get_file_name('sceditor_js'),
        get_file_name('navbar_show_js'),
    ],
];

$HTMLOUT = $page_name = $file_name = $navbar = '';
$fluent = $container->get(Database::class);
$cache = $container->get(Cache::class);
$cache->delete('staff_classes_');
$staff_classes = $cache->get('staff_classes_');
if ($staff_classes === false || is_null($staff_classes)) {
    $available_classes = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;
    foreach ($available_classes as $class) {
        $staff_classes[] = $class['value'];
    }
    $cache->set('staff_classes_', $staff_classes, 0);
}
$data = array_merge($_POST, $_GET);
$action = isset($data['action']) ? htmlsafechars($data['action']) : null;
$id = isset($data['id']) ? (int) $data['id'] : 0;
$tool = !empty($data['tool']) ? $data['tool'] : null;
write_info(_fe('{0} has accessed the {1}', $user['username'], empty($tool) ? 'staffpanel' : _fe('{0} staff page', $tool)));
$staff_tools = [
    'modtask' => 'modtask',
    'iphistory' => 'iphistory',
    'ipsearch' => 'ipsearch',
    'shit_list' => 'shit_list',
    'invite_tree' => 'invite_tree',
    'user_hits' => 'user_hits',
];
$file_names = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;
        if (!empty($data)) {
            $db_classes = $unique_classes = [];
            foreach ($data as $key => $value) {
                $db_classes[$value['av_class']][] = $value['av_class'];
            }
            $i = 1;
            $HTMLOUT .= "{$add_button}
            <h1 class='has-text-centered'>" . _('Welcome') . " {$user['username']} " . _('to the') . ' ' . _('Staff Panel') . '!</h1>';

            $header = "
                    <tr>
                        <th class='w-50'>" . _('Page name') . "</th>
                        <th><div class='has-text-centered'>" . _('Show in Navbar') . "</div></th>
                        <th><div class='has-text-centered'>" . _('Added by') . "</div></th>
                        <th><div class='has-text-centered'>" . _('Date added') . '</div></th>';
            if ($user['class'] >= UC_MAX) {
                $header .= "
                        <th><div class='has-text-centered'>" . _('Links') . '</div></th>';
            }
            $header .= '
                    </tr>';
            $body = '';
            foreach ($data as $key => $arr) {
                $end_table = count($db_classes[$arr['av_class']]) == $i ? true : false;

                if (!in_array($arr['av_class'], $unique_classes)) {
                    $unique_classes[] = $arr['av_class'];
                    $table = "
            <h1 class='has-text-centered text-shadow " . get_user_class_name((int) $arr['av_class'], true) . "'>" . _fe("{0}'s Panel", get_user_class_name((int) $arr['av_class'])) . '</h1>';
                }
                $show_in_nav = $arr['navbar'] == 1 ? '
                <span class="has-text-success show_in_navbar tooltipper" title="' . _('Hide from Navbar') . '" data-show="' . $arr['navbar'] . '" data-id="' . $arr['id'] . '">' . _('true') . '</span>' : '
                <span class="has-text-info show_in_navbar tooltipper" title="' . _('Show in Navbar') . '" data-show="' . $arr['navbar'] . '" data-id="' . $arr['id'] . '">' . _('false') . '</span>';
                $body .= "
                    <tr>
                        <td>
                            <div class='size_4'>
                                <a href='{$site_config['paths']['baseurl']}/" . htmlsafechars($arr['file_name']) . "' class='tooltipper' title='" . htmlsafechars($arr['description'] . '<br>' . $arr['file_name']) . "'>" . ucwords(htmlsafechars($arr['page_name'])) . "</a>
                            </div>
                        </td>
                        <td>
                            <div class='has-text-centered'>
                                {$show_in_nav}
                            </div>
                        </td>
                        <td>
                            <div class='has-text-centered'>
                                " . format_username((int) $arr['added_by']) . "
                            </div>
                        </td>
                        <td>
                            <div class='has-text-centered'>
                                <span>" . get_date((int) $arr['added'], 'DATE', 0, 1) . '</span>
                            </div>
                        </td>';
                if (has_access($user['class'], UC_MAX, 'coder')) {
                    $body .= "
                        <td>
                            <div class='level-center'>
                                <a href='{$_SERVER['PHP_SELF']}?action=edit&amp;id=" . (int) $arr['id'] . "' class='tooltipper' title='" . _('Edit') . "'>
                                    <i class='icon-edit icon has-text-info' aria-hidden='true'></i>
                                </a>
                                <a href='{$_SERVER['PHP_SELF']}?action=delete&amp;id=" . (int) $arr['id'] . "' class='tooltipper' title='" . _('Delete') . "'>
                                    <i class='icon-trash-empty icon has-text-danger' aria-hidden='true'></i>
                                </a>
                            </div>
                        </td>";
                }
                $body .= '
                    </tr>';
                ++$i;
                if ($end_table) {
                    $i = 1;
                    $HTMLOUT .= "<div class='bg-00 top20 round10'>$table" . main_table($body, $header) . '</div>';
                    $body = '';
                }
            }
        } else {
            $HTMLOUT .= stdmsg(_('Sorry'), _('Nothing found.'));
        }
        $title = _('Staff Panel');
        $breadcrumbs = [
            "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
        ];
        echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
    }
}

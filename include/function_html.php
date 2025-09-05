<?php
require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Delight\Auth\AuthError;
use Delight\Auth\NotLoggedInException;
use DI\DependencyException;
use DI\NotFoundException;
use Intervention\Image\Image;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use PHPMailer\PHPMailer\PHPMailer;
use Pu239\Cache;
use Pu239\Database;
use Pu239\ImageProxy;
use Spatie\Image\Exceptions\InvalidManipulation;

require_once INCL_DIR . 'function_categories.php';

/**
 * @return string
 */
function begin_main_frame()
{
    return "
            <table class='table table-bordered table-striped'>
                <tr>
                    <td class='embedded'>";
}

/**
 * @return string
 */
function end_main_frame()
{
    return '
                    </td>
                </tr>
            </table>';
}

/**
 * @param string $caption
 * @param bool   $center
 *
 * @return string
 */
function begin_frame($caption = '', $center = false)
{
    $tdextra = '';
    $htmlout = '';
    $center = $center ? " class='has-text-centered'" : '';
    if ($caption) {
        $htmlout .= "<h1{$center}>$caption</h1>";
    }
    $htmlout .= "<table class='shit table table-bordered table-striped'><tr><td$tdextra>\n";

    return $htmlout;
}

/**
 * @return string
 */
function end_frame()
{
    return "</td></tr></table>\n";
}

/**
 * @param bool $striped
 *
 * @return string
 */
function begin_table($striped = false)
{
    $htmlout = '';
    $stripe = $striped ? ' table-striped' : '';
    $htmlout .= "<table class='table table-bordered{$stripe}'>\n";

    return $htmlout;
}

/**
 * @return string
 */
function end_table()
{
    return "</table>\n";
}

/**
 * @param        $x
 * @param        $y
 * @param bool   $noesc
 * @param string $class
 *
 * @return string
 */
function tr($x, $y, $noesc = false, $class = '')
{
    if ($noesc) {
        $a = $y;
    } else {
        $a = htmlsafechars($y);
        $a = str_replace("\n", "<br>\n", $a);
    }

    $class = !empty($class) ? " class='$class'" : '';

    return "
        <tr>
            <td class='rowhead'>
                $x
            </td>
            <td{$class}>
                $a
            </td>
        </tr>";
}

/**
 * @return string
 */
function insert_smilies_frame()
{
    global $smilies, $site_config;
    $htmlout = '';
    $htmlout .= begin_frame('Smilies', true);
    $htmlout .= begin_table(false);
    $htmlout .= "<tr><td class='colhead'>Type...</td><td class='colhead'>To make a...</td></tr>\n";
    foreach ($smilies as $code => $url) {
        $htmlout .= "<tr><td>$code</td><td><img src=\"{$site_config['paths']['images_baseurl']}smilies/{$url}\" alt=''></td></tr>\n";
    }
    $htmlout .= end_table();
    $htmlout .= end_frame();

    return $htmlout;
}

/**
 * @param string      $body
 * @param string|null $header
 * @param string|null $class
 * @param string|null $wrapper_class
 * @param string|null $striped
 * @param string|null $id
 * @param bool|null   $wrapper
 *
 * @return string
 */
function main_table(string $body, ?string $header = null, ?string $class = null, ?string $wrapper_class = null, ?string $striped = 'table-striped', ?string $id = null, ?bool $wrapper = true)
{
    $id = !empty($id) ? " id='$id'" : '';
    $thead = $header != null ? "
                        <thead>
                            $header
                        </thead>" : '';
    $table = "
                    <table{$id} class='table table-bordered $striped $class'>
                        $thead
                        <tbody>
                            $body
                        </tbody>
                    </table>";
    if ($wrapper) {
        return table_wrapper($table, $wrapper_class);
    }

    return $table;
}

/**
 * @param string      $table
 * @param string|null $wrapper_class
 *
 * @return string
 */
function table_wrapper(string $table, ?string $wrapper_class = null)
{
    return "
                <div class='table-wrapper $wrapper_class'>
                    $table
                </div>";
}

/**
 * @param      $text
 * @param null $outer_class
 * @param null $inner_class
 *
 * @return string|void
 */
function main_div($text, $outer_class = null, $inner_class = null)
{
    if ($text === '') {
        return;
    } else {
        return "
                <div class='bordered bg-02 $outer_class'>
                    <div class='alt_bordered bg-00 $inner_class'>$text
                    </div>
                </div>";
    }
}

/**
 * @param        $text
 * @param string $class
 *
 * @return string|void
 */
function wrapper($text, $class = '')
{
    if ($text === '') {
        return;
    } else {
        return "
            <div class='portlet $class'>
                $text
            </div>";
    }
}

/**
 * @param $data
 * @param $template
 */
function write_css($data, $template)
{
    $classdata = '';
    foreach ($data as $class) {
        $cname = strtolower(str_replace('UC_', '', $class['name']));
        $ccolor = strtolower($class['classColor']);
        if (!empty($cname)) {
            $classdata .= ".{$cname} {
    color: $ccolor;
}
";
        }
    }
    $classdata .= '#content .chatbot {
    color: #ff8b49;
    text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;
}
';
    foreach ($data as $class) {
        $cname = strtolower(str_replace('UC_', '', $class['name']));
        if (!empty($cname)) {
            $classdata .= "#content #chatList span.{$cname} {
    font-weight: bold;
}
";
        }
    }
    $classdata .= '#content #chatList span.chatbot {
    font-weight: bold;
    font-style: italic;
}
';
    foreach ($data as $class) {
        $cname = strtolower(str_replace('UC_', '', $class['name']));
        $ccolor = strtolower($class['classColor']);
        if (!empty($cname)) {
            $classdata .= ".{$cname}_bk {
    background-color: $ccolor;
}
";
        }
    }
    if (file_exists(ROOT_DIR . "chat/css/{$template}")) {
        file_put_contents(ROOT_DIR . "chat/css/{$template}/classcolors.css", $classdata . PHP_EOL);
    }
    if (file_exists(ROOT_DIR . "templates/{$template}/css")) {
        file_put_contents(ROOT_DIR . "templates/{$template}/css/classcolors.css", $classdata . PHP_EOL);
    }
}

/**
 * @param $data
 * @param $classes
 */
function write_classes($data, $classes)
{
    $html = file_get_contents(CHAT_DIR . 'js/config.js');
    $classes = "bbCodeTags: [\n        'b',\n        'i',\n        'u',\n        'quote',\n        'code',\n        'color',\n        'url',\n        'img',\n        'chatbot',\n        'center',\n        'updown',\n        'video',\n        'size_7',\n        'size_6',\n        'size_5',\n        'size_4',\n        'size_3',\n        'size_2',\n        'size_1',\n        '" . implode("',\n        '", $classes) . "'\n    ],";
    $html = preg_replace('/(bbCodeTags:\s+\[.*?\],)/s', $classes, $html);
    file_put_contents(CHAT_DIR . 'js/config.js', $html);

    $text = '

ajaxChat.getRoleClass = function(roleID) {
    switch (parseInt(roleID)) {';
    foreach ($data as $class) {
        $text .= "
        case parseInt($class):
            return '" . strtolower(str_replace('UC_', '', $class)) . "';";
    }
    $text .= "
        case parseInt(ajaxChat.chatBotRole):
            return 'chatbot';
        default:
            return 'user';
    }
};";

    file_put_contents(ROOT_DIR . 'chat/js/classes.js', $text, FILE_APPEND);
}

/**
 * @param $template
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function write_class_files($template)
{
    global $container;

    $fluent = $container->get(Database::class);
    $classes = $js_classes = $config_classes = $data = [];
    $t = 'define(';
    $configfile = "<?php\n\ndeclare(strict_types = 1);\n\n";
    $res = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    if (!empty($valid)) {
        if ($full) {
            return $valid;
        }

        return $valid['link'];
    }

    return $valid;
}

/**
 *
 * @param array $classes
 *
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 * @throws NotFoundException
 *
 * @return string
 *
 */
function category_dropdown(array $classes = [])
{
    global $post_data;

    $cats = genrelist(true);
    $s = "
            <select id='upload_category' name='type' class='w-100' required>
                <option value='' disabled selected>" . _('Choose One') . '</option>';
    foreach ($cats as $cat) {
        foreach ($cat['children'] as $row) {
            if (empty($classes) || in_array($row['id'], $classes)) {
                $s .= "
                <option value='{$row['id']}' " . (!empty($post_data['category']) && $post_data['category'] === $row['id'] ? 'selected' : '') . '>' . htmlsafechars($cat['name']) . '::' . htmlsafechars($row['name']) . '</option>';
            }
        }
    }
    $s .= '
            </select>';

    return $s;
}
